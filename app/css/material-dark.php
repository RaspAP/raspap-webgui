<?php header("Content-Type: text/css; charset=utf-8"); ?>
<?php
require_once '../../includes/functions.php';
$color = getColorOpt();
?>

/*
Theme Name: Material Dark
Author: @marek-guran
Author URI: https://github.com/marek-guran
Description: Inspired by Google's Material You Design
License: GNU General Public License v3.0
*/

<?php
// Base color
$baseColor = $color;

// Function to darken a color by a percentage
function darkenColor($color, $percent)
{
    $percent /= 100;
    $r = hexdec(substr($color, 1, 2));
    $g = hexdec(substr($color, 3, 2));
    $b = hexdec(substr($color, 5, 2));

    $r = round($r * (1 - $percent));
    $g = round($g * (1 - $percent));
    $b = round($b * (1 - $percent));

    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

// Function to lighten a color by a percentage
function lightenColor($color, $percent)
{
    $percent /= 100;
    $r = hexdec(substr($color, 1, 2));
    $g = hexdec(substr($color, 3, 2));
    $b = hexdec(substr($color, 5, 2));

    $r = round($r + (255 - $r) * $percent);
    $g = round($g + (255 - $g) * $percent);
    $b = round($b + (255 - $b) * $percent);

    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

$textColor = lightenColor($baseColor, 95);
// Create other color variables
$cardsColor = darkenColor($baseColor, 60);
$secondaryColor = lightenColor($baseColor, 30);
$primaryColor = $baseColor;
$backgroundColor = darkenColor($baseColor, 90);

?>

@import url('all.css');

body {
  background-color: <?php echo $backgroundColor; ?>;
}

html * {
  font-family: Helvetica,Arial,sans-serif;
  color: <?php echo $textColor; ?>;
}

.nav-item.active .nav-link {
  position: relative;
  background-color: <?php echo $secondaryColor; ?>;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
  border-top-right-radius: 18px;
  border-bottom-right-radius: 18px;
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
  border-left: .01rem solid <?php echo $secondaryColor; ?>;
  border-bottom: .01rem solid <?php echo $secondaryColor; ?>;
}

.sidebar-light .nav-item.active .nav-link i {
  color: <?php echo $textColor; ?>;
}

.sidebar .nav-item.active .nav-link {
  font-weight: 400;
}

.sidebar .nav-item .nav-link span:hover {
  color: <?php echo $textColor; ?>!important;
}

#wrapper #content-wrapper #content {
  background-color: <?php echo $backgroundColor; ?>;
}

.topbar {
  background-color: <?php echo $backgroundColor; ?>;
}

.col {
  color: <?php echo $textColor; ?>;
}

.card-header .col i.fa-tachometer-alt,
.card-header .col i.fa-dot-circle,
.card-header .col i.fa-wifi,
.card-header .col i.fa-exchange-alt,
.card-header .col i.fa-hand-paper,
.card-header .col i.fa-network-wired,
.card-header .col i.fa-key,
.card-header .ra-wireguard,
.card-header .ra-wireguard:before,
.card-header .col i.fa-user-lock,
.card-header .col i.fa-chart-bar,
.card-header .col i.fa-cube,
.card-header .col i.fa-info-circle,
.card-header .col i.fa-globe,
.card-header .col i.fa-shield-alt {
  color: <?php echo $textColor; ?>;
}

i.fa-bars {
  color: <?php echo $primaryColor; ?>;
}

.nav-tabs {
  border-bottom: 1px solid <?php echo $secondaryColor; ?>;
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
  border-color: transparent;
}

.navbar-default .navbar-toggle .icon-bar {
  background-color: #d2d2d2;
}

.navbar-default .navbar-toggle:focus,
.navbar-default .navbar-toggle:hover {
  background-color: <?php echo $backgroundColor; ?>;
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
  color: <?php echo $textColor; ?>;
  background-color: <?php echo $secondaryColor; ?>;
  border-color: transparent;
}

a:focus, a:hover {
  color: #d2d2d2;
}

.card>.card-header, .modal-content, .modal-header {
  border-color: transparent;
  background-color: <?php echo $primaryColor; ?>;
  color: <?php echo $textColor; ?>;
  border-radius: 18px;
  font-size: 1.0rem;
  font-weight: 400;
}

.modal-body {
  background-color: <?php echo $backgroundColor; ?>;
}

.card-header {
  border-bottom-left-radius: 0px!important;
  border-bottom-right-radius: 0px!important;
  position: relative;
  margin-bottom: -18px;
}

.card>.card-header .fa {
  color: <?php echo $backgroundColor; ?>;
}

.card-header [class^="fa"] {
  color: <?php echo $textColor; ?>;
  font-size: 1.0rem;
}

.card, .card-body {
  border-color: transparent;
  border-radius: 18px;
  background-color: <?php echo $cardsColor; ?>;
  box-shadow: 0px -5px 5px rgba(0, 0, 0, 0.1),
              0px 4px 6px rgba(0, 0, 0, 0.1);
}

.card-body {
  padding-top: 36px; /* 18px to move down + 18px space at the top */
  padding-bottom: 36px; /* 18px space at the bottom */
}

.unstyled {
  background-color: <?php echo $cardsColor; ?>;
  color: <?php echo $textColor; ?>;
}

hr { 
  border-top: .01rem solid <?php echo $secondaryColor; ?>;
}

.sidebar-brand-text {
  color: <?php echo $secondaryColor; ?>;
}

.ra-raspap:before {
  color: #ac1b3d !important;
}

.sidebar-light #sidebarToggle {
  background-color: <?php echo $primaryColor; ?>;
  border: 1px solid <?php echo $secondaryColor; ?>; !important
}

