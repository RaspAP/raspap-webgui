<?php

/**
 * Raspbian WiFi Configuration Portal (RaspAP)
 *
 * Simple AP setup & WiFi management for Debian-based devices.
 * Enables use of simple web interface rather than SSH to control WiFi and related services on the Raspberry Pi.
 * Recommended distribution is Raspberry Pi OS (64-bit) Lite. Specific instructions to install the supported software are
 * in the README and original post by @SirLagz. For a quick run through, the packages required for the WebGUI are:
 * lighttpd (version 1.4.69 installed via apt)
 * php-cgi (version 8.2.7 installed via apt)
 * along with their supporting packages, php7.4 will also need to be enabled.
 *
 * @author  Lawrence Yau <sirlagz@gmail.com>
 * @author  Bill Zimmerman <billzimmerman@gmail.com>
 * @license GNU General Public License, version 3 (GPL-3.0)
 * @version 3.0.8
 * @link    https://github.com/RaspAP/raspap-webgui/
 * @link    https://raspap.com/
 * @see     http://sirlagz.net/2013/02/08/raspap-webgui/
 *
 * You are not obligated to bundle the LICENSE file with your RaspAP projects as long
 * as you leave these references intact in the header comments of your source files.
 */

require 'includes/csrf.php';
ensureCSRFSessionToken();

require_once 'includes/exceptions.php';
require_once 'includes/config.php';
require_once 'includes/autoload.php';
require_once 'includes/defaults.php';
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
require_once 'includes/data_usage.php';
require_once 'includes/about.php';
require_once 'includes/openvpn.php';
require_once 'includes/wireguard.php';
require_once 'includes/provider.php';
require_once 'includes/torproxy.php';

initializeApp();
?>
<!DOCTYPE html>
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

    <!-- RaspAP Fonts -->
    <link href="dist/raspap/css/style.css" rel="stylesheet" type="text/css">

    <!-- Custom CSS -->
    <link href="<?php echo $_SESSION["theme_url"]; ?>" title="main" rel="stylesheet">
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
      <?php require_once 'includes/sidebar.php'; ?>
      <!-- End of Sidebar -->
      <!-- Content Wrapper -->
      <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
          <!-- Topbar -->
          <?php require_once 'includes/navbar.php'; ?>
          <!-- End of Topbar -->
          <!-- Begin Page Content -->
          <div class="container-fluid">
          <?php require_once 'includes/page_actions.php'; ?>
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

    <!-- jQuery Mask plugin -->
    <script src="dist/jquery-mask/jquery.mask.min.js"></script>

    <!-- Custom RaspAP JS -->
    <script src="app/js/custom.js"></script>

    <?php loadFooterScripts($extraFooterScripts); ?>
  </body>
</html>
