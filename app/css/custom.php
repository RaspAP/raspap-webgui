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

@import url('all.css');

body {
  color: #212529;
}

.sidebar {
  background-color: #f8f9fc;
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

pre.unstyled {
  border-width: 0;
  background-color: transparent;
  padding: 0;
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

.signal-icon .signal-bar {
  background: <?php echo $color; ?>;
}

