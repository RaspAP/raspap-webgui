<?php
$valid_passwords = array ("admin" => "admin");
$valid_users = array_keys($valid_passwords);

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

//$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);
$validated = ($user == $config['admin_user']) && password_verify($pass, $config['admin_pass']);

if (!$validated) {
  header('WWW-Authenticate: Basic realm="RaspAP"');
  header('HTTP/1.0 401 Unauthorized');
  die ("Not authorized");
}

?>
