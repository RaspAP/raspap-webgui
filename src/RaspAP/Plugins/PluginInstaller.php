<?php

/**
 * Plugin Installer class
 *
 * @description Class to handle installation of user plugins
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Plugins;

class PluginInstaller
{
    private static $instance = null;
    
    public function __construct()
    {
        $this->pluginPath = 'plugins';
    }

    // Returns a single instance of PluginInstaller
    public static function getInstance(): PluginInstaller
    {
        if (self::$instance === null) {
            self::$instance = new PluginInstaller();
        }
        return self::$instance;
    }

    /**
     * Decodes a plugin's associated manifest JSON.
     * Returns an array of key-value pairs
     *
     * @param string $url
     * @return array $json
     */
    public function getPluginManifest(string $url): ?array
    {
        $options = [
            'http' => [
                'method' => 'GET',
                'follow_location' => 1,
            ],
        ];

        $context = stream_context_create($options);
        $content = file_get_contents($url, false, $context);

        if ($content === false) {
            return null;
        }
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return $json;
    }

    /** Returns an array of installed plugins in pluginPath
     *
     * @return array $plugins
     */ 
    public function getPlugins(): array
    {
        $plugins = [];
        if (file_exists($this->pluginPath)) {
            $directories = scandir($this->pluginPath);

            foreach ($directories as $directory) {
                $pluginClass = "RaspAP\\Plugins\\$directory\\$directory";
                $pluginFile = $this->pluginPath . "/$directory/$directory.php";

                if (file_exists($pluginFile) && class_exists($pluginClass)) {
                    $plugins[] = $pluginClass;
                }
            }
        }
        return $plugins;
    }

}

