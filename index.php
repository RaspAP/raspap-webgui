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
 * @author     Lawrence Yau <sirlagz@gmail.com>
 * @author     Bill Zimmerman <billzimmerman@gmail.com>
 * @license    GNU General Public License, version 3 (GPL-3.0)
 * @version    1.3.0
 * @link       https://github.com/billz/raspap-webgui
 * @see        http://sirlagz.net/2013/02/08/raspap-webgui/
 */

include_once( 'includes/config.php' );
include_once( RASPI_CONFIG.'/raspap.php' );
include_once( 'includes/functions.php' );
include_once( 'includes/dashboard.php' );
include_once( 'includes/authenticate.php' );
include_once( 'includes/admin.php' );
include_once( 'includes/dhcp.php' );
include_once( 'includes/hostapd.php' );
include_once( 'includes/system.php' );
include_once( 'includes/configure_client.php' );
include_once( 'includes/networking.php' );
include_once( 'includes/themes.php' );

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

if(!isset($_COOKIE['theme'])) {
    $theme = "custom.css";
} else {
    $theme = $_COOKIE['theme'];
}
$theme_url = 'dist/css/' . $theme;
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Raspbian WiFi Configuration Portal</title>

    <!-- Bootstrap Core CSS -->
    <link href="bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="bower_components/metisMenu/dist/metisMenu.min.css" rel="stylesheet">

    <!-- Timeline CSS -->
    <link href="dist/css/timeline.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="dist/css/sb-admin-2.css" rel="stylesheet">

    <!-- Morris Charts CSS -->
    <link href="bower_components/morrisjs/morris.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="bower_components/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- Custom CSS -->
    <link href="<?php echo $theme_url; ?>" title="main" rel="stylesheet">

    <link rel="shortcut icon" type="image/png" href="../img/favicon.png">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>

    <div id="wrapper">
      <!-- Navigation -->
      <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php">RaspAP Wifi Portal v1.3.0</a>
        </div>
        <!-- /.navbar-header -->

        <!-- Navigation -->
        <div class="navbar-default sidebar" role="navigation">
          <div class="sidebar-nav navbar-collapse">
            <ul class="nav" id="side-menu">
              <li>
                <a href="index.php?page=wlan0_info"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
              </li>
              <li>
                <a href="index.php?page=wpa_conf"><i class="fa fa-signal fa-fw"></i> Configure WiFi Client</a>
              </li>
              <?php if ( RASPI_HOTSPOT_ENABLED ) : ?>
              <li>
                <a href="index.php?page=hostapd_conf"><i class="fa fa-dot-circle-o fa-fw"></i> Configure Hotspot</a>
              </li>
              <?php endif; ?>
              <?php if ( RASPI_NETWORK_ENABLED ) : ?>
              <li>
                <a href="index.php?page=network_conf"><i class="fa fa-sitemap fa-fw"></i> Configure Networking</a>
              </li> 
              <?php endif; ?>
              <?php if ( RASPI_DHCP_ENABLED ) : ?>
              <li>
                <a href="index.php?page=dhcpd_conf"><i class="fa fa-exchange fa-fw"></i> Configure DHCP Server</a>
              </li>
              <?php endif; ?>
              <?php if ( RASPI_OPENVPN_ENABLED ) : ?>
              <li>
                <a href="index.php?page=openvpn_conf"><i class="fa fa-lock fa-fw"></i> Configure OpenVPN</a>
              </li>
              <?php endif; ?>
              <?php if ( RASPI_TORPROXY_ENABLED ) : ?>
              <li>
                 <a href="index.php?page=torproxy_conf"><i class="fa fa-eye-slash fa-fw"></i> Configure TOR proxy</a>
              </li>
              <?php endif; ?>
              <?php if ( RASPI_CONFAUTH_ENABLED ) : ?>
              <li>
                <a href="index.php?page=auth_conf"><i class="fa fa-lock fa-fw"></i> Configure Auth</a>
              </li>
              <?php endif; ?>
              <?php if ( RASPI_CHANGETHEME_ENABLED ) : ?>
              <li>
                <a href="index.php?page=theme_conf"><i class="fa fa-wrench fa-fw"></i> Change Theme</a>
              </li>
              <?php endif; ?>
              <li>
                 <a href="index.php?page=system_info"><i class="fa fa-cube fa-fw"></i> System</a>
              </li>
            </ul>
          </div><!-- /.navbar-collapse -->
        </div><!-- /.navbar-default -->
      </nav>

      <div id="page-wrapper">

        <!-- Page Heading -->
        <div class="row">
          <div class="col-lg-12">
            <h1 class="page-header">
              <img class="logo" src="img/raspAP-logo.png" width="45" height="45">RaspAP
            </h1>
          </div>
        </div><!-- /.row -->

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
          case "network_conf":
            DisplayNetworkingConfig();
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
          case "theme_conf":
            DisplayThemeConfig();
            break;
          case "system_info":
            DisplaySystem();
            break;
          default:
            DisplayDashboard();
        }
        ?>
      </div><!-- /#page-wrapper --> 
    </div><!-- /#wrapper -->

    <!-- RaspAP JavaScript -->
    <script src="dist/js/functions.js"></script>

    <!-- jQuery -->
    <script src="bower_components/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="bower_components/metisMenu/dist/metisMenu.min.js"></script>

    <!-- Morris Charts JavaScript -->
    <!--script src="bower_components/raphael/raphael-min.js"></script-->
    <!--script src="bower_components/morrisjs/morris.min.js"></script-->
    <!--script src="js/morris-data.js"></script-->

    <!-- Custom Theme JavaScript -->
    <script src="dist/js/sb-admin-2.js"></script>

    <!-- Custom RaspAP JS -->
    <script src="js/custom.js"></script>
  </body>
</html>
