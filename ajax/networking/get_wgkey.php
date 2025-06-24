<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

function sanitize_entity(string $raw): string {
    // Step 1: Remove dangerous shell characters by escaping, then trimming quotes
    $escaped = escapeshellarg($raw);
    $clean = trim($escaped, " \t\n\r\0\x0B'\"");
    // Step 2: Replace all non-alphanum/hyphen/underscore characters with underscores
    $clean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $clean ?? '');
    // Step 3: Handle null result from preg_replace (rare edge case)
    if (!is_string($clean)) $clean = '';
    // Step 4: Truncate to 32 characters (safely)
    $clean = substr($clean, 0, 32) ?: ''; 
    // Step 5: Fallback: if string becomes empty, use md5 hash of raw input
    return strlen($clean) > 0 ? $clean : md5($raw);
}

$entity = sanitize_entity($_POST['entity']);

if (isset($entity)) {

    // generate public/private key pairs for entity
    $pubkey = RASPI_WIREGUARD_PATH.$entity.'-public.key';
    $privkey = RASPI_WIREGUARD_PATH.$entity.'-private.key';
    $pubkey_tmp = '/tmp/'.$entity.'-public.key';
    $privkey_tmp = '/tmp/'.$entity.'-private.key';

    exec("sudo wg genkey | tee $privkey_tmp | wg pubkey > $pubkey_tmp", $return);
    $wgdata['pubkey'] = str_replace("\n",'',file_get_contents($pubkey_tmp));
    exec("sudo mv $privkey_tmp $privkey", $return);
    exec("sudo mv $pubkey_tmp $pubkey", $return);

    echo json_encode($wgdata);
}
