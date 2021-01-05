<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    {!! CSRFMetaTag() !!}
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>{{ _("RaspAP WiFi Configuration Portal") }}</title>

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
    <link href="{!! $theme_url !!}" title="main" rel="stylesheet">

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
      <ul class="navbar-nav sidebar sidebar-light d-none d-md-block accordion {!! $toggleState ?? "" !!}" id="accordionSidebar">
        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="wlan0_info">
          <div class="sidebar-brand-text ml-1">{{RASPI_BRAND_TEXT}}</div>
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
              <i class="fas fa-circle {{$hostapd_led}}"></i></span> {{ _("Hotspot").' '. _($hostapd_status) }}
            </div>
            <div class="info-item-xs"><span class="icon">
              <i class="fas fa-circle {{$memused_led}}"></i></span> {{ _("Memory Use").': '. $memused }}%
            </div>
            <div class="info-item-xs"><span class="icon">
              <i class="fas fa-circle {{$cputemp_led}}"></i></span> {{ _("CPU Temp").': '. $cputemp }}°C
            </div>
          </div>
        </div>
        <li class="nav-item">
          <a class="nav-link" href="wlan0_info"><i class="fas fa-tachometer-alt fa-fw mr-2"></i><span class="nav-label">{{ _("Dashboard") }}</span></a>
        </li>
          @if (RASPI_HOTSPOT_ENABLED)
        <li class="nav-item">
          <a class="nav-link" href="hostapd_conf"><i class="far fa-dot-circle fa-fw mr-2"></i><span class="nav-label">{{ _("Hotspot") }}</a>
        </li>
          @endif
          @if (RASPI_DHCP_ENABLED && !$bridgedEnable)
        <li class="nav-item">
          <a class="nav-link" href="dhcpd_conf"><i class="fas fa-exchange-alt fa-fw mr-2"></i><span class="nav-label">{{ _("DHCP Server") }}</a>
        </li>
          @endif
          @if (RASPI_ADBLOCK_ENABLED && !$bridgedEnable)
        <li class="nav-item">
           <a class="nav-link" href="adblock_conf"><i class="far fa-hand-paper fa-fw mr-2"></i><span class="nav-label">{{ _("Ad Blocking") }}</a>
        </li>
          @endif
          @if (RASPI_NETWORK_ENABLED)
        <li class="nav-item">
           <a class="nav-link" href="network_conf"><i class="fas fa-network-wired fa-fw mr-2"></i><span class="nav-label">{{ _("Networking") }}</a>
        </li> 
          @endif
          @if (RASPI_WIFICLIENT_ENABLED && !$bridgedEnable)
        <li class="nav-item">
          <a class="nav-link" href="wpa_conf"><i class="fas fa-wifi fa-fw mr-2"></i><span class="nav-label">{{ _("WiFi client") }}</span></a>
        </li>
          @endif
          @if (RASPI_OPENVPN_ENABLED)
        <li class="nav-item">
          <a class="nav-link" href="openvpn_conf"><i class="fas fa-key fa-fw mr-2"></i><span class="nav-label">{{ _("OpenVPN") }}</a>
        </li>
          @endif
          @if (RASPI_TORPROXY_ENABLED)
        <li class="nav-item">
           <a class="nav-link" href="torproxy_conf"><i class="fas fa-eye-slash fa-fw mr-2"></i><span class="nav-label">{{ _("TOR proxy") }}</a>
        </li>
          @endif
          @if (RASPI_CONFAUTH_ENABLED)
        <li class="nav-item">
        <a class="nav-link" href="auth_conf"><i class="fas fa-user-lock fa-fw mr-2"></i><span class="nav-label">{{ _("Authentication") }}</a>
        </li>
          @endif
          @if (RASPI_CHANGETHEME_ENABLED)
        <li class="nav-item">
          <a class="nav-link" href="theme_conf"><i class="fas fa-paint-brush fa-fw mr-2"></i><span class="nav-label">{{ _("Change Theme") }}</a>
        </li>
          @endif
          @if (RASPI_VNSTAT_ENABLED)
        <li class="nav-item">
          <a class="nav-link" href="data_use"><i class="fas fa-chart-bar fa-fw mr-2"></i><span class="nav-label">{{ _("Data usage") }}</a>
        </li>
          @endif
            @if (RASPI_SYSTEM_ENABLED)
          <li class="nav-item">
          <a class="nav-link" href="system_info"><i class="fas fa-cube fa-fw mr-2"></i><span class="nav-label">{{ _("System") }}</a>
          </li>
            @endif
         <li class="nav-item">
          <a class="nav-link" href="about"><i class="fas fa-info-circle fa-fw mr-2"></i><span class="nav-label">{{ _("About RaspAP") }}</a>
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
        <p class="text-left brand-title mt-3 ml-2"> {{-- {{_("WiFi Configuration Portal")}} --}}</p>
        <ul class="navbar-nav ml-auto">
          <div class="topbar-divider d-none d-sm-block"></div>
          <!-- Nav Item - User -->
          <li class="nav-item dropdown no-arrow">
          <a class="nav-link" href="auth_conf">
            <span class="mr-2 d-none d-lg-inline small">{{$config['admin_user']}}</span>
            <i class="fas fa-user-circle fa-3x"></i>
          </a>
          </li>
        </ul>
      </nav>
      <!-- End of Topbar -->
      <!-- Begin Page Content -->
      <div class="container-fluid">
        @yield('content')
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

    <!-- Footer scripts specific to this page -->
    @yield('footer_scripts')

  </body>
</html>
