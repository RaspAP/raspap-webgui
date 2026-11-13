<?php

/**
 * Captive Portal Plugin
 *
 * @description A captive plugin that implements nodogsplash
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/RaspAP/raspap-insiders/blob/master/LICENSE
 * @see         src/RaspAP/Plugins/PluginInterface.php
 * @see         src/RaspAP/UI/Sidebar.php
 */

namespace RaspAP\Plugins\Captive;

use RaspAP\Networking\Hotspot\HotspotService;
use RaspAP\Networking\Hotspot\WiFiManager;
use RaspAP\Networking\Hotspot\DhcpcdManager;
use RaspAP\Networking\DeviceScanner;
use RaspAP\System\Sysinfo;
use RaspAP\Plugins\PluginInterface;
use RaspAP\UI\Sidebar;

class Captive implements PluginInterface
{

    private string $pluginPath;
    private string $pluginName;
    private string $templateMain;
    private string $serviceStatus;
    private string $serviceName;
    private string $serviceDesc;
    private string $hostname;
    private array $interfaces;
    private array $interfaceConfig;
    private string $configFile;
    private string $binPath;
    private $options = [];

    public function __construct(string $pluginPath, string $pluginName)
    {
        $hotspot = new HotspotService();
        $dhcpcd = new DhcpcdManager();
        $system = new Sysinfo();
        $this->pluginPath = $pluginPath;
        $this->pluginName = $pluginName;
        $this->templateMain = 'main';
        $this->serviceName = 'nodogsplash.service';
        $this->hostname = $system->hostname();
        $this->interfaces = $hotspot->getInterfaces();
        $this->interfaceConfig = $dhcpcd->getInterfaceConfig($_SESSION['ap_interface']);
        $this->binPath = '/usr/bin/ndsctl';
        $this->configFile = '/etc/nodogsplash/nodogsplash.conf';

        if ($loaded = self::loadData()) {
            $this->options = $loaded->options ?? [];
        }

        $this->setServiceStatus();
     }

    /**
     * Initializes captive portal plugin and creates a custom sidebar item
     *
     * @param Sidebar $sidebar an instance of the Sidebar
     * @see src/RaspAP/UI/Sidebar.php
     * @see https://fontawesome.com/icons
     */
    public function initialize(Sidebar $sidebar): void
    {

        $label = _('Captive portal');
        $icon = 'fas fa-right-to-bracket';
        $action = 'plugin__'.$this->getName();
        $priority = 79;
        $sidebar->addItem($label, $icon, $action, $priority);
    }

    /**
     * Handles a page action by processing inputs and rendering a plugin template
     *
     * @param string $page the current page route
     */
    public function handlePageAction(string $page): bool
    {
        // Verify that this plugin should handle the page
        if (strpos($page, "/plugin__" . $this->getName()) === 0) {

            // Instantiate a StatusMessage object
            $status = new \RaspAP\Messages\StatusMessage;

            if (!RASPI_MONITOR_ENABLED) {
                
                if (!empty($_POST)) {
                    $action = $_POST['portal-action'] ?? '';
                    if ($action ===  'portal-start' ) {
                        $status->addMessage(sprintf(_('Attempting to start %s'), $this->serviceName), 'info');
                        exec('sudo /bin/systemctl start '. $this->serviceName, $return);
                        $this->setServiceStatus();
                    } elseif ($action === 'portal-stop' ) {
                        $status->addMessage(sprintf(_('Attempting to stop %s'), $this->serviceName), 'info');
                        exec('sudo /bin/systemctl stop '. $this->serviceName, $return);
                        $this->setServiceStatus();
                    } elseif ($action === 'portal-client-deauth') {
                        $clientMac = trim($_POST['client_mac'] ?? '');
                        if ($this->isValidMacAddress($clientMac)) {
                            if ($this->deauthenticateClient($clientMac)) {
                                $status->addMessage(sprintf(_('Client %s deauthenticated'), $clientMac), 'success');
                            } else {
                                $status->addMessage(sprintf(_('Failed to deauthenticate client %s'), $clientMac), 'danger');
                            }
                        } else {
                            $status->addMessage(_('Invalid client MAC address'), 'danger');
                        }
                    }
                    if (isset($_POST['save-settings']) ) {
                        $this->handleSaveSettings($_POST, $status);
                    }
                }

                $serviceLog = $this->getNdsctlStatusRaw();
                $serviceStatus = $this->getServiceStatus();
                $arrConfig = $this->options;

                // set gateway address from dhcp configuration if undefined 
                if (!isset($arrConfig['gateway_address']) && isset($this->interfaceConfig['StaticIP'])) {
                    $arrConfig['gateway_address'] = $this->interfaceConfig['StaticIP'];
                }

                // Populate template data
                $__template_data = [
                    'title' => _('Captive portal'),
                    'description' => _('A captive portal plugin that implements nodogsplash'),
                    'author' => _('Bill Z'),
                    'uri' => 'https://github.com/RaspAP/raspap-insiders',
                    'icon' => 'fas fa-right-to-bracket',
                    'serviceStatus' => $serviceStatus,
                    'serviceName' => 'portal',
                    'serviceLog' => $serviceLog,
                    'portalClients' => $this->parseClientsFromStatus($serviceLog),
                    'interfaces' => $this->interfaces,
                    'action' => 'plugin__'.$this->getName(),
                    'pluginName' => $this->getName(),
                    'arrConfig' => $arrConfig
                ];
                echo $this->renderTemplate($this->templateMain, compact(
                    "status",
                    "__template_data"
                ));
                return true;
            }
        }
        return false;
    }

