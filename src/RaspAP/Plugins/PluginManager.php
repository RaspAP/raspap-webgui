<?php

/**
 * Plugin Manager class
 *
 * @description Class supporting user plugins to extend RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 *              Special thanks to GitHub user @assachs
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Plugins;

use RaspAP\UI\Sidebar;

class PluginManager
{
    private static $instance = null;
    private $plugins = [];
    private $sidebar;

    private function __construct()
    {
        $this->pluginPath = 'plugins';
        $this->sidebar = new Sidebar();
        $this->autoloadPlugins(); // autoload plugins on instantiation
    }

    // Get the single instance of PluginManager
    public static function getInstance(): PluginManager
    {
        if (self::$instance === null) {
            self::$instance = new PluginManager();
        }
        return self::$instance;
    }

    // Autoload plugins found in pluginPath
    private function autoloadPlugins(): void
    {
        if (!is_dir($this->pluginPath)) {
            return;
        }
        $directories = array_filter(glob($this->pluginPath . '/*'), 'is_dir');
        foreach ($directories as $dir) {
            $pluginName = basename($dir);
            $pluginFile = "$dir/$pluginName.php";
            $pluginClass = "RaspAP\\Plugins\\$pluginName\\$pluginName"; // fully qualified class name

            if (file_exists($pluginFile)) {
                require_once $pluginFile;
                if (class_exists($pluginClass)) {
                    $plugin = new $pluginClass($this->pluginPath, $pluginName);
                    $this->registerPlugin($plugin);
                }
            }
        }
    }

    // Registers a plugin by its interface implementation 
    private function registerPlugin(PluginInterface $plugin)
    {
        $plugin->initialize($this->sidebar); // pass sidebar to initialize method 
        $this->plugins[] = $plugin; // store the plugin instance
    }

    // Returns the sidebar
    public function getSidebar(): Sidebar
    {
        return $this->sidebar;
    }

    /**
     * Iterates over registered plugins and calls its associated method
     * @param string $page
     */
    public function handlePageAction(?string $page): bool
    {
        $page = $page ?? '';

        foreach ($this->getInstalledPlugins() as $pluginClass) {
            $plugin = new $pluginClass($this->pluginPath, $pluginClass);

            if ($plugin instanceof PluginInterface) {
                // Check if this plugin can handle the page action
                if ($plugin->handlePageAction($page)) {
                    return true;
                }
            }
        }
        return false; // no plugins handled the page
    }

    // Returns all installed plugins with full class names
    public function getInstalledPlugins(): array
    {
        $plugins = [];
        if (file_exists($this->pluginPath)) {
            $directories = scandir($this->pluginPath);

            foreach ($directories as $directory) {
                if ($directory === "." || $directory === "..") continue;

                $pluginClass = "RaspAP\\Plugins\\$directory\\$directory";
                $pluginFile = $this->pluginPath . "/$directory/$directory.php";

                // Check if the file and class exist
                if (file_exists($pluginFile) && class_exists($pluginClass)) {
                    $plugins[] = $pluginClass;
                }
            }
        }
        return $plugins;
    }

}

