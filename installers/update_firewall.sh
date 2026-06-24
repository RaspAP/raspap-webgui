#!/bin/bash
# include the raspap helper functions
source /usr/local/sbin/raspap_helpers.sh

_getWebRoot

echo -n "Update firewall ... "

cat << EOF > /tmp/updateFirewall.php
<?php
//set_include_path('/var/www/html/');
\$_SESSION['locale']="en_GB.UTF-8";

require_once 'includes/config.php';
require_once 'includes/defaults.php';
require_once RASPI_CONFIG.'/raspap.php';
require_once 'includes/locale.php';
require_once 'includes/wifi_functions.php';
require_once 'includes/get_clients.php';
require_once 'includes/firewall.php';

updateFirewall();

?>
EOF

sudo php -d include_path=$raspap_webroot /tmp/updateFirewall.php
rm /tmp/updateFirewall.php
echo "done."
