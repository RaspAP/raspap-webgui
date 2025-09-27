<?php

/**
 * Raspbian WiFi Configuration Portal (RaspAP)
 *
 * Simple AP setup & WiFi management for Debian-based devices.
 * Enables use of simple web interface rather than SSH to control WiFi and related services on the Raspberry Pi.
 * Recommended distribution is Raspberry Pi OS (64-bit) Lite. Specific instructions to install the supported software are
 * in the README and original post by @SirLagz. For a quick run through, the packages required for the WebGUI are:
 * lighttpd (version 1.4.69 installed via apt)
 * php-cgi (version 8.2.28 installed via apt)
 * along with their supporting packages, php8.2 will also need to be enabled.
 *
 * @author  Lawrence Yau <sirlagz@gmail.com>
 * @author  Bill Zimmerman <billzimmerman@gmail.com>
 * @license GNU General Public License, version 3 (GPL-3.0)
 * @version 3.4.3
 * @link    https://github.com/RaspAP/raspap-webgui/
 * @link    https://raspap.com/
 * @see     http://sirlagz.net/2013/02/08/raspap-webgui/
 *
 * You are not obligated to bundle the LICENSE file with your RaspAP projects as long
 * as you leave these references intact in the header comments of your source files.
 */

require_once 'includes/bootstrap.php';
require_once 'includes/config.php';
require_once 'includes/autoload.php';
$handler = new RaspAP\Exceptions\ExceptionHandler;

require_once 'includes/CSRF.php';
require_once 'includes/session.php';
require_once 'includes/defaults.php';
require_once 'includes/locale.php';
require_once 'includes/functions.php';

// Default page actions
require_once 'includes/dashboard.php';
require_once 'includes/login.php';
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
require_once 'includes/restapi.php';

initializeApp();
?>
<!DOCTYPE html>
<html lang="en" <?php setTheme();?>>
  <head>
    <meta charset="utf-8">
    <?php echo \RaspAP\Tokens\CSRF::metaTag(); ?>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="">
    <meta name="author" content="">

    <title><?php echo RASPI_BRAND_TITLE; ?></title>

    <!-- Bootstrap Core CSS -->
    <link href="dist/bootstrap/css/bootstrap.min.css?v=<?= filemtime('dist/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">

    <!-- SB-Admin CSS -->
    <link href="dist/sb-admin/css/styles.css?v=<?= filemtime('dist/sb-admin/css/styles.css'); ?>" rel="stylesheet">

    <!-- Huebee CSS -->
    <link href="dist/huebee/huebee.min.css?v=<?= filemtime('dist/huebee/huebee.min.css'); ?>" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="dist/font-awesome/css/all.min.css?v=<?= filemtime('dist/font-awesome/css/all.min.css'); ?>" rel="stylesheet" type="text/css">

    <!-- Librespeed CSS -->
    <link href="dist/speedtest/speedtest.css?v=<?= filemtime('dist/speedtest/speedtest.css'); ?>" rel="stylesheet" />

    <!-- RaspAP Fonts -->
    <link href="dist/raspap/css/style.css?v=<?= filemtime('dist/raspap/css/style.css'); ?>" rel="stylesheet" type="text/css">

    <!-- Custom CSS -->
    <link href="<?php echo $_SESSION["theme_url"]; ?>" title="main" rel="stylesheet">
    <link rel="icon" type="image/png" href="/app/icons/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/app/icons/favicon.svg" />
    <link rel="shortcut icon" href="/app/icons/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/app/icons/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="RaspAP" />
    <meta name="theme-color" content="#ffffff">
  </head>

  <body class="sb-nav-fixed">
    <!-- Navbar -->
    <?php ob_start(); ?>
    <?php require_once 'includes/navbar.php'; ?>
    <!-- End of Navbar -->
    <div id="layoutSidenav">
      <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-light" id="sidenavAccordion">
          <div class="sb-sidenav-menu">
            <div class="nav">
              <!-- Sidebar -->
              <?php require_once 'includes/sidebar.php'; ?>
              <!-- End of Sidebar -->
            </div>
          </div>
        </nav>
      </div>
      <div id="layoutSidenav_content">
        <main>
          <div class="container-fluid mt-2">
            <?php require_once 'includes/page_actions.php'; ?>
          </div>
        </main>
        <footer class="py-4 mt-auto">
          <div class="container-fluid px-4">
            <?php require_once 'includes/footer.php'; ?>
          </div>
        </footer>
      </div>
    </div>
    <?php ob_end_flush(); ?>
    <!-- jQuery -->
    <script src="dist/jquery/jquery.min.js?v=<?= filemtime('dist/jquery/jquery.min.js'); ?>"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="dist/bootstrap/js/bootstrap.bundle.min.js?v=<?= filemtime('dist/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>

    <!-- Core plugin JavaScript -->
    <script src="dist/jquery-easing/jquery.easing.min.js?v=<?= filemtime('dist/jquery-easing/jquery.easing.min.js'); ?>"></script>

    <!-- Chart.js JavaScript -->
    <script src="dist/chart.js/Chart.min.js?v=<?= filemtime('dist/chart.js/Chart.min.js'); ?>"></script>

    <!-- SB-Admin JavaScript -->
    <script src="dist/sb-admin/js/scripts.js?v=<?= filemtime('dist/sb-admin/js/scripts.js'); ?>"></script>

    <!-- jQuery Mask plugin -->
    <script src="dist/jquery-mask/jquery.mask.min.js?v=<?= filemtime('dist/jquery-mask/jquery.mask.min.js'); ?>"></script>

    <!-- Librespeed JavaScript -->
    <script src="dist/speedtest/speedtest.js?v=<?= filemtime('dist/speedtest/speedtest.js'); ?>"></script>

    <!-- RaspAP JavaScript -->
    <script src="app/js/ajax/main.js?v=<?= filemtime('app/js/ajax/main.js'); ?>"></script>
    <script src="app/js/ui/main.js?v=<?= filemtime('app/js/ui/main.js'); ?>"></script>

    <?php loadFooterScripts($extraFooterScripts); ?>
  </body>
</html>

