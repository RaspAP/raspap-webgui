<?php

/**
 * Raspbian WiFi Configuration Portal (RaspAP)
 *
 * Simple AP setup & WiFi management for Debian-based devices.
 * Enables use of simple web interface rather than SSH to control WiFi and related services  on the Raspberry Pi.
 * Recommended distribution is Raspberry Pi OS (32-bit) Lite. Specific instructions to install the supported software are
 * in the README and original post by @SirLagz. For a quick run through, the packages required for the WebGUI are:
 * lighttpd (version 1.4.53 installed via apt)
 * php-cgi (version 7.3.19-1 installed via apt)
 * along with their supporting packages, php7.3 will also need to be enabled.
 *
 * @author  Lawrence Yau <sirlagz@gmail.com>
 * @author  Bill Zimmerman <billzimmerman@gmail.com>
 * @license GNU General Public License, version 3 (GPL-3.0)
 * @version 2.6
 * @link    https://github.com/billz/raspap-webgui/
 * @link    https://raspap.com/
 * @see     http://sirlagz.net/2013/02/08/raspap-webgui/
 *
 * You are not obligated to bundle the LICENSE file with your RaspAP projects as long
 * as you leave these references intact in the header comments of your source files.
 */

require 'includes/csrf.php';
ensureCSRFSessionToken();

require_once 'includes/config.php';
require_once 'includes/defaults.php';
require_once RASPI_CONFIG.'/raspap.php';
require_once 'includes/locale.php';
require_once 'includes/functions.php';
require_once 'includes/dashboard.php';
require_once 'includes/authenticate.php';
require_once 'includes/admin.php';
require_once 'includes/dhcp.php';
require_once 'includes/hostapd.php';
require_once 'includes/adblock.php';
require_once 'includes/system.php';
require_once 'includes/sysstats.php';
require_once 'includes/configure_client.php';
require_once 'includes/networking.php';
require_once 'includes/themes.php';
require_once 'includes/data_usage.php';
require_once 'includes/about.php';
require_once 'includes/openvpn.php';
require_once 'includes/torproxy.php';

$output = $return = 0;
$page = $_GET['page'];

$theme_url = getThemeOpt();
$toggleState = getSidebarState();
$bridgedEnabled = getBridgedState();

