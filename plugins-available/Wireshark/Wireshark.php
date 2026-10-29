<?php

/**
 * Wireshark (TShark)
 *
 * @description A Wireshark CLI (TShark) packet capture for RaspAP 
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
 * @see         src/RaspAP/Plugins/PluginInterface.php
 * @see         src/RaspAP/UI/Sidebar.php
 */

namespace RaspAP\Plugins\Wireshark;

use RaspAP\Plugins\PluginInterface;
use RaspAP\UI\Sidebar;

if (!defined('SIGTERM')) define('SIGTERM', 15);
if (!defined('SIGKILL')) define('SIGKILL', 9);


class Wireshark implements PluginInterface
{

    private string $pluginPath;
    private string $pluginName;
    private string $templateMain;
    private string $serviceStatus;
    private string $serviceDesc;
    private string $label;
    private string $icon;
    private string $binPath;
    private string $hostname;
    private array $interfaces;
    private string $captureFile;
    private array $captureFiles;
    private $options = [];
    private int $pid;

    public function __construct(string $pluginPath, string $pluginName)
    {
        $system = new \RaspAP\System\Sysinfo;
        $this->pluginPath = $pluginPath;
        $this->pluginName = $pluginName;
        $this->templateMain = 'main';
        $this->serviceStatus = 'down';
        $this->serviceDesc = 'stopped';
        $this->label = _('Wireshark');
        $this->icon = 'ra-wireshark';
        $this->binPath = '/usr/bin/tshark';
        $this->hostname = $system->hostname();
        $this->interfaces = $this->getInterfaces();
        $this->captureFile = '/tmp/capture.pcap';
        $this->pid = 0;

        if ($loaded = self::loadData()) {
            $this->options = $loaded->options ?? [];
            $this->pid = $loaded->pid ?? 0;
        }

        if (empty($this->options)) {
            $this->options = $this->getDefaults();
        }
    }

