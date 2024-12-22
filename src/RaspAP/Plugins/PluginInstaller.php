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
     * Returns user plugin details from associated manifest.json files
     *
     * @return array $plugins
     */
    public function getUserPlugins()
    {
        $installedPlugins = $this->getPlugins();

        try {
            $submodules = $this->getSubmodules(RASPI_PLUGINS_URL);
            $plugins = [];
            foreach ($submodules as $submodule) {
                $manifestUrl = $submodule['url'] .'/blob/master/manifest.json?raw=true';
                $manifest = $this->getPluginManifest($manifestUrl);

                if ($manifest) {
                    $installed = false;

                    foreach ($installedPlugins as $plugin) {
                        if (str_contains($plugin, $plugins['manifest']['namespace'])) {
                            $installed = true;
                            break;
                        }
                    }

                    $plugins[] = [
                        'manifest' => $manifest,
                        'installed' => $installed
                    ];
                }
            }
            return $plugins;
        } catch (Exception $e) {
            echo "An error occured: " .$e->getMessage();
        }
    }

    /**
     * Retrieves a plugin's associated manifest JSON
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

    /**
     * Returns git submodules for the specified repository
     *
     * @param string $repoURL
     * @return array $submodules
     */
    public function getSubmodules(string $repoUrl): array
    {
        $gitmodulesUrl = $repoUrl . '/refs/heads/master/.gitmodules';
        $gitmodulesContent = file_get_contents($gitmodulesUrl);

        if ($gitmodulesContent === false) {
            throw new Exception('Unable to fetch .gitmodules file from the repository');
        }

        $submodules = [];
        $lines = explode("\n", $gitmodulesContent);
        $currentSubmodule = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (strpos($line, '[submodule "') === 0) {
                if (!empty($currentSubmodule)) {
                    $submodules[] = $currentSubmodule;
                }
                $currentSubmodule = [];
            } elseif (strpos($line, 'path = ') === 0) {
                $currentSubmodule['path'] = substr($line, strlen('path = '));
            } elseif (strpos($line, 'url = ') === 0) {
                $currentSubmodule['url'] = substr($line, strlen('url = '));
            }
        }

        if (!empty($currentSubmodule)) {
            $submodules[] = $currentSubmodule;
        }

        return $submodules;
    }

    /**
     * Returns a list of available plugins formatted as an HTML table
     *
     * @param array $plugins
     * @return string $html
     */
    public function getHTMLPluginsTable(array $plugins): string
    {
        $html = '<table class="table table-striped table-hover">';
        $html .= '<thead><tr>';
        $html .= '<th scope="col">Name</th>';
        $html .= '<th scope="col">Version</th>';
        $html .= '<th scope="col">Description</th>';
        $html .= '<th scope="col"></th>';
        $html .= '</tr></thead></tbody>';

        foreach ($plugins as $plugin) {

            $manifest = htmlspecialchars(json_encode($plugin['manifest']), ENT_QUOTES, 'UTF-8');
            $installed = $plugin['installed'];
            if ($installed === true ) {
                $status = 'Installed';
            } else {
                $button = '<button type="button" class="btn btn-outline btn-primary btn-sm text-nowrap"
                    name="install-plugin" data-bs-toggle="modal" data-bs-target="#install-user-plugin"
                    data-plugin-manifest="' .$manifest. '"> ' . _("Details") .'</button>';
            }
            $name = '<i class="' . htmlspecialchars($plugin['manifest']['icon']) .' link-secondary me-2"></i><a href="'
                . htmlspecialchars($plugin['manifest']['plugin_uri'])
                . '" target="_blank">'
                . htmlspecialchars($plugin['manifest']['name']). '</a>';
            $html .= '<tr><td>' .$name. '</td>';
            $html .= '<td>' .htmlspecialchars($plugin['manifest']['version']). '</td>';
            $html .= '<td>' .htmlspecialchars($plugin['manifest']['description']). '</td>';
            $html .= '<td>' .$button. '</td>';
        }
        $html .= '</tbody></table>';
        return $html;
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

