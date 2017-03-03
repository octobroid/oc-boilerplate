<?php namespace Triasrahman\Premailer;

use Backend;
use Event;
use System\Classes\PluginBase;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;


/**
 * Premailer Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Premailer',
            'description' => 'CSS inliner before sending mail.',
            'author'      => 'Trias Nur Rahman',
            'homepage'    => 'http://triasrahman.com/',
            'icon'        => 'icon-envelope'
        ];
    }

    /**
     * Attach event listeners on boot.
     * 
     * @return void
     */
    public function boot()
    {
        // When preparing html to be sent
        Event::listen('mailer.prepareSend', function($self, $view, $message) {
            // Get the Swift mail message
            $swift = $message->getSwiftMessage();

            // Convert to inline css
            $cssToInlineStyles = new CssToInlineStyles($swift->getBody());
            $cssToInlineStyles->setUseInlineStylesBlock(true);
            $cssToInlineStyles->setCleanup(true);
            $cssToInlineStyles->setStripOriginalStyleTags(true);
            
            // Set the body of mail
            $swift->setBody($cssToInlineStyles->convert());
        }, 1);

    }

}