.sidebar-light #sidebarToggle::after {
  color: <?php echo $textColor; ?>;
}

.sidebar-light .nav-item .nav-link:hover i {
  color: <?php echo $textColor; ?>;
}

.sidebar-light #sidebarToggle:hover {
  background-color: <?php echo $secondaryColor; ?>;
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
  background-color: <?php echo $primaryColor; ?>;
  color: <?php echo $textColor; ?>;
  border-top: 0px;
  border-bottom-right-radius: 18px!important;
  border-bottom-left-radius: 18px!important;
  position: relative;
  margin-top: -18px;
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
  background-color: <?php echo $backgroundColor; ?>;
}

.sidebar-light .nav-item .nav-link i {
  color: rgba(230, 230, 230, .3);
}

.sidebar .nav-item .nav-link {
  padding: 0.6rem;
  padding-left: 1.2rem;
}

.sidebar-light hr.sidebar-divider {
  border-top: 1px solid <?php echo $secondaryColor; ?>;
  padding-top: 0.5rem;
}

.sidebar .nav-item .nav-link span {
  font-size: 1.0rem;
}

.topbar .topbar-divider {
  border-right: 1px solid <?php echo $secondaryColor; ?>;
}

.label-warning {
  background-color: #d2d2d2;
}

span.label.label-warning {
  color: <?php echo $backgroundColor; ?>;
}

.table>tbody>tr>td,
.table>tbody>tr>th,
.table>tfoot>tr>td,
.table>tfoot>tr>th,
.table>thead>tr>td,
.table>thead>tr>th {
  background-color: <?php echo $primaryColor; ?>;
  border-top: .01rem solid <?php echo $backgroundColor; ?>;
}

.table{
  border-radius: 18px;
  overflow: hidden;
}

.table>thead>tr>th {
  vertical-align: bottom;
  border-bottom: 0 solid <?php echo $secondaryColor; ?>;
}

[class*="btn"], [class*="btn"]:focus, [class*="btn"]:disabled {
  background-color: <?php echo $secondaryColor; ?>;
  border-color: transparent;
  border-radius: 18px;
  color: <?php echo $textColor; ?>;
}

[class*="btn"]:hover {
  border-radius: 18px;
  color: <?php echo $textColor; ?>;
  background-color: <?php echo $backgroundColor; ?>;
  border-color: transparent;
}

[class*="btn"]:hover .disabled {
  background-color:red;
}

[class*="alert"] {
  border-radius: 18px;
  color: <?php echo $textColor; ?>;
  background-color: <?php echo $backgroundColor; ?>;
  border: 1px solid #404040;
}

.close {
  font-size: 1.2em;
  font-weight: 400;
  text-shadow: none;
  color: <?php echo $textColor; ?>;
}

.form-control,
.form-control:focus,
.custom-select {
  color: <?php echo $textColor; ?>;
  background-color: <?php echo $backgroundColor; ?>;
  border: 1px solid <?php echo $secondaryColor; ?>;
  border-radius: 18px;
}

.form-control:disabled,
.form-control[readonly] {
  background-color: <?php echo $backgroundColor; ?>;
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
color: <?php echo $textColor; ?>; !important
}

.progress {
  background-color: <?php echo $backgroundColor; ?>;
  border-radius: 18px;
}

.progress-bar {
  color: <?php echo $backgroundColor; ?>;
}

#progressBar {
  background-color: <?php echo $secondaryColor; ?>!important;
}

