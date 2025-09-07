<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

$entity = escapeshellarg($_POST['entity']);

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
