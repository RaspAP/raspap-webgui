<?php
$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

if (!filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
    exit();
}

$iphash = '';
if (extension_loaded('apcu')) {
  if (!defined('SECONDSBLOCKBRUTEFORCE')) {
    define('SECONDSBLOCKBRUTEFORCE', 30);
  }

  if (!defined('MAXNUMFAILEDLOGINS')) {
    define('MAXNUMFAILEDLOGINS', 3);
  }

  if (apcu_exists('IPHASHSECRET')) {
    define('IPHASHSECRET', apcu_fetch('IPHASHSECRET'));
  }

  if (!defined('IPHASHSECRET')) {
    $ipHashSecret = str_replace('=', '', base64_encode(openssl_random_pseudo_bytes(16)));
    define('IPHASHSECRET', $ipHashSecret);
    $ipHashSecret = null;
    apcu_store('IPHASHSECRET', IPHASHSECRET);
  }

  $iphash = hash_hmac('sha256', $_SERVER['REMOTE_ADDR'], IPHASHSECRET, false);
  if (apcu_exists('iphashfailedlogin')) {
    $lastIpHashFailedLogin = apcu_fetch('iphashfailedlogin');
    if (hash_equals($lastIpHashFailedLogin, $iphash)) {
      $numFailedLogins = apcu_fetch('numfailedlogins');
      if ($numFailedLogins >= MAXNUMFAILEDLOGINS) {
        // Bruteforce detected.
        if (function_exists('http_response_code')) {
          http_response_code(429);
        } else {
          header('HTTP/1.0 429 Too Many Requests');
        }

        if ($numFailedLogins > MAXNUMFAILEDLOGINS) {
          exit();
        }

        apcu_inc('numfailedlogins');
        exit(sprintf('Too many failed logins. Please wait at least %d seconds before trying to login again.', SECONDSBLOCKBRUTEFORCE));
      }
    }
  }
}

$validated = ($user == $config['admin_user']) && password_verify($pass, $config['admin_pass']);

if (!$validated) {
  header('WWW-Authenticate: Basic realm="RaspAP"');
  if (function_exists('http_response_code')) {
    // http_response_code will respond with proper HTTP version back.
    http_response_code(401);
  } else {
    header('HTTP/1.0 401 Unauthorized');
  }

  if (extension_loaded('apcu') && !empty($iphash)) {
    apcu_store('iphashfailedlogin', $iphash, SECONDSBLOCKBRUTEFORCE);
    if (!apcu_exists('numfailedlogins')) {
      apcu_add('numfailedlogins', 0, SECONDSBLOCKBRUTEFORCE);
    }

    apcu_inc('numfailedlogins');
  }

  exit('Not authorized'.PHP_EOL);
}
