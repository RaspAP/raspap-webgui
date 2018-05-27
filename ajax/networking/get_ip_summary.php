<?php
session_start();
include_once('../../includes/functions.php');

if(isset($_POST['interface']) && isset($_POST['csrf_token']) && CSRFValidate()) {
    $int = preg_replace('/[^a-z0-9]/','',$_POST['interface']);
    exec('ip a s '.$int,$intOutput,$intResult);
    $jsonData = ['return'=>$intResult,'output'=>$intOutput];
    echo json_encode($jsonData);
} else {
    $jsonData = ['return'=>2,'output'=>['Error getting data']];
    echo json_encode($jsonData);
}

?>
