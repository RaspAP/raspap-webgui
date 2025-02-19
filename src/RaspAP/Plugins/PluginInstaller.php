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
    private $repoPublic;

    public function __construct()
    {
        $this->pluginPath = 'plugins';
        $this->manifestRaw = '/blob/master/manifest.json?raw=true';
        $this->tempSudoers = '/tmp/090_';
        $this->destSudoers = '/etc/sudoers.d/';
        $this->refModules = '/refs/heads/master/.gitmodules';
        $this->rootPath = $_SERVER['DOCUMENT_ROOT'];
        $this->pluginsManifest = '/plugins/manifest.json';
        $this->repoPublic = $this->getRepository();
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
                $pluginEntries = [];

                foreach ($pluginManifest as $plugin) {
                    $installed = false;

                    if (!empty($plugin['namespace'])) {
                        foreach ($installedPlugins as $installedPlugin) {
                            if (str_contains($installedPlugin['class'], $plugin['namespace'])) {
                                $installed = true;
                                break;
                            }
                        }
                    }
                    $pluginEntries[] = [
                        'manifest' => $plugin,
                        'installed' => $installed
                    ];
                }
                $plugins[] = $pluginEntries;
            }
            return array_merge(...$plugins);
        } catch (\Exception $e) {
            error_log("An error occurred: " . $e->getMessage());
            throw $e; // re-throw to global ExceptionHandler
        }
    }

    /**
     * Returns an array of installed plugins in pluginPath
     *
     * @param string|null $path; optional path to search for plugins. Defaults to $this->pluginPath.
     * @return array $plugins
     */
    public function getPlugins(?string $path = null): array
    {
        $plugins = [];
        $pluginPath = $path ?? $this->pluginPath;

        if (file_exists($pluginPath)) {
            $directories = scandir($pluginPath);

            foreach ($directories as $directory) {
                if ($directory === '.' || $directory === '..') {
                    continue;
                }
                $pluginClass = "RaspAP\\Plugins\\$directory\\$directory";
                $pluginFile = "$pluginPath/$directory/$directory.php";

                if (file_exists($pluginFile)) {
                    if ($path === 'plugins-available') {
                        require_once $pluginFile;
                    }
                    if (class_exists($pluginClass)) {
                        $plugins[] = [
                            'class' => $pluginClass,
                            'installPath' => $pluginPath
                        ];
                    }
                }
            }
        }
        return $plugins;
    }

    /**
     * Installs a plugin by either extracting an archive or creating a symlink,
     * then performs required actions as defined in the plugin manifest
     *
     * @param string $pluginUri
     * @param string $pluginVersion
     * @param string $installPath
     * @return boolean
     * @throws \Exception
     */
    public function installPlugin(string $pluginUri, string $pluginVersion, string $installPath): bool
    {
        $tempFile = null;
        $extractDir = null;
        $pluginDir = null;

        try {
            if ($installPath === 'plugins-available') {
                // extract plugin name from URI
                $pluginName = basename($pluginUri);
                $sourcePath = $this->rootPath . '/plugins-available/' . $pluginName;
                $targetPath = $this->rootPath . '/plugins/' . $pluginName;

                if (!is_dir($sourcePath)) {
                    throw new \Exception("Plugin '$pluginName' not found in plugins-available");
                }

                // ensure target does not already exist
                if (file_exists($targetPath)) {
                    throw new \Exception("Plugin '$pluginName' is already installed.");
                }

                // create symlink
                if (!symlink($sourcePath, $targetPath)) {
                    throw new \Exception("Failed to symlink '$pluginName' to plugins/");
                }
                $pluginDir = $targetPath;
            } else {
                // fetch and extract the plugin archive
                $archiveUrl = rtrim($pluginUri, '/') . '/archive/refs/tags/' .$pluginVersion.'.zip';
                list($tempFile, $extractDir, $pluginDir) = $this->getPluginArchive($archiveUrl);
            }

            $manifest = $this->parseManifest($pluginDir);
            $this->pluginName = preg_replace('/\s+/', '', $manifest['name']);
            $rollbackStack = []; // Store actions to rollback on failure

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
                if (!empty($manifest['javascript'])) {
                    $this->copyJavaScriptFiles($manifest['javascript'], $pluginDir);
                    $rollbackStack[] = 'removeJavaScript';
                }

                return true;
            } catch (\Exception $e) {
                throw new \Exception('Installation step failed: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            error_log('Plugin installation failed: ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        } finally {
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            if (isset($extractDir) && is_dir($extractDir)) {
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
     * Copies plugin JavaScript files to their destination
     *
     * @param array $javascript
     * @param string $pluginDir
     */
    private function copyJavaScriptFiles(array $javascript, string $pluginDir): void
    {
        foreach ($javascript as $js) {
            $source = escapeshellarg($pluginDir . DIRECTORY_SEPARATOR . $js);
            $destination = escapeshellarg($this->rootPath . DIRECTORY_SEPARATOR . 'app/js/plugins/');
            $cmd = sprintf('sudo /etc/raspap/plugins/plugin_helper.sh javascript %s %s', $source, $destination);
            $return = shell_exec($cmd);
            if (strpos(strtolower($return), 'ok') === false) {
                throw new \Exception("Failed to copy JavaScript file: $source");
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
     * @throws \Exception
     */
    private function getPluginArchive(string $archiveUrl): array
    {
        $tempFile = '';
        $extractDir = '';

        try {
            $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('plugin_', true) . '.zip';
            $extractDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('plugin_', true);
            $data = @file_get_contents($archiveUrl); // suppress PHP warnings for better exception handling

            if ($data === false) {
                $error = error_get_last();
                throw new \Exception('Failed to download archive: ' . ($error['message'] ?? 'Unknown error'));
            }

            file_put_contents($tempFile, $data);

            if (!mkdir($extractDir) && !is_dir($extractDir)) {
                throw new \Exception('Failed to create temp directory.');
            }

            $cmd = escapeshellcmd("unzip -o $tempFile -d $extractDir");
            $output = shell_exec($cmd);
            if ($output === null) {
                throw new \Exception('Failed to extract plugin archive.');
            }

            $extractedDirs = glob($extractDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
            if (empty($extractedDirs)) {
                throw new \Exception('No directories found in plugin archive.');
            }

            $pluginDir = $extractedDirs[0];

            return [$tempFile, $extractDir, $pluginDir];
        } catch (\Exception $e) {
            if (!empty($tempFile) && file_exists($tempFile)) {
            unlink($tempFile);
            }
            if (!empty($extractDir) && is_dir($extractDir)) {
                rmdir($extractDir);
            }
            throw new \Exception('Error occurred during plugin archive retrieval: ' . $e->getMessage());
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
            $manifestData = $plugin['manifest'] ?? [];
            $installed = $plugin['installed'] ?? false;
            $manifest = htmlspecialchars(json_encode($manifestData), ENT_QUOTES, 'UTF-8');

            if ($installed === true) {
                $button = '<button type="button" class="btn btn-outline btn-primary btn-sm text-nowrap"
                    name="plugin-details" data-bs-toggle="modal" data-bs-target="#install-user-plugin"
                    data-plugin-manifest="' .$manifest. '" data-plugin-installed="' .$installed. '">' . _("Installed") .'</button>';
            } elseif (!RASPI_MONITOR_ENABLED) {
                $button = '<button type="button" class="btn btn-outline btn-primary btn-sm text-nowrap"
                    name="install-plugin" data-bs-toggle="modal" data-bs-target="#install-user-plugin"
                    data-plugin-manifest="' .$manifest. '" data-repo-public="' .$this->repoPublic. '">' . _("Details") .'</button>';
            }

            $icon = htmlspecialchars($manifestData['icon'] ?? '');
            $pluginDocs = htmlspecialchars($manifestData['plugin_docs'] ?? '');
            $nameText = htmlspecialchars($manifestData['name'] ?? 'Unknown Plugin');
            $name = '<i class="' .$icon. ' link-secondary me-2"></i><a href="'
                .$pluginDocs
                .'" target="_blank">'
                .$nameText. '</a>';

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

    /**
     * Determines remote repository of installed application
     *
     * @return boolean; true if public repo
     */
    public function getRepository(): bool
    {
        $output = [];
        exec('git -C ' . escapeshellarg($this->rootPath) . ' remote -v', $output);

        foreach ($output as $line) {
            if (preg_match('#github\.com/RaspAP/(raspap-\w+)#', $line, $matches)) {
                $repo = $matches[1];
                $public = ($repo === 'raspap-webgui');
                return $public;
            }
        }
        return false;
    }
}

