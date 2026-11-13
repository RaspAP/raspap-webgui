<?php
/**
 * Tests for uninstalling a user plugin with the PluginInstaller
 *
 * Usage: php tests/plugins/run_tests.php
 */

define('RASPI_CONFIG', '/etc/raspap');

require_once __DIR__ . '/../../src/RaspAP/Plugins/PluginInstaller.php';

use RaspAP\Plugins\PluginInstaller;

$failures = 0;

function check($name, $condition, $detail = '')
{
    global $failures;
    if ($condition) {
        echo "  ok    $name\n";
    } else {
        echo "  FAIL  $name" . ($detail !== '' ? " ($detail)" : '') . "\n";
        $failures++;
    }
}

// records the sudoers files that would be removed with the plugin helper
class TestPluginInstaller extends PluginInstaller
{
    public $removedSudoers = [];
    public $failSudoers = false;

    protected function removeSudoers(string $pluginName): void
    {
        if ($this->failSudoers) {
            throw new \Exception('Plugin helper failed to remove sudoers');
        }
        $this->removedSudoers[] = $pluginName;
    }
}

function uninstall(TestPluginInstaller $installer, $namespace)
{
    try {
        return $installer->uninstallPlugin($namespace);
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function deleteTree($path)
{
    if (is_link($path) || is_file($path)) {
        unlink($path);
    } elseif (is_dir($path)) {
        foreach (array_diff(scandir($path), ['.', '..']) as $item) {
            deleteTree("$path/$item");
        }
        rmdir($path);
    }
}

// a RaspAP root with an installed copied plugin, an installed symlinked
// plugin, an installed plugin without sudoers and an available plugin
$tmp = sys_get_temp_dir() . '/raspap_plugins_test_' . getmypid();
$outside = "$tmp/outside";
mkdir("$tmp/root/plugins/DynDNS/templates", 0777, true);
mkdir("$tmp/root/plugins/Notes", 0777, true);
mkdir("$tmp/root/plugins-available/Tailscale", 0777, true);
mkdir("$tmp/root/app/js/plugins", 0777, true);
mkdir($outside);

file_put_contents("$tmp/root/plugins/manifest.json", json_encode(['plugins' => [
    [
        'name' => 'DynDNS',
        'namespace' => 'RaspAP\\Plugins\\DynDNS',
        'install_path' => 'plugins',
        'javascript' => ['source' => 'templates/app/js/DynDNS.js'],
        'sudoers' => ['www-data ALL=(ALL) NOPASSWD:/bin/systemctl start ddclient.service'],
    ],
    [
        'name' => 'Tailscale VPN',
        'namespace' => 'RaspAP\\Plugins\\Tailscale',
        'install_path' => 'plugins-available',
        'sudoers' => ['www-data ALL=(ALL) NOPASSWD:/usr/bin/tailscale *'],
    ],
    [
        'name' => 'Notes',
        'namespace' => 'RaspAP\\Plugins\\Notes',
        'install_path' => 'plugins',
    ],
    [
        'name' => 'NTP Server',
        'namespace' => 'RaspAP\\Plugins\\NTPServer',
        'install_path' => 'plugins',
        'sudoers' => ['www-data ALL=(ALL) NOPASSWD:/usr/bin/ntpq *'],
    ],
]]));

foreach (['DynDNS', 'Notes'] as $plugin) {
    file_put_contents("$tmp/root/plugins/$plugin/$plugin.php",
        "<?php namespace RaspAP\\Plugins\\$plugin; class $plugin {}\n");
}
file_put_contents("$tmp/root/plugins/DynDNS/templates/main.php", "<div></div>\n");
file_put_contents("$tmp/root/plugins-available/Tailscale/Tailscale.php",
    "<?php namespace RaspAP\\Plugins\\Tailscale; class Tailscale {}\n");
symlink("$tmp/root/plugins-available/Tailscale", "$tmp/root/plugins/Tailscale");
file_put_contents("$tmp/root/app/js/plugins/DynDNS.js", "// DynDNS\n");
file_put_contents("$tmp/root/app/js/plugins/Firewall.js", "// Firewall\n");
file_put_contents("$outside/keep.txt", "keep\n");
symlink($outside, "$tmp/root/plugins/DynDNS/templates/outside");

// the PluginManager loads the installed plugins, and getPlugins() reads
// the plugins directory relative to the web root
foreach (['DynDNS', 'Notes', 'Tailscale'] as $plugin) {
    require_once "$tmp/root/plugins/$plugin/$plugin.php";
}
chdir("$tmp/root");

$installer = new TestPluginInstaller("$tmp/root");

echo "getUserPlugins()\n";

$installed = [];
foreach ($installer->getUserPlugins() as $plugin) {
    $installed[$plugin['manifest']['name']] = $plugin['installed'];
}
check('lists every plugin of the manifest', count($installed) === 4);
check('reports the installed plugins',
    $installed['DynDNS'] && $installed['Tailscale VPN'] && $installed['Notes']
    && !$installed['NTP Server']);

echo "uninstallPlugin()\n";

$installer->failSudoers = true;
$result = uninstall($installer, 'RaspAP\\Plugins\\DynDNS');
check('reports a failure of the plugin helper',
    $result === 'Plugin helper failed to remove sudoers', var_export($result, true));
check('keeps the plugin files when the sudoers file is not removed',
    file_exists("$tmp/root/plugins/DynDNS/DynDNS.php") && file_exists("$tmp/root/app/js/plugins/DynDNS.js"));
$installer->failSudoers = false;

$result = uninstall($installer, 'RaspAP\\Plugins\\DynDNS');
check('uninstalls a plugin copied to plugins/', $result === true, var_export($result, true));
check('removes the plugin directory', !file_exists("$tmp/root/plugins/DynDNS"));
check('removes the plugin JavaScript file', !file_exists("$tmp/root/app/js/plugins/DynDNS.js"));
check('keeps the JavaScript files of other plugins', file_exists("$tmp/root/app/js/plugins/Firewall.js"));
check('does not follow a symlink in the plugin directory', file_exists("$outside/keep.txt"));
check('removes the plugin sudoers file', $installer->removedSudoers === ['DynDNS'],
    implode(',', $installer->removedSudoers));

$installer->removedSudoers = [];
$result = uninstall($installer, 'RaspAP\\Plugins\\Tailscale');
check('uninstalls a plugin linked from plugins-available', $result === true, var_export($result, true));
check('removes the symlink in plugins/',
    !file_exists("$tmp/root/plugins/Tailscale") && !is_link("$tmp/root/plugins/Tailscale"));
check('keeps the plugin in plugins-available', file_exists("$tmp/root/plugins-available/Tailscale/Tailscale.php"));
check('removes the sudoers file named like on install', $installer->removedSudoers === ['TailscaleVPN'],
    implode(',', $installer->removedSudoers));

$installer->removedSudoers = [];
$result = uninstall($installer, 'RaspAP\\Plugins\\Notes');
check('uninstalls a plugin without sudoers', $result === true, var_export($result, true));
check('does not call the plugin helper without sudoers', $installer->removedSudoers === []);

$result = uninstall($installer, 'RaspAP\\Plugins\\NTPServer');
check('refuses a plugin that is not installed',
    $result === "Plugin 'NTPServer' is not installed", var_export($result, true));
check('does not remove sudoers of a plugin that is not installed', $installer->removedSudoers === []);

$result = uninstall($installer, 'RaspAP\\Plugins\\Unknown');
check('refuses a plugin that is not in the manifest',
    $result === "Plugin 'RaspAP\\Plugins\\Unknown' not found in the plugins manifest", var_export($result, true));

$installed = [];
foreach ($installer->getUserPlugins() as $plugin) {
    $installed[$plugin['manifest']['name']] = $plugin['installed'];
}
check('reports no installed plugins afterwards', !in_array(true, $installed, true));

chdir(__DIR__);
deleteTree($tmp);

echo $failures === 0 ? "\nAll tests passed\n" : "\n$failures test(s) failed\n";
exit($failures === 0 ? 0 : 1);
