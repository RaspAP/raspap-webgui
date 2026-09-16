<?php

/**
 * Tailscale
 *
 * @description A Tailscale VPN exit node plugin for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
 * @see         src/RaspAP/Plugins/PluginInterface.php
 * @see         src/RaspAP/UI/Sidebar.php
 */

namespace RaspAP\Plugins\Tailscale;

use RaspAP\Plugins\PluginInterface;
use RaspAP\UI\Sidebar;

class Tailscale implements PluginInterface
{

    private string $pluginPath;
    private string $pluginName;
    private string $templateMain;
    private string $serviceStatus;
    private string $label;
    private string $icon;
    private string $binPath;
    private string $hostname;
    private bool $btnRefresh;
    private bool $btnNext;
    private bool $btnBack;
    private bool $btnNextSave;
    private bool $btnSave;
    private bool $btnLogout;
    private bool $btnService;
    private bool $btnStopUsing;
    private bool $btnRevokeNode;
    private bool $createNode;
    private bool $existingNode;
    private bool $advertiseNode;
    private bool $pendingNode;
    private bool $pendingSubnet;
    private bool $usingNode;

    public function __construct(string $pluginPath, string $pluginName)
    {
        $system = new \RaspAP\System\Sysinfo;
        $this->pluginPath = $pluginPath;
        $this->pluginName = $pluginName;
        $this->templateMain = 'main';
        $this->serviceStatus = 'up';
        $this->label = _('Tailscale VPN');
        $this->icon = 'ra-tailscale';
        $this->binPath = '/usr/bin/tailscale';
        $this->hostname = $system->hostname();
        $this->btnRefresh = false;
        $this->btnNext = false;
        $this->btnBack = false;
        $this->btnNextSave = false;
        $this->btnSave = false;
        $this->btnLogout = false;
        $this->btnService = false;
        $this->btnStopUsing = false;
        $this->btnRevokeNode = false;
        $this->createNode = false;
        $this->existingNode = false;
        $this->advertiseNode = false;
        $this->pendingNode = false;
        $this->pendingSubnet = false;
        $this->usingNode = false;

        if ($loaded = self::loadData()) {
            $this->advertiseNode = $loaded->advertiseNode;
            $this->pendingNode = $loaded->pendingNode;
            $this->createNode = $loaded->createNode;
            $this->existingNode = $loaded->existingNode;
            $this->usingNode = $loaded->usingNode;
            $this->pendingSubnet = $loaded->pendingSubnet;
            $this->exitNodeAddress = $loaded->exitNodeAddress;
        }
    }

    /**
     *
     * @param Sidebar $sidebar an instance of the Sidebar
     * @see src/RaspAP/UI/Sidebar.php
     * @see https://fontawesome.com/icons
     */
    public function initialize(Sidebar $sidebar): void
    {

        $label = $this->label;
        $icon = $this->icon;
        $action = 'plugin__'.$this->getName();
        $priority = 73;

        $sidebar->addItem($label, $icon, $action, $priority);
    }

