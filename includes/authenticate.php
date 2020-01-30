<?php
$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

$adminValidated = ($user == $config['admin_user']) && password_verify($pass, $config['admin_pass']);
$guestValidated = ($user == $config['guest_user']) && password_verify($pass, $config['guest_pass']);

if (!$adminValidated && !$guestValidated) {
    header('WWW-Authenticate: Basic realm="RaspAP"');
    if (function_exists('http_response_code')) {
        // http_response_code will respond with proper HTTP version back.
        http_response_code(401);
    } else {
        header('HTTP/1.0 401 Unauthorized');
    }

    exit('Not authorized'.PHP_EOL);
}
else if ($adminValidated){
    $config['user_type'] = 'admin';
}
else if($guestValidated){
 $config['user_type'] = 'guest';
}
