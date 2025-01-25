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
    private $pluginsManifest;

    public function __construct()
    {
        $this->pluginPath = 'plugins';
        $this->manifestRaw = '/blob/master/manifest.json?raw=true';
        $this->tempSudoers = '/tmp/090_';
        $this->destSudoers = '/etc/sudoers.d/';
        $this->refModules = '/refs/heads/master/.gitmodules';
        $this->rootPath = $_SERVER['DOCUMENT_ROOT'];
        $this->pluginsManifest = '/plugins/manifest.json';
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
     * Returns availble user plugin details from a manifest.json file
     *
     * @return array $plugins
     */
    public function getUserPlugins()
    {
        try {
            $manifestPath = $this->rootPath . $this->pluginsManifest;
            if (!file_exists($manifestPath)) {
                throw new \Exception("Manifest file not found at " . $manifestPath);
            }

            // decode manifest file contents
            $manifestContents = file_get_contents($manifestPath);
            $manifestData = json_decode($manifestContents, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Error parsing manifest.json: " . json_last_error_msg());
            }
            // fetch installed plugins
            $installedPlugins = $this->getPlugins();

            $plugins = [];
            foreach ($manifestData as $pluginManifest) {
                $installed = false;

                // Check if the plugin is installed
                foreach ($installedPlugins as $plugin) {
                    if (str_contains($plugin, $pluginManifest['namespace'])) {
                        $installed = true;
                        break;
                    }
                }
                $plugins[] = [
                    'manifest' => $pluginManifest,
                    'installed' => $installed
                ];
            }
            return $plugins;
        } catch (\Exception $e) {
            echo "An error occurred: " . $e->getMessage();
            return [];
        }
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
        $html .= '</tr></thead><tbody>';

        foreach ($plugins as $plugin) {

            $manifestData = $plugin['manifest'][0] ?? []; // Access the first manifest entry or default to an empty array

            $manifest = htmlspecialchars(json_encode($manifestData), ENT_QUOTES, 'UTF-8');
            $installed = $plugin['installed'];
            if ($installed === true) {
                $button = '<button type="button" class="btn btn-outline btn-primary btn-sm text-nowrap"
                    name="plugin-details" data-bs-toggle="modal" data-bs-target="#install-user-plugin"
                    data-plugin-manifest="' .$manifest. '" data-plugin-installed="' .$installed. '"> ' . _("Installed") .'</button>';
            } else {
                $button = '<button type="button" class="btn btn-outline btn-primary btn-sm text-nowrap"
                    name="install-plugin" data-bs-toggle="modal" data-bs-target="#install-user-plugin"
                    data-plugin-manifest="' .$manifest. '"> ' . _("Details") .'</button>';
            }

            $icon = htmlspecialchars($manifestData['icon'] ?? '');
            $pluginUri = htmlspecialchars($manifestData['plugin_uri'] ?? '');
            $nameText = htmlspecialchars($manifestData['name'] ?? 'Unknown Plugin');

            $name = '<i class="' . $icon . ' link-secondary me-2"></i><a href="'
                . $pluginUri
                . '" target="_blank">'
                . $nameText. '</a>';

            $version = htmlspecialchars($manifestData['version'] ?? 'N/A');
            $description = htmlspecialchars($manifestData['description'] ?? 'No description available');

            $html .= '<tr><td>' .$name. '</td>';
            $html .= '<td>' .$version. '</td>';
            $html .= '<td>' .$description. '</td>';
            $html .= '<td>' .$button. '</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }
}


