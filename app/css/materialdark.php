<?php header("Content-Type: text/css; charset=utf-8"); ?>
<?php
require_once '../../includes/functions.php';
$color = getColorOpt();
?>

/*
Theme Name: Material Dark
Author: @marek-guran
Author URI: https://github.com/marek-guran
Description: Inspired by Google's Material Design
License: GNU General Public License v3.0
*/

@import url('all.css');

body {
  background-color: #202020;
}

html * {
  font-family: Helvetica,Arial,sans-serif;
  color: #afafaf;
}

h2 {
  font-size: 2rem !important;
}

h4 {
  font-size: 1.3rem;
}

h5.card-title {
  font-size: 1.2rem;
}

.page-header {
  border-left: .01rem solid #d2d2d2;
  border-bottom: .01rem solid #d2d2d2;
}

.sidebar-light .nav-item.active .nav-link i {
  color: #d2d2d2;
}

.sidebar .nav-item.active .nav-link {
  font-weight: 400;
}

#wrapper #content-wrapper #content {
  background-color: #202020;
}

.topbar {
  background-color: #202020;
}

.col {
  color: white;
}

.card-header .col i.fa-tachometer-alt {
  color: white!important;
}

.card-header .col i.fa-dot-circle {
  color: white!important;
}

.card-header .col i.fa-wifi {
  color: white!important;
}

.card-header .col i.fa-exchange-alt {
  color: white!important;
}

.card-header .col i.fa-hand-paper {
  color: white!important;
}

.card-header .col i.fa-network-wired {
  color: white!important;
}

.card-header .col i.fa-key {
  color: white!important;
}

.card-header .ra-wireguard {
  color: white!important;
}

.card-header .ra-wireguard:before {
  color: white!important;
}

.card-header .col i.fa-user-lock {
  color: white!important;
}

.card-header .col i.fa-chart-bar {
  color: white!important;
}

.card-header .col i.fa-cube {
  color: white!important;
}

.card-header .col i.fa-info-circle {
  color: white!important;
}

.nav-tabs {
  border-bottom: 1px solid #404040;
}
.nav-tabs .nav-link.active,
.nav-tabs .nav-link {
  font-size: 1.0rem;
  border-top-left-radius: 18px;
  border-top-right-radius: 18px;
}

.nav-tabs .nav-link:hover {
  border-color: transparent;
}

.navbar-default .navbar-brand:hover {
  color: #d2d2d2;
}

.navbar-default .navbar-toggle {
  border-color: #d2d2d2;
}

.navbar-default .navbar-toggle .icon-bar {
  background-color: #d2d2d2;
}

.navbar-default .navbar-toggle:focus,
.navbar-default .navbar-toggle:hover {
  background-color: #202020;
}

#content, .navbar, .sidebar, .footer, .sticky-footer {
  background-attachment: scroll;
  background-repeat: repeat;
  background-size: auto;
  background-position: 0 0;
  background-origin: padding-box;
  background-clip: border-box;
}

.sticky-footer {
  background-position: 30px 0;
}

.sidebar {
  background-position: 0 20px;
}

.nav-tabs .nav-link.active {
  color: #d2d2d2;
  background-color: #141414;
  border-color: #404040 #404040 #141414;
}

a:focus, a:hover {
  color: #d2d2d2;
}

.card>.card-header, .modal-content, .modal-header {
  border-color: <?php echo $color; ?>;
  background-color: <?php echo $color; ?>;
  color: #afafaf;
  border-radius: 18px;
  font-size: 1.0rem;
  font-weight: 400;
}

.modal-body {
  background-color: #141414;
}

.card-header {
  border-bottom-left-radius: 0px!important;
  border-bottom-right-radius: 0px!important;
}

.card>.card-header .fa {
  color: #202020;
}

.card-header [class^="fa"] {
  color: #afafaf;
  font-size: 1.0rem;
}

.card, .card-body {
  border-color: #343434;
  border-radius: 18px;
  background-color: #141414;
}

hr { 
  border-top: .01rem solid #d2d2d2;
}

.sidebar-brand-text {
  color: <?php echo $color; ?>;
}

.ra-raspap:before {
  color: #ac1b3d !important;
}

.sidebar-light #sidebarToggle {
  background-color: #202020;
  border: 1px solid #afafaf !important
}

.sidebar-light #sidebarToggle::after {
  color: #afafaf;
}

.sidebar-light .nav-item .nav-link:hover i {
  color: #d2d2d2;
}

.sidebar-light #sidebarToggle:hover {
  background-color: #202020;
}

.sidebar.toggled .nav-item .nav-link span {
  display: none;
}