    /**
     * Renders a template from inside a plugin directory
     * @param string $templateName
     * @param array $__data
     */
    public function renderTemplate(string $templateName, array $__data = []): string
    {
        $templateFile = "{$this->pluginPath}/{$this->getName()}/templates/{$templateName}.php";

        if (!file_exists($templateFile)) {
            return "Template file {$templateFile} not found.";
        }
        if (!empty($__data)) {
            extract($__data);
        }
        ob_start();
        include $templateFile;
        return ob_get_clean();
    }

    /**
     * Generate and write Nodogsplash configuration file
     *
     * @return bool success status
     * @throws Exception on write failure
     */
    private function writeConfig(): bool
    {
        $config = $this->generateConfig();

        // write to temp file
        $tempFile = '/tmp/nodogsplash.conf';
        $targetFile = $this->configFile;

        if (file_put_contents($tempFile, $config) === false) {
            throw new \Exception("Failed to write temporary config file");
        }

        $result = shell_exec("sudo cp $tempFile $targetFile 2>&1");

        if (!file_exists($targetFile)) {
            throw new \Exception("Failed to copy config to $targetFile: $result");
            error_log("Captive::writeConfig(): Failed to copy config to $targetFile: $result");
        }

        return true;
    }

    /**
     * Generate Nodogsplash configuration file content
     *
     * @return string file content
     */
    private function generateConfig(): string
    {
        $conf = [];

        $conf[] = "#";
        $conf[] = "# Nodogsplash Configuration File";
        $conf[] = "# Generated by RaspAP on " . date('Y-m-d H:i:s');
        $conf[] = "#";
        $conf[] = "";

        // gateway interface (required)
        $conf[] = "# Parameter: GatewayInterface";
        $conf[] = "# Default: NONE";
        $conf[] = "#";
        $conf[] = "# GatewayInterface is not autodetected, has no default, and must be set here.";
        $conf[] = "# Set GatewayInterface to the interface on your router";
        $conf[] = "# that is to be managed by Nodogsplash.";
        $conf[] = "# Typically br-lan for the wired and wireless lan.";
        $conf[] = "#";
        $conf[] = "GatewayInterface " . $this->options['gateway_interface'];
        $conf[] = "";

        // web root
        if (!empty($this->options['web_root']) && $this->options['web_root'] !== '/etc/nodogsplash/htdocs') {
            $conf[] = "# Parameter: WebRoot";
            $conf[] = "# Default: /etc/nodogsplash/htdocs";
            $conf[] = "#";
            $conf[] = "WebRoot " . $this->options['web_root'];
            $conf[] = "";
        }

        // gateway name
        if (!empty($this->options['gateway_name']) && $this->options['gateway_name'] !== 'NoDogSplash') {
            $conf[] = "# Parameter: GatewayName";
            $conf[] = "# Default: NoDogSplash";
            $conf[] = "#";
            $conf[] = "GatewayName " . $this->options['gateway_name'];
            $conf[] = "";
        }

        // gateway address
        if (!empty($this->options['gateway_address'])) {
            $conf[] = "# Parameter: GatewayAddress";
            $conf[] = "# Default: Discovered from GatewayInterface";
            $conf[] = "#";
            $conf[] = "GatewayAddress " . $this->options['gateway_address'];
            $conf[] = "";
        }

        // gateway port
        if (!empty($this->options['gateway_port']) && $this->options['gateway_port'] != 2050) {
            $conf[] = "# Parameter: GatewayPort";
            $conf[] = "# Default: 2050";
            $conf[] = "#";
            $conf[] = "GatewayPort " . $this->options['gateway_port'];
            $conf[] = "";
        }

        // status page
        if (!empty($this->options['status_page']) && $this->options['status_page'] !== 'status.html') {
            $conf[] = "# Parameter: StatusPage";
            $conf[] = "# Default: status.html";
            $conf[] = "#";
            $conf[] = "StatusPage " . $this->options['status_page'];
            $conf[] = "";
        }

        // splash page
        if (!empty($this->options['splash_page']) && $this->options['splash_page'] !== 'splash.html') {
            $conf[] = "# Parameter: SplashPage";
            $conf[] = "# Default: splash.html";
            $conf[] = "#";
            $conf[] = "SplashPage " . $this->options['splash_page'];
            $conf[] = "";
        }

        // redirect URL
        if (!empty($this->options['redirect_url'])) {
            $conf[] = "# Parameter: RedirectURL";
            $conf[] = "# Default: none";
            $conf[] = "#";
            $conf[] = "RedirectURL " . $this->options['redirect_url'];
            $conf[] = "";
        }

        // max clients
        if (!empty($this->options['max_clients']) && $this->options['max_clients'] != 20) {
            $conf[] = "# Parameter: MaxClients";
            $conf[] = "# Default: 20";
            $conf[] = "#";
            $conf[] = "MaxClients " . $this->options['max_clients'];
            $conf[] = "";
        }

        // session timeout
        if (isset($this->options['session_timeout']) && $this->options['session_timeout'] != 0) {
            $conf[] = "# Parameter: SessionTimeout";
            $conf[] = "# Default: 0";
            $conf[] = "#";
            $conf[] = "SessionTimeout " . $this->options['session_timeout'];
            $conf[] = "";
        }

        // preauth idle timeout
        if (!empty($this->options['preauth_idle_timeout']) && $this->options['preauth_idle_timeout'] != 10) {
            $conf[] = "# Parameter: PreAuthIdleTimeout";
            $conf[] = "# Default: 10";
            $conf[] = "#";
            $conf[] = "PreAuthIdleTimeout " . $this->options['preauth_idle_timeout'];
            $conf[] = "";
        }

        // auth idle timeout
        if (!empty($this->options['auth_idle_timeout']) && $this->options['auth_idle_timeout'] != 120) {
            $conf[] = "# Parameter: AuthIdleTimeout";
            $conf[] = "# Default: 120";
            $conf[] = "#";
            $conf[] = "AuthIdleTimeout " . $this->options['auth_idle_timeout'];
            $conf[] = "";
        }

        // check interval
        if (!empty($this->options['check_interval']) && $this->options['check_interval'] != 30) {
            $conf[] = "# Parameter: CheckInterval";
            $conf[] = "# Default: 30";
            $conf[] = "#";
            $conf[] = "CheckInterval " . $this->options['check_interval'];
            $conf[] = "";
        }

        // MAC mechanism
        if (!empty($this->options['mac_mechanism']) && $this->options['mac_mechanism'] !== 'block') {
            $conf[] = "# Parameter: MACMechanism";
            $conf[] = "# Default: block";
            $conf[] = "#";
            $conf[] = "MACMechanism " . $this->options['mac_mechanism'];
            $conf[] = "";
        }

        // blocked MAC list
        if (!empty($this->options['blocked_mac_list'])) {
            $conf[] = "# Parameter: BlockedMACList";
            $conf[] = "# Default: none";
            $conf[] = "#";
            $conf[] = "BlockedMACList " . $this->options['blocked_mac_list'];
            $conf[] = "";
        }

        // allowed MAC list
        if (!empty($this->options['allowed_mac_list'])) {
            $conf[] = "# Parameter: AllowedMACList";
            $conf[] = "# Default: none";
            $conf[] = "#";
            $conf[] = "AllowedMACList " . $this->options['allowed_mac_list'];
            $conf[] = "";
        }

        // trusted MAC list
        if (!empty($this->options['trusted_mac_list'])) {
            $conf[] = "# Parameter: TrustedMACList";
            $conf[] = "# Default: none";
            $conf[] = "#";
            $conf[] = "TrustedMACList " . $this->options['trusted_mac_list'];
            $conf[] = "";
        }

        // gateway IP range
        if (!empty($this->options['gateway_ip_range']) && $this->options['gateway_ip_range'] !== '0.0.0.0/0') {
            $conf[] = "# Parameter: GatewayIPRange";
            $conf[] = "# Default: 0.0.0.0/0";
            $conf[] = "#";
            $conf[] = "GatewayIPRange " . $this->options['gateway_ip_range'];
            $conf[] = "";
        }

        // debug level
        if (isset($this->options['debug_level']) && $this->options['debug_level'] != 1) {
            $conf[] = "# Parameter: DebugLevel";
            $conf[] = "# Default: 1";
            $conf[] = "#";
            $conf[] = "DebugLevel " . $this->options['debug_level'];
            $conf[] = "";
        }

        // binauth
        if (!empty($this->options['binauth_script'])) {
            $conf[] = "# Parameter: BinAuth";
            $conf[] = "#";
            $conf[] = "BinAuth " . $this->options['binauth_script'];
            $conf[] = "";
        }

        // firewall rulesets
        $conf[] = "# FirewallRuleSet: authenticated-users";
        $conf[] = "#";
        $conf[] = "FirewallRuleSet authenticated-users {";

        if (!empty($this->options['allow_all_authenticated'])) {
            $conf[] = "  FirewallRule allow all";
        } else {
            $conf[] = "  # Add custom rules here";
            $conf[] = "  FirewallRule allow tcp port 53";
            $conf[] = "  FirewallRule allow udp port 53";
            $conf[] = "  FirewallRule allow tcp port 80";
            $conf[] = "  FirewallRule allow tcp port 443";
        }

        $conf[] = "}";
        $conf[] = "# end FirewallRuleSet authenticated-users";
        $conf[] = "";

        // pre-authenticated users
        $conf[] = "# FirewallRuleSet: preauthenticated-users";
        $conf[] = "#";
        $conf[] = "FirewallRuleSet preauthenticated-users {";

        if (!empty($this->options['allow_preauth_dns'])) {
            $conf[] = "  FirewallRule allow tcp port 53";
            $conf[] = "  FirewallRule allow udp port 53";
        }

        $conf[] = "}";
        $conf[] = "# end FirewallRuleSet preauthenticated-users";
        $conf[] = "";

        // users to router
        $conf[] = "# FirewallRuleSet: users-to-router";
        $conf[] = "#";
        $conf[] = "FirewallRuleSet users-to-router {";
        $conf[] = "  FirewallRule allow udp port 53";
        $conf[] = "  FirewallRule allow tcp port 53";
        $conf[] = "  FirewallRule allow udp port 67";
        $conf[] = "  FirewallRule allow tcp port 22";
        $conf[] = "  FirewallRule allow tcp port 80";
        $conf[] = "  FirewallRule allow tcp port 443";
        $conf[] = "}";
        $conf[] = "# end FirewallRuleSet users-to-router";
        $conf[] = "";

        return implode(PHP_EOL, $conf);
    }