    /**
     * Initialize the plugin
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
        $priority = 75;

        $sidebar->addItem($label, $icon, $action, $priority);
    }

    /**
     * Handles a page action by processing inputs and rendering a plugin template
     *
     * @param string $page the current page route
     */
    public function handlePageAction(string $page): bool
    {
        // verify that this plugin should handle the page
        if (strpos($page, "/plugin__" . $this->getName()) === 0) {

            // instantiate a StatusMessage object
            $status = new \RaspAP\Messages\StatusMessage;

            if (!RASPI_MONITOR_ENABLED) {
                if (isset($_POST['interface']) || !empty($this->options['interface'])) {
                    $interface = $_POST['interface'] ?? $this->options['interface'];
                }

                if (isset($_GET['download']) && isset($_GET['token'])) {
                    $this->handleFileDownload($_GET);
                    exit;
                }

                if (isset($_POST['saveSettings'])) {
                    $this->handleSaveSettings($_POST, $status);
                }

                if (isset($_POST['startCapture'])) {

                    // build options from saved config
                    $options = [
                        'output_file' => $this->options['output_file'],
                    ];
                    if (!empty($this->options['capture_filter'])) {
                        $options['capture_filter'] = $this->options['capture_filter'];
                    }
                    if (!empty($this->options['packet_count'])) {
                        $options['packet_count'] = $this->options['packet_count'];
                    }
                    if (!empty($this->options['duration'])) {
                        $options['duration'] = $this->options['duration'];
                    }
                    if (!empty($this->options['ring_buffer_size'])) {
                        $options['ring_buffer_size'] = $this->options['ring_buffer_size'];
                    }
                    if (!empty($this->options['ring_buffer_files'])) {
                        $options['ring_buffer_files'] = $this->options['ring_buffer_files'];
                    }
                    if (!empty($this->options['snaplen'])) {
                        $options['snaplen'] = $this->options['snaplen'];
                    }
                    if (!empty($this->options['promiscuous'])) {
                        $options['promiscuous'] = $this->options['promiscuous'];
                    }

                    // start packet capture
                    $status->addMessage(sprintf(_('Attempting to start packet capture on %s'), $interface), 'info');
                    $pid = $this->startCapture($interface, $options);
                    if (isset($pid)) {
                        $status->addMessage(sprintf(_('TShark started with PID %s'), $pid), 'success');
                        $status->addMessage('Packet capture in progress', 'success');
                        $this->setServiceStatus('up');
                        $this->serviceDesc = 'running';
                    } else {
                        $status->addMessage('Failed to start packet capture', 'danger');
                    }

                } elseif (isset($_POST['stopCapture'])) {
                    $status->addMessage(sprintf(_('Attempting to stop packet capture on %s'), $interface), 'info');
                    $success = $this->stopCapture();
                    if ($success) {
                        $status->addMessage('Packet capture stopped', 'success');
                        $this->setServiceStatus('down');
                        $this->serviceDesc = 'stopped';
                    } else {
                        $status->addMessage('Unable to stop packet capture', 'danger');
                    }
                }

                if (isset($_POST['delete_file'])) {
                        $this->handleFileDelete($_POST, $status);
                }

            }

            // check for expected binary
            if (!file_exists($this->binPath)) {
                $installPage = 'https://tshark.dev/setup/install/';
                $status->addMessage(sprintf(_('Expected tshark binary not found at: %s'), $this->binPath), 'warning');
                $status->addMessage(sprintf(_('Visit the <a href="%s" target="_blank">installation instructions</a> or reinstall the Wireshark plugin.'), $installPage), 'warning');
            } else {
                $hostname = $this->hostname;
            }

            $arrConfig = $this->options;
            $captureFiles = $this->getCaptureFiles();

            // Populate template data
            $__template_data = [
                'title' => $this->label,
                'description' => _('A Wireshark (TShark) CLI packet capture for RaspAP'),
                'author' => _('Bill Z'),
                'uri' => 'https://github.com/RaspAP/',
                'icon' => $this->icon,
                'serviceStatus' => $this->getServiceStatus(),
                'serviceDesc' => $this->serviceDesc,
                'interfaces' => $this->interfaces,
                'serviceName' => 'tshark',
                'action' => 'plugin__'.$this->getName(),
                'pluginName' => $this->getName(),
                'arrConfig' => $arrConfig,
                'captureFiles' => $captureFiles
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
     * Handle save settings action
     *
     * @param array $post POST data
     * @param StatusMessage $status
     */
    private function handleSaveSettings(array $post, $status): void
    {
        try {
            $this->options['interface'] = !empty($post['interface']) ?
                $post['interface'] : 'eth0';

            $this->options['output_file'] = !empty($post['output_file']) ?
                trim($post['output_file']) : '/tmp/capture.pcap';

            $this->options['capture_filter'] = !empty($post['capture_filter']) ?
                trim($post['capture_filter']) : '';

            $this->options['packet_count'] = !empty($post['packet_count']) && is_numeric($post['packet_count']) ?
                (int)$post['packet_count'] : '';

            $this->options['duration'] = !empty($post['duration']) && is_numeric($post['duration']) ?
                (int)$post['duration'] : '';

            $this->options['ring_buffer_size'] = !empty($post['ring_buffer_size']) && is_numeric($post['ring_buffer_size']) ?
                (int)$post['ring_buffer_size'] : '';

            $this->options['ring_buffer_files'] = !empty($post['ring_buffer_files']) && is_numeric($post['ring_buffer_files']) ?
                (int)$post['ring_buffer_files'] : '';

            $this->options['snaplen'] = !empty($post['snaplen']) && is_numeric($post['snaplen']) ?
                (int)$post['snaplen'] : '';

            $this->options['promiscuous'] = isset($post['promiscuous']) && $post['promiscuous'] == '1';

            $this->persistData();

            $status->addMessage(_('TShark settings saved successfully'), 'success');

        } catch (Exception $e) {
            error_log("TShark: Error saving settings - " . $e->getMessage());
            $status->addMessage(_('Error saving settings: ') . $e->getMessage(), 'danger');
        }
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
     * Starts TShark packet capture with options
     *
     * @param string $interface capture interface
     * @param array $options capture options
     *   capture_filter => string: BPF capture filter
     *   packet_count => int: stop after capturing N packets
     *   duration => int: stop after N seconds
     *   ring_buffer_size => int: file size in KB before rotation
     *   ring_buffer_files => int: number of ring buffer files
     *   snaplen => int: snapshot length (bytes per packet)
     *   promiscuous => bool: enable promiscuous mode (default: true)
     * @return int PID of started process
     * @throws Exception on failure to start tshark
     */
    public function startCapture(string $interface = 'eth0', array $options = []): int
    {
        // set defaults and sanitize options
        $outputFile = $this->captureFile;
        $captureFilter = $this->sanitizeFilter($options['capture_filter'] ?? '');
        $packetCount = isset($options['packet_count']) ? (int)$options['packet_count'] : null;
        $duration = isset($options['duration']) ? (int)$options['duration'] : null;
        $ringBufferSize = isset($options['ring_buffer_size']) ? (int)$options['ring_buffer_size'] : null;
        $ringBufferFiles = isset($options['ring_buffer_files']) ? (int)$options['ring_buffer_files'] : null;
        $snaplen = isset($options['snaplen']) ? (int)$options['snaplen'] : null;
        $promiscuous = $options['promiscuous'] ?? true;

        // build command
        $cmd = sprintf('sudo tshark -i %s', escapeshellarg($interface));

        // output file
        $cmd .= sprintf(' -w %s', escapeshellarg($outputFile));

        // capture filter (BPF syntax)
        if (!empty($captureFilter)) {
            $cmd .= sprintf(' -f %s', escapeshellarg($captureFilter));
        }

        // packet count limit
        if ($packetCount !== null && $packetCount > 0) {
            $cmd .= sprintf(' -c %d', $packetCount);
        }

        // duration limit
        if ($duration !== null && $duration > 0) {
            $cmd .= sprintf(' -a duration:%d', $duration);
        }

        // ring buffer configuration
        if ($ringBufferSize !== null && $ringBufferSize > 0) {
            $cmd .= sprintf(' -b filesize:%d', $ringBufferSize);
        }

        if ($ringBufferFiles !== null && $ringBufferFiles > 0) {
            $cmd .= sprintf(' -b files:%d', $ringBufferFiles);
        }

        // snapshot length
        if ($snaplen !== null && $snaplen > 0) {
            $cmd .= sprintf(' -s %d', $snaplen);
        }

        // promiscuous mode
        if (!$promiscuous) {
            $cmd .= ' -p';
        }

        // run process in background + capture pid
        $cmd .= ' > /dev/null 2>&1 & echo $!';

        $output = [];
        exec($cmd, $output);

        if (!empty($output[0])) {
            $this->pid = (int)trim($output[0]);
            if ($this->pid > 0) {
                $this->persistData();
                return $this->pid;
            }
        } 
        throw new \Exception("Failed to start TShark");
    }

    /**
     * Sanitizes BPF capture filter
     *
     * @param string $filter
     * @return string sanitized filter
     * @throws Exception
     */
    private function sanitizeFilter(string $filter): string
    {
        if (empty($filter)) {
            return '';
        }

        // strip null bytes + whitespace
        $filter = str_replace("\0", '', $filter);
        $filter = trim($filter);

        // check for command injection
        $invalid = ['|', ';', '&', '$', '`', "\n", "\r", '>', '<'];
        foreach ($invalid as $char) {
            if (strpos($filter, $char) !== false) {
                throw new \Exception("Invalid character in capture filter: {$char}");
            }
        }

        // basic BPF syntax check
        $validPattern = '/^[a-zA-Z0-9\s\.\:\(\)\[\]\/!=<>-]+$/';
        if (!preg_match($validPattern, $filter)) {
            throw new \Exception("Invalid BPF filter syntax");
        }

        // test filter validity
        $testCmd = sprintf('sudo tshark -f %s -c 0 2>&1', escapeshellarg($filter));
        exec($testCmd, $testOutput, $returnCode);

        $testResult = implode("\n", $testOutput);
        if (strpos($testResult, 'syntax error') !== false || strpos($testResult, 'parse error') !== false) {
            throw new \Exception("Invalid BPF filter: " . $testResult);
        }
        return $filter;
    }

    /**
     * Stop TShark packet capture with SIGTERM
     *
     * @return bool
     */
    public function stopCapture(): bool
    {
        if ($this->pid && $this->isRunning()) {
            // send SIGTERM with negative PID to kill process group 
            posix_kill(-$this->pid, SIGTERM);

            // kill parent PID
            posix_kill($this->pid, SIGTERM);

            $timeout = 5;
            while ($timeout > 0 && $this->isRunning()) {
                usleep(500000); // 0.5 seconds
                $timeout -= 0.5;
            }

            if ($this->isRunning()) {
                posix_kill($this->pid, SIGKILL);
            }
            return true;
        }
        return false;
    }

    /**
     * Check if TShark process is running
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        if ($this->pid === null) {
            return false;
        }
        return posix_kill($this->pid, 0);
    }

    /**
     * Gets TShark PID
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
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
        $this->serviceStatus = $status;
        $this->persistData();
    }

    /**
     * Enumerates available network interfaces
     *
     * @return array $interfaces
     */
    public function getInterfaces(): array
    {
        exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);

        // filter out loopback, others?
        $interfaces = array_filter($interfaces, function ($iface) {
            return !preg_match('/^(lo)/', $iface);
        });
        $interfaces[] = "any";
        sort($interfaces);

        return array_values($interfaces);
    }

    /**
     * Get default capture options
     *
     * @return array default configuration
     */
    private function getDefaults(): array
    {
        return [
            'interface' => 'eth0',
            'output_file' => '/tmp/capture.pcap',
            'capture_filter' => '',
            'packet_count' => '',
            'duration' => '300',
            'ring_buffer_size' => '',
            'ring_buffer_files' => '',
            'snaplen' => '',
            'promiscuous' => true
        ];
    }

    /**
     * TShark capture file Management
     * Methods for listing and downloading capture files
     */

    /**
     * Get list of capture files in /tmp directory
     *
     * @return array Array of capture file information
     */
    private function getCaptureFiles(): array
    {
        $captureFiles = [];
        $tmpDir = '/tmp';

        // find all .pcap and .pcapng files
        $pattern = $tmpDir . '/*.{pcap,pcapng}';
        $files = glob($pattern, GLOB_BRACE);

        if ($files === false) {
            error_log("TShark: Failed to list capture files");
            return $captureFiles;
        }

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $fileInfo = [
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'size_human' => getHumanReadableDatasize(filesize($file)),
                'modified' => filemtime($file),
                'modified_human' => date('Y-m-d H:i:s', filemtime($file)),
                'download_token' => $this->generateToken(basename($file))
            ];

            $captureFiles[] = $fileInfo;
        }

        // sort by modification time
        usort($captureFiles, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $captureFiles;
    }

    /**
     * Generate secure download token for a file
     *
     * @param string $filename filename to generate token for
     * @return string secure token
     */
    private function generateToken(string $filename): string
    {
        // use session ID +filename to create unique token
        $secret = 'raspap_secret';
        return hash_hmac('sha256', $filename . session_id(), $secret);
    }

    /**
     * Verify download token
     *
     * @param string $filename the filename
     * @param string $token token to verify
     * @return bool true if valid
     */
    private function verifyToken(string $filename, string $token): bool
    {
        $expectedToken = $this->generateToken($filename);
        return hash_equals($expectedToken, $token);
    }

    /**
     * Handle file download request
     * 
     * @param array $get GET parameters
     */
    private function handleFileDownload(array $get): void
    {
        if (!isset($get['download']) || !isset($get['token'])) {
            return;
        }

        $filename = basename($get['download']); // prevent path traversal
        $token = $get['token'];

        // verify token
        if (!$this->verifyToken($filename, $token)) {
            http_response_code(403);
            die('Invalid download token');
        }

        // validate file
        $filePath = '/tmp/' . $filename;
        if (!file_exists($filePath) || !preg_match('/\.(pcap|pcapng)$/i', $filename)) {
            http_response_code(404);
            die('File not found');
        }

        if (!is_file($filePath)) {
            http_response_code(403);
            die('Invalid file type');
        }

        $filesize = filesize($filePath);

        // important: clean output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // set headers for download
        header('Content-Type: application/vnd.tcpdump.pcap');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $filesize);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        flush();

        $cmd = sprintf('sudo cat %s', escapeshellarg($filePath));
        passthru($cmd);
        exit;
    }

    /**
     * Handle file delete
     *
     * @param array $post POST data
     * @param StatusMessages $status
     */
    private function handleFileDelete(array $post, $status): void
    {
        if (!isset($post['delete_file'])) {
            return;
        }

        $filename = basename($post['delete_file']); // Security: prevent path traversal
        $filePath = '/tmp/' . $filename;

        if (!file_exists($filePath) || !preg_match('/\.(pcap|pcapng)$/i', $filename)) {
            $status->addMessage(_('File not found'), 'danger');
            return;
        }

        // delete the file
        $cmd = sprintf('sudo rm %s 2>&1', escapeshellarg($filePath));
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0) {
            $status->addMessage(sprintf(_('File "%s" deleted successfully'), $filename), 'success');
        } else {
            error_log("TShark: Failed to delete file - " . implode("\n", $output));
            $status->addMessage(sprintf(_('Failed to delete file "%s"'), $filename), 'danger');
        }
    }

    /* Persist plugin data
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