.progress-bar.bg-success {
  background-color: <?php echo $primaryColor; ?>!important;
  color: <?php echo $textColor; ?>!important;
}

.progress .progress-bar {
  padding-left: 5px;
}

.progress-bar.progress-bar-info.progress-bar-striped.active {
  background-color: <?php echo $secondaryColor; ?>;
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

.custom-control-input:checked ~ .custom-control-label::before {
  background-color: <?php echo $secondaryColor; ?>;
}

.custom-control-input:checked ~ .custom-control-label::before {
  background-color: <?php echo $primaryColor; ?>;
  border-color: <?php echo $primaryColor; ?>;
}

.wg-keygen {
  background-color: <?php echo $primaryColor; ?>;
  border: 1px solid yellow <?php echo $secondaryColor; ?>;
  border-top-right-radius: 18px !important;
  border-bottom-right-radius: 18px !important;
}

.btn.btn-outline-secondary.js-add-dhcp-upstream-server {
  background-color: <?php echo $primaryColor; ?>;
  border: 1px solid <?php echo $secondaryColor; ?>;
  border-top-right-radius: 18px !important;
  border-bottom-right-radius: 18px !important;
}

.btn.btn-outline-success.js-add-dhcp-static-lease {
  border: 1px solid <?php echo $secondaryColor; ?>;
}

.btn.btn-outline-success.js-add-dhcp-static-lease:hover {
  background-color: <?php echo $primaryColor; ?>;
}

.text-muted {
  font-size: 0.8rem;
}

.fas.fa-circle {
  font-size: 0.7rem;
}

pre {
  background-color: <?php echo $backgroundColor; ?>;
  border: <?php echo $backgroundColor; ?>;
}

button.btn.btn-light.js-toggle-password {
  border: 1px solid <?php echo $secondaryColor; ?>;
}

.btn-primary {
  border-color: transparent;
  background-color: <?php echo $primaryColor; ?>;
}

.btn-primary:hover {
  background-color: <?php echo $secondaryColor; ?>;
  border-color: transparent;
}

.btn.service-status {
  background-color: <?php echo $backgroundColor; ?>;
}

input.btn.btn-success {
  background-color: <?php echo $secondaryColor; ?>;
}

input.btn.btn-success:hover {
  background-color: <?php echo $backgroundColor; ?>;
  border-color: transparent;
}

.signal-icon .signal-bar {
  background: <?php echo $secondaryColor; ?>;
}

.figure-img {
  border-radius: 18px;
}

.logoutput {
  border-radius: 18px!important;
  background-color: <?php echo $backgroundColor; ?>;
  border: 1px solid <?php echo $primaryColor; ?>!important;
}

.btn-sm {
  border-top-right-radius: 18px!important;
  border-bottom-right-radius: 18px!important;
}

.signal-icon .signal-bar {
  background: <?php echo $secondaryColor; ?>;
}

input.btn.btn-warning {
  background-color: <?php echo $secondaryColor; ?>;
}

input.btn.btn-warning:hover {
  background-color: <?php echo $backgroundColor; ?>;!important
}

button.btn.btn-danger {
  background-color: <?php echo $secondaryColor; ?>;
}

button.btn.btn-danger:hover {
  background-color: <?php echo $backgroundColor; ?>;!important
}

.btn-group label.active {
        background-color: <?php echo $primaryColor; ?>!important;
        border-color:transparent!important;
        color: <?php echo $textColor; ?>;!important
}

.btn-group {
  background-color: <?php echo $cardsColor; ?>;!important
}

.btn-group:hover {
  background-color: <?php echo $cardsColor; ?>;!important
}

.btn.btn-outline-secondary#gen_wpa_passphrase {
  background-color: <?php echo $primaryColor; ?>;
  border: 1px solid <?php echo $secondaryColor; ?>;
  border-top-right-radius: 18px !important;
  border-bottom-right-radius: 18px !important;
}

a.scroll-to-top.rounded {
  display: inline;
  background-color: <?php echo $secondaryColor; ?>;
  border-radius: 18px!important;
}

a.scroll-to-top.rounded i.fas.fa-angle-up {
  color: <?php echo $textColor; ?>;
}

.btn.btn-sm.btn-outline-secondary.rounded-right {
  border: 1px solid <?php echo $secondaryColor; ?>;
  background-color: <?php echo $primaryColor; ?>;
}

.info-item.col-xs-3 {
  color: <?php echo $textColor; ?>;
}

.text-muted {
  color: <?php echo $textColor; ?>!important;
}

.grid-stack-item-content {
  width: 100%;
  height: 100%;
  padding: 5px;
  box-sizing: border-box;
}