    /**
     * Handle save settings action
     *
     * @param array $post POST data
     * @param StatusMessage $status
     */
    private function handleSaveSettings(array $post, $status): void
    {
        try {
            // basic settings
            $this->options['gateway_interface'] = !empty($post['gateway_interface']) ?
                $post['gateway_interface'] : 'br-lan';

            $this->options['gateway_name'] = !empty($post['gateway_name']) ?
                trim($post['gateway_name']) : 'NoDogSplash';

            $this->options['gateway_address'] = !empty($post['gateway_address']) ?
                trim($post['gateway_address']) : '';

            $this->options['gateway_port'] = !empty($post['gateway_port']) && is_numeric($post['gateway_port']) ?
                (int)$post['gateway_port'] : 2050;

            // splash page settings (defaults, not user editable)
            $this->options['web_root'] = !empty($post['web_root']) ?
                trim($post['web_root']) : '/etc/nodogsplash/htdocs';

            $this->options['splash_page'] = !empty($post['splash_page']) ?
                trim($post['splash_page']) : 'splash.html';

            $this->options['status_page'] = !empty($post['status_page']) ?
                trim($post['status_page']) : 'status.html';

            $this->options['redirect_url'] = !empty($post['redirect_url']) ?
                trim($post['redirect_url']) : '';

            // client options
            $this->options['max_clients'] = !empty($post['max_clients']) && is_numeric($post['max_clients']) ?
                (int)$post['max_clients'] : 250;

            $this->options['session_timeout'] = !empty($post['session_timeout']) && is_numeric($post['session_timeout']) ?
                (int)$post['session_timeout'] : 0;

            $this->options['preauth_idle_timeout'] = !empty($post['preauth_idle_timeout']) && is_numeric($post['preauth_idle_timeout']) ?
                (int)$post['preauth_idle_timeout'] : 10;

            $this->options['auth_idle_timeout'] = !empty($post['auth_idle_timeout']) && is_numeric($post['auth_idle_timeout']) ?
                (int)$post['auth_idle_timeout'] : 120;

            $this->options['check_interval'] = !empty($post['check_interval']) && is_numeric($post['check_interval']) ?
                (int)$post['check_interval'] : 30;

            // MAC address control
            $this->options['mac_mechanism'] = !empty($post['mac_mechanism']) &&
                in_array($post['mac_mechanism'], ['block', 'allow']) ?
                $post['mac_mechanism'] : 'block';

            $this->options['blocked_mac_list'] = !empty($post['blocked_mac_list']) ?
                trim($post['blocked_mac_list']) : '';

            $this->options['allowed_mac_list'] = !empty($post['allowed_mac_list']) ?
                trim($post['allowed_mac_list']) : '';

            $this->options['trusted_mac_list'] = !empty($post['trusted_mac_list']) ?
                trim($post['trusted_mac_list']) : '';

            // firewall settings
            $this->options['allow_all_authenticated'] = isset($post['allow_all_authenticated']) &&
                $post['allow_all_authenticated'] == '1';

            $this->options['allow_preauth_dns'] = isset($post['allow_preauth_dns']) &&
                $post['allow_preauth_dns'] == '1';

            // advanced options
            $this->options['gateway_ip_range'] = !empty($post['gateway_ip_range']) ?
                trim($post['gateway_ip_range']) : '';

            $this->options['debug_level'] = !empty($post['debug_level']) &&
                in_array($post['debug_level'], ['0', '1', '2', '3']) ?
                $post['debug_level'] : '1';

            $this->options['binauth_script'] = !empty($post['binauth_script']) ?
                trim($post['binauth_script']) : '';

            // persist to plugin json data
            $this->persistData();

            // write configuration to disk
            $this->writeConfig();

            $status->addMessage(_('Portal settings saved successfully'), 'success');
        } catch (Exception $e) {
            error_log("Portal plugin: Error saving settings - " . $e->getMessage());
            $status->addMessage(_('Error saving settings: ') . $e->getMessage(), 'danger');
        }
    }

