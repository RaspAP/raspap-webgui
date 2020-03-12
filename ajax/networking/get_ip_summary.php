<?php

require '../../includes/csrf.php';

require_once '../../includes/functions.php';

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
