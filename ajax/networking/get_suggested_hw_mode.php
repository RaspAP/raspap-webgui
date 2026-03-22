<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

header('Content-Type: application/json');

$interface = $_POST['interface'] ?? '';

if (empty($interface)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing interface parameter']);
    exit;
}

$suggestedHWMode = suggestWifiHwMode($interface);
$supportedModes = supportedModes($suggestedHWMode);

$data = [
    'interface' => $interface,
    'suggested_hw_mode' => $suggestedHWMode,
    'supported_modes' => $supportedModes,
];

function suggestWifiHwMode(string $interface): string {
    $interface = escapeshellarg($interface);
    
    $phyCmd = "iw dev $interface info 2>/dev/null | grep -oP 'phy\\s*\\K\\S+'";
    $phy = trim(shell_exec($phyCmd));

    if ($phy === '') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No valid phy for ' . $interface]);
        exit;
    }

    $cmd = "iw phy phy$phy info 2>/dev/null";
    $text = shell_exec($cmd);
    
    if (empty($text)) {
        http_response_code(500);
        echo json_encode(['error' => 'No phy info retrieved']);
        exit;
    }

    // --- Capability presence ---
    $hasEHT = preg_match('/EHT\s+(?:Capabilities|MAC Capabilities|PHY Capabilities)/i', $text);
    $hasHE  = preg_match('/HE\s+(?:Capabilities|MAC Capabilities|PHY Capabilities)/i', $text);
    $hasVHT = preg_match('/VHT\s+Capabilities/i', $text);
    $hasHT  = preg_match('/HT\s+(?:Capabilities|MCS rate indexes supported)/i', $text);

    // --- Band / frequency detection ---
    $has24GHz = preg_match('/Band\s+1:|2412\s*MHz|2437\s*MHz|2462\s*MHz/i', $text);
    $has5GHz  = preg_match('/Band\s+2:|5180\s*MHz|5500\s*MHz|5745\s*MHz/i', $text);
    $has6GHz  = preg_match('/Band\s+[34]:|5955\s*MHz|6[0-9]{3}\s*MHz/i', $text);

    // Decision - prioritize explicit capability blocks over bands
    if ($hasEHT) {
        $suggested = 'be';
    } elseif ($hasHE) {
        $suggested = 'ax';
    } elseif ($hasVHT) {
        $suggested = 'ac';
    } elseif ($hasHT) {
        $suggested = 'n';
    } elseif ($has5GHz) {
        $suggested = 'a';
    } else {
        $suggested = 'g';
    }

    // Return debug info in JSON for now
    // header('Content-Type: application/json');
    // echo json_encode([
    //     'phy'           => $phy,
    //     'cmd'           => $cmd,
    //     'suggested'     => $suggested,
    //     'text'          => $text,
    //     'debug'         => [
    //         'hasEHT'  => (bool)$hasEHT,
    //         'hasHE'   => (bool)$hasHE,
    //         'hasVHT'  => (bool)$hasVHT,
    //         'hasHT'   => (bool)$hasHT,
    //         'has5GHz' => (bool)$has5GHz,
    //         'has6GHz' => (bool)$has6GHz,
    //         'heMatches' => $heMatches,
    //     ],
    //     'label' => match($suggested) {
    //         'be' => '802.11be - 2.4/5/6 GHz',
    //         'ax' => '802.11ax - 2.4/5/6 GHz',
    //         'ac' => '802.11ac - 5 GHz',
    //         'n'  => '802.11n - 2.4/5 GHz',
    //         'a'  => '802.11a - 5 GHz',
    //         'g'  => '802.11g - 2.4 GHz',
    //         default => 'Unknown'
    //     }
    // ]);

    // Comment out the above echo and uncomment below for production:
    return $suggested;
}

function supportedModes(string $highestMode, bool $incl = true): array {
    $standards = HotspotService::get80211Standards();

    $standardsCodes = array_keys($standards);

    $i = array_search($highestMode, $standardsCodes, true);

    return $i === false ? $arr : array_slice($standardsCodes, 0, $incl ? $i + 1 : $i);
}

echo json_encode($data, JSON_PRETTY_PRINT);