    /**
     * Returns a hypothetical service status
     * @return string $status
     */
    public function getServiceStatus()
    {
        return $this->serviceStatus;
    }

    // Setter for service status
    public function setServiceStatus(): void
    {
        $status = 'down'; 
        exec('systemctl status '.$this->serviceName, $output, $return);
        foreach ($output as $line) {
            if (strpos($line, 'Active: active (running)') !== false) {
                $status = 'up';
            } 
        }
        $this->serviceStatus = $status;
        $this->persistData();
    }

    /**
     * Retrieves raw ndsctl status output
     *
     * @return string $output
     */
    public function getNdsctlStatusRaw(): string
    {
        $cmd = escapeshellcmd('sudo '. $this->binPath). ' status';
        exec($cmd, $response, $exitCode);

        if ($exitCode === 0) {
            $statusRaw = implode(PHP_EOL, $response);
            return $statusRaw;
        }
        return _("Failed to execute ndsctl.");
    }

    /**
     * Parse ndsctl status output and extract active client details.
     *
     * @param string $statusRaw
     * @return array<int,array<string,string>>
     */
    private function parseClientsFromStatus(string $statusRaw): array
    {
        if (empty($statusRaw)) {
            return [];
        }

        preg_match_all('/Client\s+\d+\R(.*?)(?=\R\RClient\s+\d+|\R\R====|\z)/s', $statusRaw, $matches);
        if (empty($matches[1])) {
            return [];
        }

        $clients = [];
        foreach ($matches[1] as $block) {
            $client = [
                'ip' => $this->captureValue('/IP:\s*([^\s]+)\s+MAC:/', $block),
                'mac' => $this->captureValue('/MAC:\s*([0-9a-f:]{17})/i', $block),
                'token' => $this->captureValue('/Token:\s*([^\s]+)/', $block),
                'last_activity' => $this->captureValue('/Last Activity:\s*(.+)/', $block),
                'session_start' => $this->captureValue('/Session Start:\s*(.+)/', $block),
                'session_end' => $this->captureValue('/Session End:\s*(.+)/', $block),
                'download' => $this->captureValue('/Download:\s*([^;\r\n]+)/', $block),
                'upload' => $this->captureValue('/Upload:\s*([^;\r\n]+)/', $block),
                'state' => $this->captureValue('/State:\s*(.+)/', $block),
            ];

            if (!empty($client['mac'])) {
                $clients[] = array_map(static fn($v) => trim((string) $v), $client);
            }
        }

        return $clients;
    }

