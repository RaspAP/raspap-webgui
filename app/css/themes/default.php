<?php header("Content-Type: text/css;charset=utf-8"); ?>
<?php
    require_once '../../../includes/functions.php';
    $color = getColorOpt();
?>
/*
Theme Name: RaspAP default
Author: @billz
Author URI: https://github.com/billz
Description: Default theme for RaspAP
License: GNU General Public License v3.0
*/

/* Light Mode */
:root {
  --raspap-theme-color: <?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>;
  --raspap-theme-lighter: <?php echo htmlspecialchars(lightenColor($color, 20), ENT_QUOTES, 'UTF-8'); ?>;
  --raspap-theme-darker: <?php echo htmlspecialchars(darkenColor($color, 20), ENT_QUOTES, 'UTF-8'); ?>;
}

/* Typography */
a {
  --bs-link-color: var(--raspap-theme-color);
  --bs-link-hover-color: var(--raspap-theme-color);
}

/* Elements */

/* Icons */
i.fa.fa-bars {
  color: #d1d3e2;
}
i.fa.fa-bars:hover{
  color: #6e707e;
}

/* Buttons */
.btn-primary {
  --bs-btn-bg: var(--raspap-theme-color);
  --bs-btn-border-color: var(--raspap-theme-color);
  --bs-btn-hover-bg: var(--raspap-theme-darker);
  --bs-btn-hover-border-color: var(--raspap-theme-darker);
  --bs-btn-active-bg: var(--raspap-theme-darker);
  --bs-btn-active-border-color: var(--raspap-theme-darker);
  --bs-btn-disabled-bg: var(--raspap-theme-lighter);
  --bs-btn-disabled-border-color: var(--raspap-theme-lighter);
}

.btn-outline-primary {
  --bs-btn-color: var(--raspap-theme-color);
  --bs-btn-border-color: var(--raspap-theme-color);
  --bs-btn-hover-bg: var(--raspap-theme-color);
  --bs-btn-hover-border-color: var(--raspap-theme-color);
  --bs-btn-active-bg: var(--raspap-theme-color);
  --bs-btn-active-border-color: var(--raspap-theme-color);
  --bs-btn-disabled-color: var(--raspap-theme-color);
  --bs-btn-disabled-border-color: var(--raspap-theme-color);
}

html:not([data-bs-theme="dark"]) .btn-outline-warning {
  --bs-btn-color: #333;
}

.btn-light {
  --bs-btn-color: var(--raspap-theme-darker);
  --bs-btn-hover-color: var(--raspap-theme-darker);
  --bs-btn-active-color: var(--raspap-theme-darker);
  --bs-btn-disabled-color: var(--raspap-theme-darker);
}

.btn-link {
  --bs-link-color: var(--raspap-theme-color);
  --bs-link-hover-color: var(--raspap-theme-darker);
}

/* Forms */
.form-check-input:checked {
  background-color: var(--raspap-theme-color); 
  border-color: var(--raspap-theme-color); 
}

/* Layout */
.sb-sidenav .sb-sidenav-menu .nav-link:hover,
.sb-sidenav .sb-nav-link-icon.active .nav-link,
.sb-sidenav .sb-nav-link-icon.active i.sb-nav-link-icon {
  color: var(--raspap-theme-color);
}

.sidebar-brand-text {
  color: var(--raspap-theme-color); 
}
.sidebar-brand-text:focus,
.sidebar-brand-text:hover {
  color: var(--raspap-theme-darker);
}

.sidebar {
  background-color: #f8f9fc;
}

#navbar-system-mode.active {
  color: var(--raspap-theme-color);
}

.card .card-header,
.modal-header {
  border-color: var(--raspap-theme-color); 
  color: #fff;
  background-color: var(--raspap-theme-color); 
}
.card-body {
  color: #495057;
}
.card-footer, .modal-footer {
  background-color: #f2f1f0;
}

/* --- Page Specific --- */
/* Dashboard */
.connection-item,
.connection-item > i,
.connections-left > .connection-item > span  {
  color: var(--raspap-text-light);
}

.connection-item > a.active > span,
.connection-item > a.active > i {
  color: var(--raspap-theme-color) !important;
}

.band.active {
  border-color: var(--raspap-theme-color);
  color: var(--raspap-theme-color);
}

.device-label {
  color: var(--raspap-theme-color);
}

.status-item {
  color: var(--raspap-text-light);
}
.status-item.active > span {
  color: var(--raspap-theme-color) !important;
}
.status-item.active > div > i {
  color: var(--raspap-theme-color) !important;
}

.client-type i {
  color: var(--raspap-theme-color);
  border: 2px solid var(--raspap-theme-color);
}

.client-type i.badge-icon {
  background: var(--raspap-theme-color);
  color: var(--raspap-offwhite);
}

.client-count {
  background: var(--raspap-theme-color);
  color: var(--raspap-offwhite);
}

/* WiFi Client */
.signal-icon .signal-bar {
  background: var(--raspap-theme-color); 
}

/*
Theme Name: Lights Out
Author: @billz
Author URI: https://github.com/billz
Description: A Bootstrap dark mode theme for RaspAP
License: GNU General Public License v3.0
*/
/* Dark Mode */
html[data-bs-theme="dark"],
html[data-bs-theme="dark"] body,
html[data-bs-theme="dark"] footer,
html[data-bs-theme="dark"] .sb-sidenav,
html[data-bs-theme="dark"] .sb-topnav,
html[data-bs-theme="dark"] .card-body,
html[data-bs-theme="dark"] .card-footer,
html[data-bs-theme="dark"] .modal:not(#modal-admin-login) .modal-body,
html[data-bs-theme="dark"] .modal:not(#modal-admin-login) .modal-footer {
  background-color: var(--bs-dark) !important;
  color: var(--bs-light) !important;
}

html[data-bs-theme="dark"] .card,
html[data-bs-theme="dark"] .card-footer,
html[data-bs-theme="dark"] .modal:not(#modal-admin-login) .modal-body,
html[data-bs-theme="dark"] .modal:not(#modal-admin-login) .modal-footer {
  border-color: var(--bs-secondary);
}

html[data-bs-theme="dark"] .sb-topnav #sidebarToggle,
html[data-bs-theme="dark"] .sb-sidenav .sb-sidenav-menu .nav-link,
html[data-bs-theme="dark"] .card-body,
html[data-bs-theme="dark"] .table > * > * > * {
  color: var(--bs-light) !important;
}
html[data-bs-theme="dark"] .sb-sidenav .sb-sidenav-menu .nav-link:hover,
html[data-bs-theme="dark"] .sb-sidenav .sb-nav-link-icon.active .nav-link {
  color: var(--raspap-theme-color) !important;
}

html[data-bs-theme="dark"] .sb-status,
html[data-bs-theme="dark"] .card-footer,
html[data-bs-theme="dark"] .info-item-xs {
  color: var(--bs-secondary) !important;
}

html[data-bs-theme="dark"] .nav-tabs {
  --bs-nav-tabs-link-active-color: var(--bs-light);
  --bs-nav-tabs-link-active-bg: var(--bs-dark);
  --bs-nav-tabs-link-active-border-color: var(--bs-secondary) var(--bs-secondary) var(--bs-dark);
  --bs-nav-tabs-border-color: var(--bs-secondary);
  --bs-nav-tabs-link-hover-border-color: var(--bs-secondary);
}

html[data-bs-theme="dark"] .ip-info-toggle {
  background: transparent;
  border: 2px solid var(--raspap-text-light);
}
