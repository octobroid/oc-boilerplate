<?php namespace Backend\Widgets;

use Block;
use Backend;
use Redirect;
use BackendAuth;
use Backend\Classes\WidgetBase;

/**
 * RoleImpersonator widget.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class RoleImpersonator extends WidgetBase
{
    /**
     * bindToController
     */
    public function bindToController()
    {
        parent::bindToController();

        Block::append('banner-area', $this->makePartial('roleimpersonator'));
    }

    /**
     * loadAssets adds widget specific asset files. Use $this->addJs() and $this->addCss()
     * to register new assets to include on the page.
     * @return void
     */
    protected function loadAssets()
    {
        $this->addCss('css/roleimpersonator.css', 'core');
    }

    /**
     * onStopImpersonateRole
     */
    public function onStopImpersonateRole()
    {
        BackendAuth::stopImpersonateRole();

        if (post('redirect')) {
            return Backend::redirect('backend/userroles/update/'.$this->getImpersonatingRole()->id);
        }

        return Redirect::refresh();
    }

    /**
     * getImpersonatingRole
     */
    public function getImpersonatingRole()
    {
        return BackendAuth::getUser()->getRoleImpersonation();
    }
}
