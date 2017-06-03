<?php

/**
 * Raspbian WiFi Configuration Portal
 *
 * Enables use of simple web interface rather than SSH to control wifi and hostapd on the Raspberry Pi.
 * Recommended distribution is Raspbian Server Edition. Specific instructions to install the supported software are
 * in the README and original post by @SirLagz. For a quick run through, the packages required for the WebGUI are:
 * lighttpd (I have version 1.4.31-2 installed via apt)
 * php5-cgi (I have version 5.4.4-12 installed via apt)
 * along with their supporting packages, php5 will also need to be enabled.
 * 
 * @author     Lawrence Yau <sirlagz@gmail.comm>
 * @author     Bill Zimmerman <billzimmerman@gmail.com>
 * @author     Joe Haig <josephhaig@gmail.com>
 * @license    GNU General Public License, version 3 (GPL-3.0)
 * @version    1.1
 * @link       https://github.com/billz/raspap-webgui
 * @see        http://sirlagz.net/2013/02/08/raspap-webgui/
 */

define('RASPI_CONFIG', '/etc/raspap');
define('RASPI_ADMIN_DETAILS', RASPI_CONFIG.'/raspap.auth');

// Constants for configuration file paths.
// These are typical for default RPi installs. Modify if needed.
define('RASPI_DNSMASQ_CONFIG', '/etc/dnsmasq.conf');
define('RASPI_DNSMASQ_LEASES', '/var/lib/misc/dnsmasq.leases');
define('RASPI_HOSTAPD_CONFIG', '/etc/hostapd/hostapd.conf');
define('RASPI_WPA_SUPPLICANT_CONFIG', '/etc/wpa_supplicant/wpa_supplicant.conf');
define('RASPI_HOSTAPD_CTRL_INTERFACE', '/var/run/hostapd');
define('RASPI_WPA_CTRL_INTERFACE', '/var/run/wpa_supplicant');
define('RASPI_OPENVPN_CLIENT_CONFIG', '/etc/openvpn/client.conf');
define('RASPI_OPENVPN_SERVER_CONFIG', '/etc/openvpn/server.conf');
define('RASPI_TORPROXY_CONFIG', '/etc/tor/torrc');

// Optional services, set to true to enable.
define('RASPI_OPENVPN_ENABLED', false );
define('RASPI_TORPROXY_ENABLED', false );

include_once( RASPI_CONFIG.'/raspap.php' );
include_once( 'includes/functions.php' );
include_once( 'includes/dashboard.php' );
include_once( 'includes/authenticate.php' );
include_once( 'includes/admin.php' );
include_once( 'includes/dhcp.php' );
include_once( 'includes/hostapd.php' );
include_once( 'includes/system.php' );
include_once( 'includes/configure_client.php' );

$output = $return = 0;
$page = $_GET['page'];

session_start();
if (empty($_SESSION['csrf_token'])) {
    if (function_exists('mcrypt_create_iv')) {
        $_SESSION['csrf_token'] = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
    } else {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}
$csrf_token = $_SESSION['csrf_token'];

$navigation = array(
  array(
    'label' => 'Dashboard',
    'icon' => 'dashboard',
    'link' => 'index.php?page=wlan0_info'
  ),
  array(
    'label' => 'Client',
    'icon' => 'signal',
    'link' => 'index.php?page=wpa_conf'
  ),
  array(
    'label' => 'Hotspot',
    'icon' => 'record',
    'link' => 'index.php?page=hostapd_conf'
  ),
  array(
    'label' => 'DHCP',
    'icon' => 'transfer',
    'link' => 'index.php?page=dhcpd_conf'
  )
);

if ( RASPI_OPENVPN_ENABLED ) {
  array_push($navigation, array(
      'label' => 'OpenVPN',
      'icon' => 'eye-close',
      'link' => 'index.php?page=openvpn_conf'
    )
  );
}
if ( RASPI_TORPROXY_ENABLED ) {
  array_push($navigation, array(
      'label' => 'TOR proxy',
      'icon' => 'sunglasses',
      'link' => 'index.php?page=torproxy_conf'
    )
  );
}

array_push($navigation,
  array(
    'label' => 'Auth',
    'icon' => 'lock',
    'link' => 'index.php?page=auth_config'
  ),
  array(
    'label' => 'System',
    'icon' => 'cog',
    'link' => 'index.php?page=dhcpd_conf'
  )
);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, inital-scale=1">

    <title>Raspbian WiFi Configuration Portal</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </head>

  <body>
    <div class="navbar navbar-default" role="navigation">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navigation" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">
            <span><img class="logo" src="img/raspAP-logo.png" height="25"></span>
            RaspAP Wifi Portal v1.1
          </a>
        </div>
      </div>
    </div>
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-3 col-sm-4">
          <nav class="collapse navbar-collapse" id="navigation" role="navigation">
            <div class="panel panel-default">
              <div class="panel-body">
                <div class="sidebar-nav navbar-collapse">
                  <ul class="nav nav-pills nav-stacked">
                    <?php foreach($navigation as $nav) { ?>
                    <li>
                      <a href="<?php echo $nav['link'] ?>"><span class="glyphicon glyphicon-<?php echo $nav['icon'] ?>"></span> <?php echo $nav['label'] ?></a>
                    </li>
                    <?php } ?>
                  </ul>
                </div>
              </div>
            </div>
          </nav>
        </div>
        <div class="col-md-9 col-sm-8 col-xs-12">
          <?php
            // handle page actions
            switch( $page ) {
              case "wlan0_info":
                DisplayDashboard();
                break;
              case "dhcpd_conf":
                DisplayDHCPConfig();
                break;
              case "wpa_conf":
                DisplayWPAConfig();
                break;
              case "hostapd_conf":
                DisplayHostAPDConfig();
                break;
              case "openvpn_conf":
                DisplayOpenVPNConfig();
                break;
              case "torproxy_conf":
                DisplayTorProxyConfig();
                break;
              case "auth_conf":
                DisplayAuthConfig($config['admin_user'], $config['admin_pass']);
                break;
              case "save_hostapd_conf":
                SaveTORAndVPNConfig();
                break;
              case "system_info":
                DisplaySystem();
                break;
              default:
                DisplayDashboard();
            }
          ?>
        </div>
      </div>


    </div>
  </body>
</html>
