<?php header("Content-Type: text/css; charset=utf-8"); ?>
<?php
require_once '../../includes/functions.php';
$color = getColorOpt();
?>

/*
Theme Name: RaspAP default
Author: @billz
Author URI: https://github.com/billz
Description: Default theme for RaspAP
License: GNU General Public License v3.0
*/

body {
  color: #212529;
}

.page-header {
  margin: 20px 0 20px;
}

.navbar-logo {
  margin-top: 0.5em;
  margin-left: 0.5em;
}

/* Small devices (portrait phones, up to 576px) */
@media (max-width: 576px) {
  .container-fluid, .card-body, .col-md-6 { padding-left: 0.5rem; padding-right: 0.5rem; }
  .card .card-header { padding: .75rem .5rem; font-size: 1.0rem; }
  .row { margin-left: 0rem; margin-right: 0rem; }
  .col-lg-12 { padding-right: 0.25rem; padding-left: 0.25rem; }
  .form-group.col-md-6 { margin-left: -0.5rem; }
  .js-wifi-stations { margin-left: -0.5rem; margin-right: -0.5rem; }
  h4.mt-3 { margin-left: 0.5rem; }
}

.sidebar {
  background-color: #f8f9fc;
}

.sidebar-brand-text {
  text-transform: none;
  color: #212529;
  font-size: 2.0rem;
  font-weight: 500;
  font-family: Helvetica, Arial, sans-serif;
}

.sidebar .nav-item.active .nav-link {
  font-weight: 500;
}

.card .card-header, .modal-header {
  border-color: <?php echo $color; ?>;
  color: #fff;
  background-color: <?php echo $color; ?>;
}

.modal-header {
  border-radius: 0px;
}

.btn-primary {
  color: <?php echo $color; ?>;
  border-color: <?php echo $color; ?>;
  background-color: #fff;
}

.card-footer, .modal-footer {
  background-color: #f2f1f0;
}

.nav-item {
  font-size: 0.85rem;
}

.nav-tabs .nav-link.active,
.nav-tabs .nav-link {
  font-size: 1.0rem;
}

.nav-tabs a.nav-link {
  color: #6e707e;
}

a.nav-link.active {
  font-weight: bolder;
}

.sidebar .nav-item .nav-link {
  padding: 0.6rem 0.6rem 0.6rem 1.0rem;
}

.alert-success {
  background-color: #d4edda;
}

.btn-primary {
  background-color: #fff;
}

.btn-warning {
  color: #333;
}

.btn-primary:hover {
  background-color: <?php echo $color; ?>;
  border-color: <?php echo $color; ?>;
}

i.fa.fa-bars {
  color: #d1d3e2;
}

i.fa.fa-bars:hover{
  color: #6e707e;
}

.info-item {
  width: 10rem;
  float: left;
}

.info-item-xs {
  font-size: 0.7rem;
  margin-left: 0.3rem;
}

.info-item-wifi {
  width: 6rem;
  float: left;
}

.service-status {
  border-width: 0;
}

.service-status-up {
  color: #a1ec38;
}

.service-status-warn {
  color: #f6f044;
}

.service-status-down {
  color: #f80107;
  animation: flash 1s linear infinite;
}
@keyframes flash {
  50% {
    opacity: 0;
  }
}
 
.logoutput {
  width:100%;
  height: 20rem;
  border: 1px solid #d1d3e2;
  border-radius: .35rem;
}

pre.unstyled {
  border-width: 0;
  background-color: transparent;
  padding: 0;
}

.dhcp-static-leases {
  margin-top: 1em;
  margin-bottom: 1em;
}

.dhcp-static-lease-row {
  margin-top: 0.5em;
  margin-bottom: 0.5em;
}

.loading-spinner {
  background: url("../../app/img/loading-spinner.gif") no-repeat scroll center center transparent;
  min-height: 150px;
  width: 100%;
}

.js-reload-wifi-stations {
  min-width: 10rem;
}

.sidebar.toggled .nav-item .nav-link span {
  display: none;
} .sidebar .nav-item .nav-link i,
.sidebar .nav-item .nav-link span {
    font-size: 1.0rem;
}

.btn-warning:hover {
    color: #000;
}

.toggle-off.btn {
  padding-left: 1.2rem;
  font-size: 0.9rem!important;
}

.toggle-on.btn {
  font-size: 0.9rem!important;
}

canvas#divDBChartBandwidthhourly {
  height: 350px!important;
}

.chart-container {
  height: 150px;
  width: 200px;
}

.table {
  margin-bottom: 0rem;
}

.check-hidden {
  visibility: hidden;
}

.check-updated {
  opacity: 0;
  color: #90ee90;
}

.check-progress {
  color: #999;
}

.fa-check {
  color: #90ee90;
}

.fa-times {
  color: #ff4500;
}

