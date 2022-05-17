<?php namespace Backend\Controllers;

use App;
use Log;
use Mail;
use Flash;
use System;
use Config;
use Backend;
use Request;
use Validator;
use BackendAuth;
use Backend\Models\AccessLog;
use Backend\Classes\Controller;
use Backend\Models\User as UserModel;
use System\Classes\UpdateManager;
use ApplicationException;
use ValidationException;
use Exception;

/**
 * Auth is the backend authentication controller
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 *
 */
class Auth extends Controller
{
    /**
     * @var array publicActions defines controller actions visible to unauthenticated users
     */
    protected $publicActions = [
        'index',
        'signin',
        'signout',
        'restore',
        'reset',
        'migrate',
        'setup'
    ];

    /**
     * __construct is the constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->layout = 'auth';
    }

    /**
     * index is the default route, redirects to signin or migrate
     */
    public function index()
    {
        if ($this->checkAdminAccounts()) {
            return Backend::redirect('backend/auth/migrate');
        }
        else {
            return Backend::redirect('backend/auth/signin');
        }
    }

    /**
     * signin displays the log in page
     */
    public function signin()
    {
        $this->bodyClass = 'signin';

        // Clear Cache and any previous data to fix invalid security token issue
        $this->setResponseHeader('Cache-Control', 'no-cache, no-store, must-revalidate');

        try {
            if ($this->checkPostbackFlag()) {
                return $this->handleSubmitSignin();
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
    }

    /**
     * handleSubmitSignin handles the submission of the sign in form
     */
    protected function handleSubmitSignin()
    {
        $rules = [
            'login' => 'required|between:2,255',
            'password' => 'required|between:4,255'
        ];

        $validation = Validator::make(post(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        if (is_null($remember = Config::get('backend.force_remember', true))) {
            $remember = (bool) post('remember');
        }

        // Authenticate user
        $user = BackendAuth::authenticate([
            'login' => post('login'),
            'password' => post('password')
        ], $remember);

        // Log the sign in event
        AccessLog::add($user);

        // Redirect to the intended page after successful sign in
        return Backend::redirectIntended('backend');
    }

    /**
     * signout logs out a backend user
     */
    public function signout()
    {
        BackendAuth::logout();

        // Add HTTP Header 'Clear Site Data' to purge all sensitive data upon signout
        if (Request::secure()) {
            $this->setResponseHeader(
                'Clear-Site-Data',
                'cache, cookies, storage, executionContexts'
            );
        }

        return Backend::redirect('backend');
    }

    /**
     * restore displays a page to request a password reset verification code
     */
    public function restore()
    {
        try {
            if ($this->checkPostbackFlag()) {
                return $this->handleSubmitRestore();
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
    }

    /**
     * handleSubmitRestore submits the restore form
     */
    protected function handleSubmitRestore()
    {
        $rules = [
            'login' => 'required|between:2,255'
        ];

        $validation = Validator::make(post(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $user = BackendAuth::findUserByLogin(post('login'));

        if (!$user) {
            // For security reasons, only show detailed error when debug mode is on
            if (System::checkDebugMode()) {
                throw new ValidationException([
                    'login' => trans('backend::lang.account.restore_error', ['login' => post('login')])
                ]);
            }
        }
        else {
            // User found, send reset email
            $code = $user->getResetPasswordCode();
            $link = Backend::url('backend/auth/reset/' . $user->id . '/' . $code);

            $data = [
                'name' => $user->full_name,
                'link' => $link,
            ];

            Mail::send('backend::mail.restore', $data, function ($message) use ($user) {
                $message->to($user->email, $user->full_name)->subject(trans('backend::lang.account.password_reset'));
            });
        }

        Flash::success(trans('backend::lang.account.restore_success'));
        return Backend::redirect('backend/auth/signin');
    }

    /**
     * reset backend user password using verification code
     */
    public function reset($userId = null, $code = null)
    {
        try {
            if ($this->checkPostbackFlag()) {
                return $this->handleSubmitReset();
            }

            if (!$userId || !$code) {
                throw new ApplicationException(trans('backend::lang.account.reset_error'));
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }

        $this->vars['code'] = $code;
        $this->vars['id'] = $userId;
    }

    /**
     * handleSubmitReset submits the reset form
     */
    protected function handleSubmitReset()
    {
        if (!post('id') || !post('code')) {
            throw new ApplicationException(trans('backend::lang.account.reset_error'));
        }

        $rules = [
            'password' => 'required|between:4,255'
        ];

        $validation = Validator::make(post(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $code = post('code');
        $user = BackendAuth::findUserById(post('id'));

        if (!$user) {
            throw new ApplicationException(trans('backend::lang.account.reset_error'));
        }

        // Validate password against policy
        $user->validatePasswordPolicy(post('password'));

        if (!$user->checkResetPasswordCode($code)) {
            throw new ApplicationException(trans('backend::lang.account.reset_error'));
        }

        if (!$user->attemptResetPassword($code, post('password'))) {
            throw new ApplicationException(trans('backend::lang.account.reset_fail'));
        }

        $user->clearResetPassword();

        BackendAuth::clearThrottleForUserId($user->id);

        Flash::success(trans('backend::lang.account.reset_success'));

        return Backend::redirect('backend/auth/signin');
    }

    /**
     * setup will allow a user to create the first admin account
     */
    public function setup()
    {
        $this->bodyClass = 'setup';

        if (!$this->checkAdminAccounts()) {
            return Backend::redirect('backend/auth/signin');
        }

        try {
            if ($this->checkPostbackFlag()) {
                return $this->handleSubmitSetup();
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
    }

    /**
     * handleSubmitSetup creates a new admin
     */
    protected function handleSubmitSetup()
    {
        if (!$this->checkAdminAccounts()) {
            return Backend::redirect('backend/auth/signin');
        }

        // Validate user input
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|between:6,255|email|unique:backend_users',
            'login' => 'required|between:2,255|unique:backend_users',
            'password' => 'required:create|between:4,255|confirmed',
            'password_confirmation' => 'required_with:password|between:4,255'
        ];

        $validation = Validator::make(post(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        // Validate password against policy
        (new UserModel)->validatePasswordPolicy(post('password'));

        // Create user and sign in
        $user = UserModel::createDefaultAdmin(post());
        BackendAuth::login($user);

        // Redirect
        Flash::success(__('Welcome to your Administration Area, :name', ['name' => post('first_name')]));
        return Backend::redirect('backend');
    }

    /**
     * migrate shows a progress bar while the database migrates
     */
    public function migrate()
    {
        $this->bodyClass = 'setup';

        if (!$this->checkAdminAccounts()) {
            return Backend::redirect('backend/auth/signin');
        }
    }

    /**
     * migrate_onMigrate migrates the database
     */
    public function migrate_onMigrate()
    {
        if (!$this->checkAdminAccounts()) {
            return Backend::redirect('backend/auth/signin');
        }

        try {
            UpdateManager::instance()->update();
        }
        catch (Exception $ex) {
            Log::error($ex);
            Flash::error($ex->getMessage());
        }

        return Backend::redirect('backend/auth/setup');
    }

    /**
     * checkPostbackFlag checks to see if the form has been submitted
     */
    protected function checkPostbackFlag(): bool
    {
        return Request::method() === 'POST' && post('postback');
    }

    /**
     * checkAdminAccounts will determine if this is a new installation
     */
    protected function checkAdminAccounts(): bool
    {
        // Debug mode must be turned on
        if (!System::checkDebugMode()) {
            return false;
        }

        // There must be no admin accounts, with database migrated
        if (System::hasDatabase() && UserModel::count() > 0) {
            return false;
        }

        // Ensures database hasn't fallen over
        if (!App::hasDatabase()) {
            return false;
        }

        return true;
    }
}