    /**
     * Extract first regex capture group from a text block.
     */
    private function captureValue(string $pattern, string $subject): string
    {
        if (preg_match($pattern, $subject, $m)) {
            return trim($m[1]);
        }
        return '-';
    }

    /**
     * Executes ndsctl deauth for a specific client identifier.
     */
    private function deauthenticateClient(string $identifier): bool
    {
        $cmd = 'sudo ' . escapeshellarg($this->binPath) . ' deauth ' . escapeshellarg($identifier);
        exec($cmd, $response, $exitCode);
        return $exitCode === 0;
    }

    /**
     * Validate MAC format before passing to shell command.
     */
    private function isValidMacAddress(string $mac): bool
    {
        return (bool) preg_match('/^[0-9a-f]{2}(:[0-9a-f]{2}){5}$/i', $mac);
    }

    /* A method to persist plugin data
     *
     * This writes to the volatile /tmp directory which is cleared
     * on each system boot, so should not be considered as a robust
     * method of data persistence; it's used here for demo purposes only.
     *
     * @note Plugins should avoid use of $_SESSION vars as these are
     * super globals that may conflict with other user plugins.
     */
    public function persistData()
    {
        $serialized = serialize($this);
        file_put_contents("/tmp/plugin__{$this->getName()}.data", $serialized);
    }

    // Static method to load persisted data
    public static function loadData(): ?self
    {
        $filePath = "/tmp/plugin__".self::getName() .".data";
        if (file_exists($filePath)) {
            $data = file_get_contents($filePath);
            return unserialize($data);
        }
        return null;
    }

    // Returns an abbreviated class name
    public static function getName(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }

}

