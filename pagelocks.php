<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Assets;
use Grav\Common\Plugin;
use Grav\Common\User\DataUser\User;
use Grav\Plugin\PageLocks\LockHandler;

/**
 * Class PageLocksPlugin
 * @package Grav\Plugin
 */
class PageLocksPlugin extends Plugin
{
    protected string $route = 'locks';

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                // Uncomment following line when plugin requires Grav < 1.7
                // ['autoload', 100000],
                ['onPluginsInitialized', 0]
            ]
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        if ($this->isAdmin()) {
            // Handle possible PageLocks requests
            $lockHandler = new LockHandler();
            $response = $lockHandler->handleRequest();

            // if there was an async PageLocks request, return response and stop processing.
            if ($response) {
                echo json_encode($response);
                die();
            }

            $this->enable([
                // Put your main events here
                'onAssetsInitialized' => ['onAssetsInitialized', 0],
                'onAdminMenu' => ['onAdminMenu', 0],
                'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
            ]);

            return;
        }

        // Enable the main events we are interested in
        $this->enable([
            // Put your main events here
        ]);
    }

    /**
     * Add assets required for page
     */
    public function onAssetsInitialized()
    {
        // Should minified assets be used?
        $min = $this->config->get('plugins.pagelocks.productionMode', true) ? '.min' : '';

        /** @var Assets */
        $assets = $this->grav['assets'];

        // Add script for all Admin pages. Must at least check on which page user is.
        $assets->addJs("plugin://pagelocks/js/pagelocker$min.js");
        $assets->addCss("plugin://pagelocks/css/page.css");

        // Add scripts required for Admin page of PageLocks: /admin/locks
        $route = $this->grav['uri']->uri();

        if (preg_match('/\/admin\/locks$/', $route) === 1) {
            $assets->addJs("plugin://pagelocks/js/pagelocksadmin$min.js");
            $assets->addCss("plugin://pagelocks/css/lock-admin.css");
        }
    }

    /**
     * Add navigation item to the admin plugin
     */
    public function onAdminMenu()
    {
        $this->grav['twig']->plugins_hooked_nav['PLUGIN_PAGELOCKS.LOCKS'] = [
            'route' => $this->route,
            'icon' => 'fa-lock'
        ];
    }

    /**
     * Add plugin templates path
     */
    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/admin/templates';
    }

    /**
     * Check if user is logged in.
     * 
     * @return bool True if user is logged-in
     */
    private function isUserLoggedIn(): bool
    {
        /** @var User */
        $user = $this->grav['user'];

        return isset($user->email);
    }
}
