<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

define('BLOCKLISTS_FILE', __DIR__ . '/../../config/blocklists.json');

if (isset($_POST['blocklist_id'])) {
    $blocklist_id = $_POST['blocklist_id'];
    $json = file_get_contents(BLOCKLISTS_FILE);
    $allLists = json_decode($json, true);

    if ($allLists === null) {
        echo json_encode([
            'return' => 3,
            'output' => ['Failed to parse blocklists.json']
        ]);
        exit;
    }
    $flatList = flattenList($allLists);

    if (!isset($flatList[$blocklist_id])) {
        echo json_encode(['return' => 1, 'output' => ['Invalid blocklist ID']]);
        exit;
    }

    $list_url = escapeshellcmd($flatList[$blocklist_id]['list_url']);
    $dest_file = escapeshellcmd($flatList[$blocklist_id]['dest_file']);
    $dest = pathinfo($dest_file, PATHINFO_FILENAME);
    $scriptPath = RASPI_CONFIG . '/adblock/update_blocklist.sh';

    if (!file_exists($scriptPath)) {
        echo json_encode([
            'return' => 5,
            'output' => ["Update script not found: $scriptPath"]
        ]);
        exit;
    }
    exec("sudo $scriptPath $list_url $dest_file " . RASPI_ADBLOCK_LISTPATH, $output, $return_var);
    echo json_encode([
        'return' => $return_var,
        'output' => $output,
        'list' => $dest
    ]);

} else {
    echo json_encode(['return' => 2, 'output' => ['No blocklist ID provided']]);
}

function flattenList(array $grouped): array {
    $flat = [];
    foreach ($grouped as $group) {
        foreach ($group as $name => $meta) {
            $flat[$name] = $meta;
        }
    }
    return $flat;
}