?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <?php echo CSRFMetaTag() ?>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title><?php echo _("RaspAP WiFi Configuration Portal"); ?></title>

    <!-- Bootstrap Core CSS -->
    <link href="dist/bootstrap/css/bootstrap.css" rel="stylesheet">

    <!-- SB-Admin-2 CSS -->
    <link href="dist/sb-admin-2/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="dist/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <!-- Huebee CSS -->
    <link href="dist/huebee/huebee.min.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="dist/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Custom CSS -->
    <link href="<?php echo $theme_url; ?>" title="main" rel="stylesheet">

    <link rel="shortcut icon" type="image/png" href="app/icons/favicon.png?ver=2.0">
    <link rel="apple-touch-icon" sizes="180x180" href="app/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="app/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="app/icons/favicon-16x16.png">
    <link rel="icon" type="image/png" href="app/icons/favicon.png" />
    <link rel="manifest" href="app/icons/site.webmanifest">
    <link rel="mask-icon" href="app/icons/safari-pinned-tab.svg" color="#b91d47">
    <meta name="msapplication-config" content="app/icons/browserconfig.xml">
    <meta name="msapplication-TileColor" content="#b91d47">
    <meta name="theme-color" content="#ffffff">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
      <!-- Sidebar -->
      <ul class="navbar-nav sidebar sidebar-light d-none d-md-block accordion <?php echo (isset($toggleState)) ? $toggleState : null ; ?>" id="accordionSidebar">
        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php?page=wlan0_info">
          <div class="sidebar-brand-text ml-1"><?php echo RASPI_BRAND_TEXT; ?></div>
        </a>
        <!-- Divider -->
        <hr class="sidebar-divider my-0">
        <div class="row">
          <div class="col-xs ml-3 sidebar-brand-icon">
            <img src="app/img/raspAP-logo.php" class="navbar-logo" width="64" height="64">
          </div>
          <div class="col-xs ml-2">
            <div class="ml-1">Status</div>
            <div class="info-item-xs"><span class="icon">
              <i class="fas fa-circle <?php echo ($hostapd_led); ?>"></i></span> <?php echo _("Hotspot").' '. _($hostapd_status); ?>
            </div>
            <div class="info-item-xs"><span class="icon">
              <i class="fas fa-circle <?php echo ($memused_led); ?>"></i></span> <?php echo _("Memory Use").': '. htmlspecialchars($memused, ENT_QUOTES); ?>%
            </div>
            <div class="info-item-xs"><span class="icon">
              <i class="fas fa-circle <?php echo ($cputemp_led); ?>"></i></span> <?php echo _("CPU Temp").': '. htmlspecialchars($cputemp, ENT_QUOTES); ?>°C
            </div>
          </div>
        </div>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=wlan0_info"><i class="fas fa-tachometer-alt fa-fw mr-2"></i><span class="nav-label"><?php echo _("Dashboard"); ?></span></a>
        </li>
          <?php if (RASPI_HOTSPOT_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=hostapd_conf"><i class="far fa-dot-circle fa-fw mr-2"></i><span class="nav-label"><?php echo _("Hotspot"); ?></a>
        </li>
          <?php endif; ?>
          <?php if (RASPI_DHCP_ENABLED && !$bridgedEnabled) : ?>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=dhcpd_conf"><i class="fas fa-exchange-alt fa-fw mr-2"></i><span class="nav-label"><?php echo _("DHCP Server"); ?></a>
        </li>
          <?php endif; ?>
          <?php if (RASPI_ADBLOCK_ENABLED && !$bridgedEnabled) : ?>
        <li class="nav-item">
           <a class="nav-link" href="index.php?page=adblock_conf"><i class="far fa-hand-paper fa-fw mr-2"></i><span class="nav-label"><?php echo _("Ad Blocking"); ?></a>
        </li>
          <?php endif; ?>
          <?php if (RASPI_NETWORK_ENABLED) : ?>
        <li class="nav-item">
           <a class="nav-link" href="index.php?page=network_conf"><i class="fas fa-network-wired fa-fw mr-2"></i><span class="nav-label"><?php echo _("Networking"); ?></a>
        </li> 
          <?php endif; ?>
          <?php if (RASPI_WIFICLIENT_ENABLED && !$bridgedEnabled) : ?>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=wpa_conf"><i class="fas fa-wifi fa-fw mr-2"></i><span class="nav-label"><?php echo _("WiFi client"); ?></span></a>
        </li>
          <?php endif; ?>
          <?php if (RASPI_OPENVPN_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=openvpn_conf"><i class="fas fa-key fa-fw mr-2"></i><span class="nav-label"><?php echo _("OpenVPN"); ?></a>
        </li>
          <?php endif; ?>
          <?php if (RASPI_TORPROXY_ENABLED) : ?>
        <li class="nav-item">
           <a class="nav-link" href="index.php?page=torproxy_conf"><i class="fas fa-eye-slash fa-fw mr-2"></i><span class="nav-label"><?php echo _("TOR proxy"); ?></a>
        </li>
          <?php endif; ?>
          <?php if (RASPI_CONFAUTH_ENABLED) : ?>
        <li class="nav-item">
        <a class="nav-link" href="index.php?page=auth_conf"><i class="fas fa-user-lock fa-fw mr-2"></i><span class="nav-label"><?php echo _("Authentication"); ?></a>
        </li>
          <?php endif; ?>
          <?php if (RASPI_CHANGETHEME_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=theme_conf"><i class="fas fa-paint-brush fa-fw mr-2"></i><span class="nav-label"><?php echo _("Change Theme"); ?></a>
        </li>
          <?php endif; ?>
          <?php if (RASPI_VNSTAT_ENABLED) : ?>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=data_use"><i class="fas fa-chart-bar fa-fw mr-2"></i><span class="nav-label"><?php echo _("Data usage"); ?></a>
        </li>
          <?php endif; ?>
            <?php if (RASPI_SYSTEM_ENABLED) : ?>
          <li class="nav-item">
          <a class="nav-link" href="index.php?page=system_info"><i class="fas fa-cube fa-fw mr-2"></i><span class="nav-label"><?php echo _("System"); ?></a>
          </li>
            <?php endif; ?>
         <li class="nav-item">
          <a class="nav-link" href="index.php?page=about"><i class="fas fa-info-circle fa-fw mr-2"></i><span class="nav-label"><?php echo _("About RaspAP"); ?></a>
        </li>
        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">

        <!-- Sidebar Toggler (Sidebar) -->
        <div class="text-center d-none d-md-block">
          <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>

    </ul>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">
      <!-- Topbar -->
      <nav class="navbar navbar-expand navbar-light topbar mb-1 static-top">
        <!-- Sidebar Toggle (Topbar) -->
        <button id="sidebarToggleTopbar" class="btn btn-link d-md-none rounded-circle mr-3">
          <i class="fa fa-bars"></i>
        </button>
        <!-- Topbar Navbar -->
        <p class="text-left brand-title mt-3 ml-2"><?php //echo _("WiFi Configuration Portal"); ?></p>
        <ul class="navbar-nav ml-auto">
          <div class="topbar-divider d-none d-sm-block"></div>
          <!-- Nav Item - User -->
          <li class="nav-item dropdown no-arrow">
          <a class="nav-link" href="index.php?page=auth_conf">
            <span class="mr-2 d-none d-lg-inline small"><?php echo htmlspecialchars($config['admin_user'], ENT_QUOTES); ?></span>
            <i class="fas fa-user-circle fa-3x"></i>
          </a>
          </li>
        </ul>
      </nav>
      <!-- End of Topbar -->
      <!-- Begin Page Content -->
      <div class="container-fluid">
      <?php
        $extraFooterScripts = array();
        // handle page actions
        switch ($page) {
        case "wlan0_info":
            DisplayDashboard($extraFooterScripts);
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
        case "adblock_conf":
            DisplayAdBlockConfig();
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
            DisplayThemeConfig($extraFooterScripts);
            break;
        case "data_use":
            DisplayDataUsage($extraFooterScripts);
            break;
        case "system_info":
            DisplaySystem();
            break;
        case "about":
            DisplayAbout();
            break;
        default:
            DisplayDashboard($extraFooterScripts);
        }
        ?>
      </div><!-- /.container-fluid -->
    </div><!-- End of Main Content -->
    <!-- Footer -->
    <footer class="sticky-footer bg-grey-100">
      <div class="container my-auto">
        <div class="copyright text-center my-auto">
          <span></span>
        </div>
      </div>
    </footer>
    <!-- End Footer -->
    </div><!-- End of Content Wrapper -->
    </div><!-- End of Page Wrapper -->
    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top" style="display: inline;">
      <i class="fas fa-angle-up"></i>
    </a> 

    <!-- jQuery -->
    <script src="dist/jquery/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="dist/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript -->
    <script src="dist/jquery-easing/jquery.easing.min.js"></script>

    <!-- Chart.js JavaScript -->
    <script src="dist/chart.js/Chart.min.js"></script>

    <!-- SB-Admin-2 JavaScript -->
    <script src="dist/sb-admin-2/js/sb-admin-2.js"></script>

    <!-- Custom RaspAP JS -->
    <script src="app/js/custom.js"></script>

    <?php
    // Load non default JS/ECMAScript in footer.
    foreach ($extraFooterScripts as $script) {
        echo '<script type="text/javascript" src="' , $script['src'] , '"';
        if ($script['defer']) {
            echo ' defer="defer"';
        }
        echo '></script>' , PHP_EOL;
    }
    ?>
  </body>
</html>