.sidebar.toggled .nav-item .nav-link {
  text-align: center;
  padding: .6rem 1rem;
  width: 6.5rem;
}

.card-footer, .modal-footer {
  background-color: #202020;
  border-top: 0px;
  border-bottom-right-radius: 18px!important;
  border-bottom-left-radius: 18px!important;
}

.modal-footer {
  border-radius: 18px;
}

.card>.card-header::before, .navbar-default::before {
  content: " ";
  display: block;
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  z-index: 2;
  background-size: 100% 2px, 3px 100%;
  pointer-events: none;
}

.sidebar-light, .sticky-footer {
  background-color: #202020;
}

.sidebar-light .nav-item .nav-link i {
  color: rgba(230, 230, 230, .3);
}

.sidebar .nav-item .nav-link {
  padding: 0.6rem;
  padding-left: 1.2rem;
}

.sidebar-light hr.sidebar-divider {
  border-top: 1px solid #404040;
  padding-top: 0.5rem;
}

.sidebar .nav-item .nav-link span {
  font-size: 1.0rem;
}

.topbar .topbar-divider {
  border-right: 1px solid #404040;
}

.label-warning {
  background-color: #d2d2d2;
}

span.label.label-warning {
  color: #202020;
}

.table>tbody>tr>td,
.table>tbody>tr>th,
.table>tfoot>tr>td,
.table>tfoot>tr>th,
.table>thead>tr>td,
.table>thead>tr>th {
  background-color: #202020;
  border-top: .01rem solid #202020;
}

.table{
  border-radius: 18px;
  overflow: hidden;
}

.table>thead>tr>th {
  vertical-align: bottom;
  border-bottom: 0 solid #d2d2d2;
}

[class*="btn"], [class*="btn"]:focus, [class*="btn"]:disabled {
  background-color: #202020;
  border-color: #404040;
  border-radius: 18px;
  color: #d2d2d2;
}

[class*="btn"]:hover {
  border-radius: 18px;
  color: #d2d2d2;
  background-color: #202020;
  border-color: #afafaf;
}

[class*="btn"]:hover .disabled {
  background-color:red;
}

[class*="alert"] {
  border-radius: 18px;
  color: #d2d2d2;
  background-color: #202020;
  border: 1px solid #404040;
}

.close {
  font-size: 1.2em;
  font-weight: 400;
  text-shadow: none;
  color: #d2d2d2;
}

.form-control,
.form-control:focus,
.custom-select {
  color: #d2d2d2;
  background-color: #202020;
  border: 1px solid #404040;
  border-radius: 18px;
}

.form-control:disabled,
.form-control[readonly] {
  background-color: #202020;
  opacity: 0.5;
}

.form-control::-webkit-input-placeholder { color: #d2d2d2; } 
.form-control:-moz-placeholder { color: #d2d2d2; }  
.form-control::-moz-placeholder { color: #d2d2d2; }  
.form-control:-ms-input-placeholder { color: #d2d2d2; }  
.form-control::-ms-input-placeholder { color: #d2d2d2; }

.form-control option {
  font-size: 1em;
}

input[type="text"]{
color: #d2d2d2 !important
}

.progress {
  background-color: #202020;
  border-radius: 18px;
}

.progress-bar {
  color: #202020;
}

.progress .progress-bar {
  padding-left: 5px;
}

.progress-bar.progress-bar-info.progress-bar-striped.active {
  background-color: #d2d2d2;
}

.figure-img {
  filter: opacity(0.7);
}

.ra-wireguard:before {
  color: #404040 !important;
}

.ra-wireguard:hover:before {
  color: #d1d3e2 !important;
}

.sidebar .nav-item.active .nav-link span.ra-wireguard:before {
    color: #d2d2d2 !important;
}

.logoutput {
  background-color: #202020;
  border-color: #404040;
}

.text-muted {
  font-size: 0.8rem;
}

.fas.fa-circle {
  font-size: 0.7rem;
}

pre {
  background-color: #202020;
  border: #202020;
}

button.btn.btn-light.js-toggle-password {
  border: 1px solid #343434;
}

.btn-primary {
  color: <?php echo $color; ?>;
  border-color: <?php echo $color; ?>;
  background-color: #fff;
}

.btn-primary:hover {
  background-color: <?php echo $color; ?>;
  border-color: <?php echo $color; ?>;
}

.signal-icon .signal-bar {
  background: #2b8080;
}

.figure-img {
  border-radius: 18px;
}

.logoutput {
  border-radius: 18px!important;
}

.btn-sm {
  border-top-right-radius: 18px!important;
  border-bottom-right-radius: 18px!important;
}

.signal-icon .signal-bar {
  background: <?php echo $color; ?>;
}
