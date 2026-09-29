<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

header('Content-Type: application/json');

$interface = $_POST['interface'] ?? '';
$interface = preg_replace('/[^a-zA-Z0-9]/', '', $interface); // sanitize
$udev_path = "/sys/class/net/{$interface}";

if (empty($interface) || !file_exists($udev_path)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing interface']);
    exit;
}

$data = [
    'interface' => $interface,
    'interface_up' => false,
    'udev' => [],
    'driver' => [],
];

// check if interface is up for buttons
exec("ip link show $interface 2>/dev/null", $ipLinkOutput, $linkStatus);
$data['interface_up'] = (bool)preg_grep('/state UP/', $ipLinkOutput);

// udevadm info
$udev_cmd = "udevadm info --query=property --path=" . escapeshellarg($udev_path);
exec($udev_cmd, $udev_output, $udev_ret);
if ($udev_ret === 0) {
    foreach ($udev_output as $line) {
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $data['udev'][$key] = $value;
        }
    }
}

// ethtool info
$ethtool_cmd = "ethtool -i {$interface} 2>/dev/null";
exec($ethtool_cmd, $ethtool_output, $ethtool_ret);
if ($ethtool_ret === 0) {
    foreach ($ethtool_output as $line) {
        if (strpos($line, ':') !== false) {
            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $data['driver'][$key] = $value;
        }
    }
}

// append supported modes
$data['capabilities'] = [
    'supported_modes' => getSupportedModes($interface)
];

echo json_encode($data, JSON_PRETTY_PRINT);

function getSupportedModes(string $interface): string
{
    // resolve phy for interface
    $phyCmd = "iw dev " . escapeshellarg($interface) . " info 2>/dev/null | grep wiphy | awk '{print \"phy\" \$2}'";
    $phy = trim(shell_exec($phyCmd));
    if (empty($phy)) {
        error_log("Could not resolve phy for interface: {$interface}");
        return 'Unavailable';
    }

    // get output for phy
    $phyInfoCmd = "iw phy " . escapeshellarg($phy) . " info 2>/dev/null";
    $phyOutput = shell_exec($phyInfoCmd);
    if (empty($phyOutput)) {
        error_log("iw phy output empty for {$phy}");
        return 'Unavailable';
    }

    // parse supported modes
    $lines = explode("\n", $phyOutput);
    $modes = [];
    $capture = false;

    foreach ($lines as $line) {
        $line = trim($line);

        if (strncmp($line, 'Supported interface modes:', 26) === 0) {
            $capture = true;
            continue;
        }
        if ($capture && strncmp($line, '*', 1) !== 0) {
            break;
        }
        // lines starting with "*"
        if ($capture && preg_match('/^\*\s+(.*)$/', $line, $matches)) {
            $modes[] = $matches[1];
        }
    }
    return !empty($modes) ? implode(', ', $modes) : 'None';
}

