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
    private $pluginName;
    private $manifestRaw;
    private $tempSudoers;
    private $destSudoers;
    private $refModules;
    private $rootPath;

    public function __construct()
    {
        $this->pluginPath = 'plugins';
        $this->manifestRaw = '/blob/master/manifest.json?raw=true';
        $this->tempSudoers = '/tmp/090_';
        $this->destSudoers = '/etc/sudoers.d/';
        $this->refModules = '/refs/heads/master/.gitmodules';
        $this->rootPath = $_SERVER['DOCUMENT_ROOT'];
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
                $manifestUrl = $submodule['url'] .$this->manifestRaw;
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
        } catch (\Exception $e) {
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
        $gitmodulesUrl = $repoUrl .$this->refModules;
        $gitmodulesContent = file_get_contents($gitmodulesUrl);

        if ($gitmodulesContent === false) {
            throw new \Exception('Unable to fetch .gitmodules file from the repository');
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
     * Returns an array of installed plugins in pluginPath
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

    /**
     * Retrieves a plugin archive and performs install actions defined in the manifest
     *
     * @param string $archiveUrl
     * @return boolean
     */
    public function installPlugin($archiveUrl): bool
    {
        try {
            list($tempFile, $extractDir, $pluginDir) = $this->getPluginArchive($archiveUrl);

            $manifest = $this->parseManifest($pluginDir);
            $this->pluginName = preg_replace('/\s+/', '', $manifest['name']);
            $rollbackStack = []; // store actions to rollback on failure

            try {
                if (!empty($manifest['sudoers'])) {
                    $this->addSudoers($manifest['sudoers']);
                    $rollbackStack[] = 'removeSudoers';
                }
                if (!empty($manifest['dependencies'])) {
                    $this->installDependencies($manifest['dependencies']);
                    $rollbackStack[] = 'uninstallDependencies';
                }
                if (!empty($manifest['user_nonprivileged'])) {
                    $this->createUser($manifest['user_nonprivileged']);
                    $rollbackStack[] = 'deleteUser';
                }
                if (!empty($manifest['configuration'])) {
                    $this->copyConfigFiles($manifest['configuration'], $pluginDir);
                    $rollbackStack[] = 'removeConfigFiles';
                }
                $this->copyPluginFiles($pluginDir, $this->rootPath);
                $rollbackStack[] = 'removePluginFiles';

                return true;

            } catch (\Exception $e) {
                //$this->rollback($rollbackStack, $manifest, $pluginDir);
                error_log('Plugin installation failed: ' . $e->getMessage());
                return false;
            }

        } catch (\Exception $e) {
            throw new \Exception('error: ' .$e->getMessage());
        } finally {
            // cleanup tmp files
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            if (is_dir($extractDir)) {
                $this->deleteDir($extractDir);
            }
        }
    }

    /**
     * Adds sudoers entries to a temp file and copies to /etc/sudoers.d/
     *
     * @param array $sudoers
     */
    private function addSudoers(array $sudoers): void
    {
        $tmpSudoers = $this->tempSudoers . $this->pluginName;
        $destination = $this->destSudoers;
        $content = implode("\n", $sudoers);

        if (file_put_contents($tmpSudoers, $content) === false) {
            throw new \Exception('Failed to update sudoers file.');
        }

        $cmd = sprintf('sudo visudo -cf %s', escapeshellarg($tmpSudoers));
        $return = shell_exec($cmd);
        if (strpos(strtolower($return), 'parsed ok') !== false) {
            $cmd = sprintf('sudo /etc/raspap/plugins/plugin_helper.sh sudoers %s', escapeshellarg($tmpSudoers));
            $return = shell_exec($cmd);
            if (strpos(strtolower($return), 'ok') === false) {
                throw new \Exception('Plugin helper failed to install sudoers.');
            }
        } else {
            throw new \Exception('Sudoers check failed.');
        }
    }

    /**
     * Installs plugin dependencies from the aptitude package repository
     *
     * @param array $dependencies
     */
    private function installDependencies(array $dependencies): void
    {
        $packages = array_keys($dependencies);
        $packageList = implode(' ', $packages);

        $cmd = sprintf('sudo /etc/raspap/plugins/plugin_helper.sh packages %s', escapeshellarg($packageList));
        $return = shell_exec($cmd);
        if (strpos(strtolower($return), 'ok') === false) {
            throw new \Exception('Plugin helper failed to install depedencies.');
        }
    }

    /**
     * Creates a non-priviledged Linux user
     *
     * @param array $user
     */
    private function createUser(array $user): void
    {
        if (empty($user['name']) || empty($user['pass'])) {
            throw new \InvalidArgumentException('User name or password is missing.');
        }
        $username = escapeshellarg($user['name']);
        $password = escapeshellarg($user['pass']);

        $cmd = sprintf('sudo /etc/raspap/plugins/plugin_helper.sh user %s %s', $username, $password);
        $return = shell_exec($cmd);
        if (strpos(strtolower($return), 'ok') === false) {
            throw new \Exception('Plugin helper failed to create user: ' . $user['name']);
        }
    }

    /**
     * Copies plugin configuration files to their destination
     *
     * @param array $configurations
     * @param string $pluginDir
     */
    private function copyConfigFiles(array $configurations, string $pluginDir): void
    {
        foreach ($configurations as $config) {
            $source = escapeshellarg($pluginDir . DIRECTORY_SEPARATOR . $config['source']);
            $destination = escapeshellarg($config['destination']);
            $cmd = sprintf('sudo /etc/raspap/plugins/plugin_helper.sh config %s %s', $source, $destination);
            $return = shell_exec($cmd);
            if (strpos(strtolower($return), 'ok') === false) {
                throw new \Exception("Failed to copy configuration file: $source to $destination");
            }
        }
    }

    /**
     * Copies an extracted plugin directory from /tmp to /plugins
     *
     * @param string $source
     * @param string $destination
     */
    private function copyPluginFiles(string $source, string $destination): void
    {
        $source = escapeshellarg($source);
        $destination = escapeshellarg($destination . DIRECTORY_SEPARATOR .$this->pluginPath . DIRECTORY_SEPARATOR . $this->pluginName);
        $cmd = sprintf('sudo /etc/raspap/plugins/plugin_helper.sh plugin %s %s', $source, $destination);
        $return = shell_exec($cmd);
        if (strpos(strtolower($return), 'ok') === false) {
            throw new \Exception('Failed to copy plugin files to: ' . $destination);
        }
    }

    /**
     * Parses and returns a downloaded plugin manifest
     *
     * @param string $pluginDir
     * @return array json
     */
    private function parseManifest($pluginDir): array
    {
        $manifestPath = $pluginDir . DIRECTORY_SEPARATOR . 'manifest.json';
        if (!file_exists($manifestPath)) {
            throw new \Exception('manifest.json file not found.');
        }
        $json = file_get_contents($manifestPath);
        $manifest = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse manifest.json: ' . json_last_error_msg());
        }
        return $manifest;
    }

    /**
     * Retrieves a plugin archive and extracts it to /tmp
     *
     * @param string $archiveUrl
     * @return array
     */
    private function getPluginArchive(string $archiveUrl): array
    {
        try {

            $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('plugin_', true) . '.zip';
            $extractDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('plugin_', true);
            $data = file_get_contents($archiveUrl);

            if ($data === false) {
                throw new \Exception('Failed to download archive.');
            }

            file_put_contents($tempFile, $data);

            if (!mkdir($extractDir) && !is_dir($extractDir)) {
                throw new \Exception('Failed to create temp directory.');
            }

            $cmd = escapeshellcmd("unzip -o $tempFile -d $extractDir");
            $output = shell_exec($cmd);
            if ($output === null) {
                throw new \Exception('Failed to extract archive.');
            }

            $extractedDirs = glob($extractDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
            if (empty($extractedDirs)) {
                throw new \Exception('No directories found in archive.');
            }
            $pluginDir = $extractedDirs[0];

            return [$tempFile, $extractDir, $pluginDir];

        } catch (\Exception $e) {
            throw new \Exception('Error occurred: ' .$e->getMessage());
        }
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $itemPath = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($itemPath) ? $this->deleteDir($itemPath) : unlink($itemPath);
        }
        rmdir($dir);
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
}


