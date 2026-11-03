<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

$entity = $_POST['entity'] ?? '';

if (in_array($entity, ['wg-server', 'wg-peer'], true)) {

    // generate the public/private key pair of the entity in /etc/wireguard,
    // without a copy of the private key in /tmp
    $pubkey = \RaspAP\Networking\VpnFile::run(['wireguard', 'genkey', $entity]);
    $wgdata['pubkey'] = trim((string)$pubkey);

    echo json_encode($wgdata);
}
