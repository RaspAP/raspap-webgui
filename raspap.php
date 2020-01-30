<?php

$config = array(
    'admin_user' => 'admin',
    'admin_pass' => '$2y$10$YKIyWAmnQLtiJAy6QgHQ.eCpY4m.HCEbiHaTgN6.acNC6bDElzt.i',
	'guest_user' => 'guest1',
    'guest_pass' => '$2y$10$HBTn5OYaZyVeArGbRNtJDeb8B8QLu12Ee5ZyXHbA7MK8XO3oOmEzq'
);


if (file_exists(RASPI_CONFIG.'/raspap.auth')) {
    if ($auth_details = fopen(RASPI_CONFIG.'/raspap.auth', 'r')) {
	$config['admin_user'] = trim(fgets($auth_details));
        $config['admin_pass'] = trim(fgets($auth_details));
        $config['guest_user'] = trim(fgets($auth_details));
        $config['guest_pass'] = trim(fgets($auth_details));
        fclose($auth_details);
    }
}
