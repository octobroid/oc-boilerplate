<?php namespace Backend\Controllers;

use Backend;
use Media\Controllers\Index as MediaController;

/**
 * CMS Media Manager
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 * @deprecated Use Media\Controllers\Index. Remove if year >= 2023.
 */
class Media extends MediaController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        traceLog('Controller Backend\Controllers\Media has been deprecated, use ' . MediaController::class . ' instead.');

        parent::__construct();

        $this->setResponse(Backend::redirect('media'));
    }
}
