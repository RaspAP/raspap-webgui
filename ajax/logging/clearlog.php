<?php

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (isset($_POST['svc'])) {
    $tmplog = '/tmp/' .$_POST['svc']. '.log';

    // clear log for requested service
    exec("sudo truncate -s 0 $tmplog", $return);
    echo json_encode($return);
}
