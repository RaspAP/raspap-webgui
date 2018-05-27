<?php
$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

$validated = ($user == $config['admin_user']) && password_verify($pass, $config['admin_pass']);

if (!$validated) {
  header('WWW-Authenticate: Basic realm="RaspAP"');
  header('HTTP/1.0 401 Unauthorized');
  die ("Not authorized");
}

?>
