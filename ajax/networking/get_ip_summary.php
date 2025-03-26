<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

if (isset($_POST['interface'])) {
    $int = preg_replace('/[^a-z0-9]/', '', $_POST['interface']);
    exec('ip a s '.$int, $intOutput, $intResult);
    $intOutput = array_map('htmlentities', $intOutput);
    $jsonData = ['return'=>$intResult,'output'=>$intOutput];
    echo json_encode($jsonData);
} else {
    $jsonData = ['return'=>2,'output'=>['Error getting data']];
    echo json_encode($jsonData);
}
