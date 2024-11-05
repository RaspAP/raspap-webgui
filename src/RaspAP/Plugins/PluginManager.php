<?php

/**
 * Plugin Manager class
 *
 * @description Architecture to support user plugins for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Plugins;

use RaspAP\UI\Sidebar;

class PluginManager {
    private static $instance = null;
    private $plugins = [];
    private $sidebar;

    private function __construct() {
        $this->pluginPath = 'plugins';
        $this->sidebar = new Sidebar();
        $this->autoloadPlugins(); // autoload plugins on instantiation
    }

    // Get the single instance of PluginManager
    public static function getInstance(): PluginManager {
        if (self::$instance === null) {
            self::$instance = new PluginManager();
        }
        return self::$instance;
    }

    // Autoload plugins found in pluginPath
    private function autoloadPlugins(): void {
        if (!is_dir($this->pluginPath)) {
            return;
        }
        $directories = array_filter(glob($this->pluginPath . '/*'), 'is_dir');
        foreach ($directories as $dir) {
            $pluginName = basename($dir);
            $pluginFile = "$dir/$pluginName.php";

            if (file_exists($pluginFile)) {
                require_once $pluginFile;
                $className = "RaspAP\\Plugins\\$pluginName\\$pluginName"; // Fully qualified class name
                if (class_exists($className)) {
                    $plugin = new $className();
                    $this->registerPlugin($plugin);
                }
            }
        }
    }

    // Registers a plugin by its interface implementation 
    private function registerPlugin(PluginInterface $plugin) {
        $plugin->initialize($this->sidebar); // pass sidebar to initialize method 
        $this->plugins[] = $plugin; // store the plugin instance
    }

    /**
     * Renders a template from inside a plugin directory
     * @param string $pluginName
     * @param string $templateName
     * @param array $__data
     */
    public function renderTemplate(string $pluginName, string $templateName, array $__data = []): string {
        // Construct the file path for the template
        $templateFile = "{$this->pluginPath}/{$pluginName}/templates/{$templateName}.php";

        if (!file_exists($templateFile)) {
            return "Template file {$templateFile} not found.";
        }

        // Extract the data for use in the template
        if (!empty($__data)) {
            extract($__data);
        }

        // Start output buffering to capture the template output
        ob_start();
        include $templateFile;
        return ob_get_clean(); // return the output
    }

    // Returns the sidebar
    public function getSidebar(): Sidebar {
        return $this->sidebar;
    }

    // Forwards the page to the responsible plugin
    public function handlePageAction(string $page): void {
        foreach ($this->getInstalledPlugins() as $plugin) {
            if (str_starts_with($page, "/plugin__" . $plugin . "__")) {
                require_once($this->pluginPath . "/" . $plugin . "/functions.php");
                $function = '\\' . $plugin . '\\pluginHandlePageAction';
                $function($page);
            }
        }
    }

    // Returns all installed plugins
    public function getInstalledPlugins(): array {
        $plugins = [];
        if (file_exists($this->pluginPath)) {
            $files = scandir($this->pluginPath);
            foreach ($files as $file) {
                if ($file === "." || $file === "..") continue;
                $filePath = $this->pluginPath . '/' . $file;
                if (is_dir($filePath)) {
                    $plugins[] = $file;
                }
            }
        }
        return $plugins;
    }
}