    /**
     * Handles a page action by processing inputs and rendering a plugin template.
     *
     * @param string $page the current page route
     */
    public function handlePageAction(string $page): bool
    {
        // Verify that this plugin should handle the page
        if (strncmp($page, "/plugin__" . $this->getName(), strlen("/plugin__" . $this->getName())) === 0) {

            // Instantiate a StatusMessage object
            $status = new \RaspAP\Messages\StatusMessage;

            if (!RASPI_MONITOR_ENABLED) {
                if (isset($_POST['nextSave'])) {
                    $nodeConfigOpt = isset($_POST['configOpt']) ? $_POST['configOpt'] : '';
                    switch ($nodeConfigOpt) {
                    case 'existing':
                        $this->existingNode = true;
                        $this->persistData();
                        break;
                    case 'create':
                        $this->createNode = true;
                        $this->persistData();
                        break;
                    case 'subnet':
                        $this->pendingSubnet = false;
                        $this->usingNode = true;
                        $this->persistData();
                    }
                    if (isset($_POST['exit_node'])) {
                        $ipv4address = $_POST['exit_node'];
                        if ($this->validIPv4($ipv4address)) {
                            $status->addMessage('Attempting to set device to use exit node at '.$ipv4address, 'info');

                            $subnet = null;
                            $allowLan = false;
                            $allowDNS = false;
                            $allowRoutes = false;
                            $forceReauth = false;

                            if (isset($_POST['optSubnetRoute'])) {
                                $subnet = $_POST['cidr'];
                                if (validateCidr($subnet)) {
                                    $status->addMessage('Attempting to advertise subnet route '.$subnet, 'info');
                                    $this->pendingSubnet = true;
                                } else {
                                    $status->addMessage('Invalid subnet route '.$subnet, 'danger');
                                }
                            } else {
                                $this->usingNode = true;
                            }
                            if (isset($_POST['optAllowLan'])) {
                                $status->addMessage('Attempting to route LAN traffic through exit node at '.$ipv4address, 'info');
                                $allowLan = true;
                            }
                            if (isset($_POST['optAllowDNS'])) {
                                $allowDNS = true;
                            }
                            if (isset($_POST['optAllowRoutes'])) {
                                $allowRoutes = true;
                            }
                            if (isset($_POST['optForceReauth'])) {
                                $forceReauth = true;
                            }
                            $magicHostname = $this->setExitNode($ipv4address, $subnet, $allowLan, $allowDNS, $allowRoutes, $forceReauth);

                            $status->addMessage('Device configured to use exit node at '.$ipv4address, 'success');
                            $status->addMessage('Other devices in your tailnet can reach this host at '.$magicHostname, 'success');

                            $this->existingNode = false;
                            $this->exitNodeAddress = $ipv4address;
                            $this->persistData();
                        } else {
                            $status->addMessage('Invalid IP address for exit node', 'danger');
                        }
                    }
                }
                if (isset($_POST['stopUsingNode']) && $this->usingNode) {
                    $exitNodeAddress = $_POST['editNodeAddress'];
                    $status->addMessage('Attempting to stop using exit node at '.$exitNodeAddress, 'info');
                    $this->setExitNode(); // unsets exit node
                    $this->usingNode = false;
                    $this->persistData();
                } elseif (isset($_POST['revokeNode'])) {
                    $status->addMessage('Attempting to revoke exit node', 'info');
                    $this->revokeExitNode(); // stop advertising exit node
                    $success = $this->tailscaleLogout();
                    if ($success) {
                        $status->addMessage('Disconnected from tailscale and expired node key', 'success');
                        $this->setServiceStatus('down');
                    } else {
                        $status->addMessage('Unable to disconnect from tailscale', 'danger');
                    }
                } elseif (isset($_POST['revertSave'])) {
                    $this->createNode = false;
                    $this->existingNode = false;
                    $this->persistData();
                }
                if (isset($_POST['nextSave']) && !$this->advertiseNode) {
                    if (isset($_POST['exitnode'])) {
                        $status->addMessage('Advertising device as a Tailscale exit node', 'info');
                        $this->advertiseExitNode();
                        $this->advertiseNode = true;
                        $this->persistData();
                    }
                    if (isset($_POST['optimize'])) {
                        $status->addMessage('Attempting to optimize UDP throughput', 'info');
                        $success = $this->optimizeUDP();
                        if ($success) {
                            $status->addMessage('Kernel transport layer offloads enabled for UDP', 'success');
                        } else {
                            $status->addMessage('Failed to enable kernel transport layer offloads for UDP', 'danger');
                        }
                    }
                } elseif (isset($_POST['startTailscale'])) {
                    $status->addMessage('Attempting to set tailscale up', 'info');
                    $this->setServiceStatus('up');

                } elseif (isset($_POST['stopTailscale'])) {
                    $status->addMessage('Attempting to set tailscale down', 'info');
                    $this->setServiceStatus('down');
                } elseif (isset($_POST['logout'])) {
                    $status->addMessage('Attempting to disconnect from tailscale', 'info');
                    $success = $this->tailscaleLogout();
                    if ($success) {
                        $status->addMessage('Disconnected from tailscale and expired node key', 'success');
                        $this->setServiceStatus('down');
                    } else {
                        $status->addMessage('Unable to disconnect from tailscale', 'danger');
                    }
                }
            }

            // check for expected binary
            if (!file_exists($this->binPath)) {
                $installPage = 'https://tailscale.com/kb/1174/install-debian-bookworm';
                $status->addMessage(sprintf(_('Expected tailscale binary not found at: %s'), $this->binPath), 'warning');
                $status->addMessage(sprintf(_('Visit the <a href="%s" target="_blank">installation instructions</a> or reinstall the Tailscale plugin.'), $installPage), 'warning');
            } else {
                $hostname = $this->hostname;
                $state = $this->getLoginStatus($hostname);
                $result = $this->handleStateChange($state, $status);
                $content = $result['content'];
                $status = $result['status'];
                $serviceLog = $this->getTailscaleStatusRaw();
            }

            // Populate template data
            $__template_data = [
                'title' => $this->label,
                'description' => _('A Tailscale VPN exit node extension for RaspAP'),
                'author' => _('Bill Z'),
                'uri' => 'https://github.com/RaspAP/',
                'icon' => $this->icon,
                'serviceStatus' => $this->getServiceStatus(),
                'serviceName' => 'tailscale',
                'action' => 'plugin__'.$this->getName(),
                'pluginName' => $this->getName(),
                'content' => $content,
                'btnRefresh' => $this->btnRefresh,
                'btnNext' => $this->btnNext,
                'btnSave' => $this->btnSave,
                'btnNextSave' => $this->btnNextSave,
                'btnBack' => $this->btnBack,
                'btnLogout' => $this->btnLogout,
                'btnService' => $this->btnService,
                'btnStopUsing' => $this->btnStopUsing,
                'btnRevokeNode' => $this->btnRevokeNode,
                'serviceLog' => $serviceLog
            ];

            echo $this->renderTemplate($this->templateMain, compact(
                "status",
                "__template_data"
            ));
            return true;
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
     * Resolves Tailscale login status from JSON output
     *
     * @param string $hostname
     * @return string
     */
    public function getLoginStatus(string $hostname): string
    {
        $cmd = 'sudo ' . escapeshellcmd($this->binPath) . ' status --json';
        $response = shell_exec($cmd);

        if (!$response) {
            return 'needs_login';
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'needs_login';
        }

        if (!empty($data['BackendState'])) {
            if ($data['BackendState'] == "NeedsLogin") {
                return "needs_login";
            }
        }

        // check if local machine is being queried
        if (!empty($data['Self']['HostName']) && $data['Self']['HostName'] === $hostname) {
            if (!empty($data['Self']['ExitNodeOption'])) {
                return 'logged_in_exit_node';
            }
            return 'logged_in';
        }

        // check peers
        if (!empty($data['Peer']) && is_array($data['Peer'])) {
            foreach ($data['Peer'] as $peer) {
                if (!empty($peer['HostName']) && $peer['HostName'] === $hostname) {
                    if (!empty($peer['ExitNode'])) {
                        return 'logged_in_exit_node';
                    }
                    return 'logged_in';
                }
            }
        }
        return 'needs_login';
    }

    /**
     * Retrieves a value from Tailscale status JSON by dot-notated key
     *
     * @param string $key dot-notated key (ex: "Self.HostName")
     * @return mixed|null returns value or null if not found
     */
    public function getTailscaleValue(string $key)
    {
        $cmd = 'sudo ' . escapeshellcmd($this->binPath) . ' status --json';
        $response = shell_exec($cmd);
        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $parts = explode('.', $key);
        foreach ($parts as $part) {
            if (is_array($data) && array_key_exists($part, $data)) {
                $data = $data[$part];
            } else {
                return null;
            }
        }

        return $data;
    }

    /**
     * Handles Tailscale state changes
     *
     * @param string $state
     * @param object $status
     * @return array{content: string, status: object}
     */
    public function handleStateChange($state, $status): array
    {
        if ($state === 'needs_login') {
            $this->setServiceStatus('up'); // Tailscale must be up to fetch AuthURL
            $loginUrl = $this->getTailscaleValue('AuthURL');
            if ($loginUrl) {
                ob_start();
                include 'templates/dialogs/auth.php';
                $content = ob_get_clean();
                $this->btnNext = true;
            } else {
                if ($this->serviceStatus == 'down') {
                    $btnLabel = _("Start Tailscale");
                    $this->btnService = true;
                } else {
                    $btnLabel = _("Refresh");
                    $this->btnRefresh = true;
                }
                $content = sprintf(_("Unable to retrieve Tailscale login. Choose <strong>%s</strong> to continue."), $btnLabel);
            }
        } elseif ($state === 'logged_in' && !$this->createNode && !$this->existingNode && !$this->usingNode && !$this->pendingSubnet) {
            $address = $this->getIPv4();
            if ($this->validIPv4($address)) {
                ob_start();
                include 'templates/dialogs/config.php';
                $content = ob_get_clean();
                $this->btnNextSave = true;
                $this->btnLogout = true;
                $this->btnService = true;
            }
        } elseif ($state === 'logged_in' && $this->createNode && !$this->pendingNode) {
            $address = $this->getIPv4();
            if ($this->validIPv4($address)) {
                ob_start();
                include 'templates/dialogs/exit.php';
                $content = ob_get_clean();
                $this->btnBack = true;
                $this->btnNextSave = true;
                $this->btnLogout = true;
                $this->btnService = true;
            }
        } elseif ($state === 'logged_in' && $this->pendingSubnet) {
            $address = $this->getIPv4();
            $exitNodeIP = $_POST['exit_node'];
            if ($this->validIPv4($address)) {
                ob_start();
                include 'templates/dialogs/subnet.php';
                $content = ob_get_clean();
                $this->btnNextSave = true;
                $this->btnLogout = true;
                $this->btnService = true;
            }
        } elseif ($state === 'logged_in' && $this->usingNode) {
            $address = $this->getIPv4();
            if ($this->validIPv4($address)) {
                $magicHostname = $this->getDnsHostname();
                ob_start();
                include 'templates/dialogs/using.php';
                $content = ob_get_clean();
                $this->btnStopUsing = true;
            }
        } elseif ($state === 'logged_in' && $this->existingNode) {
            $address = $this->getIPv4();
            if ($this->validIPv4($address)) {
                $nodeOptions = $this->getExitNodeOptions();
                $suggested = $this->getSuggestedExitNode();
                $subnet = $this->getInterfaceSubnet($_SESSION['ap_interface']);
                ob_start();
                include 'templates/dialogs/select.php';
                $content = ob_get_clean();
                if (!empty($nodeOptions)) {
                    $this->btnNextSave = true;
                }
                $this->btnBack = true;
                $this->btnLogout = true;
                $this->btnService = true;
            }
        } elseif ($state === 'logged_in' && $this->pendingNode) {
            ob_start();
            include 'templates/dialogs/approve.php';
            $content = ob_get_clean();
            $this->btnNext = true;
            $this->btnLogout = true;
            $this->btnService = true;
            $this->pendingNode = true;
            $this->persistData();
        } elseif ($state === 'logged_in_exit_node' && $this->createNode) {
            $status->addMessage('Device approved and activated as a Tailscale exit node', 'success');
            $address = $this->getIPv4();
            ob_start();
            include 'templates/dialogs/active.php';
            $content = ob_get_clean();
            $this->btnLogout = true;
            $this->btnService = true;
            $this->btnRevokeNode = true;
            $this->advertiseNode = false;
            $this->pendingNode = false;
            $this->persistData();

        }
        return [
            'content' => $content,
            'status' => $status
        ];
    }

    /**
     * Retrieves the current node's IPv4 address
     *
     * @return string $response
     */
    public function getIPv4(): string
    {
        $cmd = escapeshellcmd($this->binPath). ' ip --4';
        $response = shell_exec($cmd);
        return trim($response); 
    }

    /**
     * Validates an IPv4 address
     *
     * @param string $response
     * @return boolean
     */
    public function validIPv4($response): bool
    {
        return (bool) preg_match('/\b((?:\d{1,3}\.){3}\d{1,3})\b/', $response);
    }

    /**
     * Retrieves a list of available exit nodes in a tailnet
     *
     * @return array
     */
    public function getExitNodes(): array
    {
        $output = shell_exec('tailscale exit-node list');
        $lines = array_filter(explode("\n", $output));

        $nodes = [];
        foreach ($lines as $line) {
            $line = trim($line);
            // skip header and comments
            if (empty($line) || strncmp($line, '#', 1) === 0 || strpos($line, 'IP') !== false) {
                continue;
            }

            $columns = preg_split('/\s{2,}/', $line);
            if (count($columns) >= 5) {
                $nodes[] = [
                    'ip' => $columns[0],
                    'hostname' => $columns[1],
                    'country' => $columns[2],
                    'city' => $columns[3],
                    'status' => $columns[4]
                ];
            }
        }
        $nodes[] = compact('ip', 'hostname', 'country', 'city', 'status');
        return $nodes;
    }

    /**
     * Retrieves a list of options for a given node
     *
     * @return array $options
     */
    public function getExitNodeOptions(): array
    {

        $nodes = $this->getExitNodes();
        $suggested = $this->getSuggestedExitNode();
        $options = [];
        foreach ($nodes as $node) {
            if (
                empty($node['hostname']) ||
                empty($node['ip']) ||
                !is_string($node['hostname']) ||
                !is_string($node['ip'])
            ) {
                continue; // skip invalid entries
            }

            // trim trailing dot for matching hostname
            $label = sprintf('%s (%s)%s', $node['hostname'], $node['ip'],
                ($node['hostname'] === $suggested) ? ' ★' : ''
            );
            $options[$node['ip']] = $label;
        }

        return $options;
    }

    /**
     * Retrieves the hostname of the suggested exit node
     *
     * @return string
     */
    public function getSuggestedExitNode(): ?string
    {
        $output = shell_exec('tailscale exit-node suggest');
        if (preg_match('/Suggested exit node:\s+([\w\.-]+)/', $output, $matches)) {
            return rtrim($matches[1], '.'); // strip trailing dot in hostname
        }
        return null;
    }

    /**
     * Advertises device as a Tailscale exit node
     *
     * @return boolean
     */
    public function advertiseExitNode(): bool
    {
        $cmd = 'sudo ' .escapeshellcmd($this->binPath). ' up --advertise-exit-node';
        exec($cmd, $exitCode);

        // the tailscale CLI initiates the request silently and requires manual approval via the admin console.
        // this pending state is inferred with a flag here.
        $this->pendingNode = true;
        $this->persistData();

        return $exitCode === 0;
    }

    /**
     * Revokes a Tailscale exit node
     *
     * @return boolean
     */
    public function revokeExitNode(?string $ipv4address = null): bool
    {
        $cmd = 'sudo ' . escapeshellcmd($this->binPath) . ' set --advertise-exit-node=false';
        exec($cmd, $exitCode);
        usleep(100000); // sleep 100ms

        $cmd = 'sudo ' . escapeshellcmd($this->binPath) . ' up';
        exec($cmd, $exitCode);

        return $exitCode === 0; 
    }

    /**
     * Sets or unsets a Tailscale exit node
     *
     * @param string|null $ipv4address optional. if null, unsets the exit node
     * @param string|null $subnet optional. CIDR subnet to advertise
     * @param bool|null $allowLan optional
     * @param bool|null $acceptDNS optional
     * @param bool|null $acceptRoutes optional
     * @param bool|null $forceReauth optional
     * @return string|null hostname if setting an exit node, null when unsetting
     */
    public function setExitNode(
        ?string $ipv4address = null,
        ?string $subnet = null,
        ?bool $allowLan = null,
        ?bool $acceptDNS = null,
        ?bool $acceptRoutes = null,
        ?bool $forceReauth = null
        ): ?string
    {
        $cmd = 'sudo ' . escapeshellcmd($this->binPath) . ' up';
        
        if ($ipv4address !== null) {
            $cmd .= ' --exit-node=' . escapeshellarg($ipv4address);

            if ($subnet !== null) {
                $cmd .= ' --advertise-routes=' . escapeshellarg($subnet);
            }
            if ($allowLan) {
                $cmd .= ' --exit-node-allow-lan-access';
            }
            if ($acceptDNS) {
                $cmd .= ' --accept-dns';
            }
            if ($acceptRoutes) {
                $cmd .= ' --accept-routes';
            }
            if ($forceReauth) {
                $cmd .= ' --force-reauth';
            }
            $cmd .= ' --reset';
        } else {
            $cmd .= ' --reset'; // clear the exit node and reset defaults
        }
        exec($cmd, $output, $exitCode);
        return $exitCode === 0 && $ipv4address !== null ? $this->getDnsHostname() : null;
    }

    /**
     * Retrieves a Tailscale DNS hostname
     *
     * @see https://tailscale.com/kb/1054/dns#magicdns
     * @return string|null
     */
    public function getDnsHostname(): ?string
    {
        $cmd = 'sudo ' .escapeshellcmd($this->binPath). ' dns status';
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            return null;
        }

        foreach ($output as $line) {
            // match the MagicDNS suffix line
            if (preg_match('/suffix\s*=\s*([\w.-]+\.ts\.net)/', $line, $matches)) {
                return trim($this->hostname) .'.'. $matches[1];
            }
        }
        return null; // not found
    }

    /**
     * Retrieves the subnet for a given network interface
     *
     * @param string $interface
     * @return string|null if not found
     */
    public function getInterfaceSubnet($interface): ?string
    {
        $output = [];
        $cmd = "ip -o -f inet addr show ". escapeshellarg($interface) ." 2>/dev/null";
        exec($cmd, $output);

        if (empty($output)) {
            return null;
        }

        foreach ($output as $line) {
            if (preg_match('/inet (\d+\.\d+\.\d+\.\d+)\/(\d+)/', $line, $matches)) {
                $ip = $matches[1];
                $prefix = (int)$matches[2];
                $subnet = $this->ipToSubnet($ip, $prefix);
                return "$subnet/$prefix";
            }
        }
        return null;

    }

    private function ipToSubnet(string $ip, int $prefix): string
    {
        $ipLong = ip2long($ip);
        $netmask = -1 << (32 - $prefix);
        $netmask = $netmask & 0xFFFFFFFF; // ensure unsigned
        $network = $ipLong & $netmask;
        return long2ip($network);
    }

    /**
     * Optimizes a Tailscale exit node for enhanced UDP performance
     *
     * @see https://tailscale.com/kb/1320/performance-best-practices
     * @return boolean
     */
    public function optimizeUDP(): bool
    {
        $script = RASPI_CONFIG.'/networking/ts-optimize.sh';
        $cmd = escapeshellcmd("sudo {$script}");
        exec($cmd, $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * Disconnects from Tailscale and expires node key
     *
     * return boolean
     */
    public function tailscaleLogout()
    {
        $cmd = 'sudo ' .escapeshellcmd($this->binPath). ' logout';
        exec($cmd, $output, $exitCode);

        $this->createNode = false;
        $this->advertiseNode = false;
        $this->pendingNode = false;
        $this->persistData();

        return $exitCode === 0;
    }

    /**
     * Retrieves raw Tailscale status output
     *
     * @return string $output
     */
    public function getTailscaleStatusRaw(): string
    {
        $cmd = escapeshellcmd($this->binPath). ' status';
        exec($cmd, $response, $exitCode);

        if ($exitCode === 0) {
            $statusRaw = implode(PHP_EOL, $response);
            return $statusRaw;
        }
        return _("Not connected: Login required.");
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
    public function setServiceStatus($status)
    {
        $cmd = 'sudo ' .escapeshellcmd($this->binPath) .' '. escapeshellarg($status) .' > /dev/null 2>&1 &';
        exec($cmd, $output, $exitCode);
        sleep(2);

        if ($exitCode === 0) {
            $this->serviceStatus = $status;
        }
        $this->persistData();
    }

    /* An example method to persist plugin data
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

