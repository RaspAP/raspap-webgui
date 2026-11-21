<?php header("Content-Type: text/css;charset=utf-8"); ?>
<?php
    require_once '../../../includes/config.php';
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
/* Login */
.login-brand {
  color: var(--raspap-theme-color);
}

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

.ip-info-toggle {
  background: #e3e3e3;
}
.ip-info-toggle button {
  color: var(--raspap-theme-color);
}
.ip-info-toggle button.active {
  background: var(--raspap-theme-color);
  color: white;
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

/*
Text is Bootstrap's dark body color (#dee2e6), about 87% white on #212529,
in line with Material's high-emphasis text. These restore Bootstrap 5.3's
dark variables: sb-admin bundles Bootstrap 5.2, whose :root block loads
later and resets them to light values.
*/
html[data-bs-theme="dark"] {
  color-scheme: dark;
  --raspap-dark-text: #dee2e6;
  --raspap-dark-input-bg: #2b3035;
  --raspap-dark-raised-bg: #343a40;
  --raspap-dark-border: #495057;
  --bs-body-color: #dee2e6;
  --bs-body-color-rgb: 222, 226, 230;
  --bs-body-bg: #212529;
  --bs-body-bg-rgb: 33, 37, 41;
  --bs-emphasis-color: #f8f9fa;
  --bs-emphasis-color-rgb: 248, 249, 250;
  --bs-secondary-color: rgba(222, 226, 230, 0.75);
  --bs-secondary-color-rgb: 222, 226, 230;
  --bs-secondary-bg: #343a40;
  --bs-secondary-bg-rgb: 52, 58, 64;
  --bs-tertiary-color: rgba(222, 226, 230, 0.5);
  --bs-tertiary-color-rgb: 222, 226, 230;
  --bs-tertiary-bg: #2b3035;
  --bs-tertiary-bg-rgb: 43, 48, 53;
  --bs-heading-color: inherit;
  --bs-code-color: #e685b5;
  --bs-border-color: #495057;
  --bs-border-color-translucent: rgba(255, 255, 255, 0.15);
  --bs-form-valid-color: #75b798;
  --bs-form-valid-border-color: #75b798;
  --bs-form-invalid-color: #ea868f;
  --bs-form-invalid-border-color: #ea868f;
}

/* Forms */
html[data-bs-theme="dark"] .form-control,
html[data-bs-theme="dark"] .form-select,
html[data-bs-theme="dark"] .datatable-input,
html[data-bs-theme="dark"] .datatable-selector,
html[data-bs-theme="dark"] textarea.plugin-log {
  color: var(--raspap-dark-text);
  background-color: var(--raspap-dark-input-bg);
  border-color: var(--raspap-dark-border);
}
html[data-bs-theme="dark"] .form-control:focus,
html[data-bs-theme="dark"] .form-select:focus {
  color: var(--raspap-dark-text);
  background-color: var(--raspap-dark-input-bg);
}
html[data-bs-theme="dark"] .form-control::placeholder {
  color: var(--bs-tertiary-color);
}
html[data-bs-theme="dark"] .form-control:disabled,
html[data-bs-theme="dark"] .form-select:disabled {
  color: var(--bs-secondary-color);
  background-color: var(--bs-body-bg);
}
html[data-bs-theme="dark"] .form-control::file-selector-button {
  color: var(--raspap-dark-text);
  background-color: var(--raspap-dark-raised-bg);
}
html[data-bs-theme="dark"] .form-select:not([multiple]):not([size]) {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23dee2e6' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
}
html[data-bs-theme="dark"] .input-group-text {
  color: var(--raspap-dark-text);
  background-color: var(--raspap-dark-raised-bg);
  border-color: var(--raspap-dark-border);
}
html[data-bs-theme="dark"] .form-check-input:not(:checked) {
  background-color: var(--raspap-dark-input-bg);
  border-color: var(--raspap-dark-border);
}
html[data-bs-theme="dark"] .form-switch .form-check-input:not(:checked):not(:focus) {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%28255, 255, 255, 0.25%29'/%3e%3c/svg%3e");
}

/* Buttons */
html[data-bs-theme="dark"] .btn-light {
  --bs-btn-color: var(--raspap-dark-text);
  --bs-btn-bg: var(--raspap-dark-raised-bg);
  --bs-btn-border-color: var(--raspap-dark-border);
  --bs-btn-hover-color: var(--raspap-dark-text);
  --bs-btn-hover-bg: #3d444b;
  --bs-btn-hover-border-color: #5c636a;
  --bs-btn-active-color: var(--raspap-dark-text);
  --bs-btn-active-bg: #495057;
  --bs-btn-active-border-color: #5c636a;
  --bs-btn-disabled-color: var(--bs-secondary-color);
  --bs-btn-disabled-bg: var(--raspap-dark-raised-bg);
  --bs-btn-disabled-border-color: var(--raspap-dark-border);
}
html[data-bs-theme="dark"] button.btn.btn-light.js-toggle-password,
html[data-bs-theme="dark"] .input-group > .btn {
  border-color: var(--raspap-dark-border);
}

/* Tables, lists, menus and other surfaces */
html[data-bs-theme="dark"] .table-light {
  --bs-table-color: var(--raspap-dark-text);
  --bs-table-bg: var(--raspap-dark-input-bg);
  --bs-table-border-color: var(--raspap-dark-border);
}
html[data-bs-theme="dark"] .list-group {
  --bs-list-group-color: var(--raspap-dark-text);
  --bs-list-group-bg: var(--bs-body-bg);
  --bs-list-group-border-color: var(--raspap-dark-border);
  --bs-list-group-action-color: var(--bs-secondary-color);
  --bs-list-group-action-hover-color: var(--raspap-dark-text);
  --bs-list-group-action-hover-bg: var(--raspap-dark-input-bg);
  --bs-list-group-action-active-color: var(--raspap-dark-text);
  --bs-list-group-action-active-bg: var(--raspap-dark-raised-bg);
  --bs-list-group-disabled-bg: var(--bs-body-bg);
}
html[data-bs-theme="dark"] .dropdown-menu {
  --bs-dropdown-color: var(--raspap-dark-text);
  --bs-dropdown-bg: var(--raspap-dark-raised-bg);
  --bs-dropdown-border-color: var(--bs-border-color-translucent);
  --bs-dropdown-divider-bg: var(--raspap-dark-border);
  --bs-dropdown-link-color: var(--raspap-dark-text);
  --bs-dropdown-link-hover-color: var(--raspap-dark-text);
  --bs-dropdown-link-hover-bg: #3d444b;
  --bs-dropdown-header-color: var(--bs-secondary-color);
}
html[data-bs-theme="dark"] .modal {
  --bs-modal-bg: var(--bs-body-bg);
  --bs-modal-color: var(--raspap-dark-text);
}
html[data-bs-theme="dark"] .pagination {
  --bs-pagination-bg: var(--bs-body-bg);
  --bs-pagination-border-color: var(--raspap-dark-border);
  --bs-pagination-hover-bg: var(--raspap-dark-input-bg);
  --bs-pagination-hover-border-color: var(--raspap-dark-border);
  --bs-pagination-focus-bg: var(--raspap-dark-input-bg);
  --bs-pagination-disabled-color: var(--bs-secondary-color);
  --bs-pagination-disabled-bg: var(--bs-body-bg);
  --bs-pagination-disabled-border-color: var(--raspap-dark-border);
}
html[data-bs-theme="dark"] .accordion {
  --bs-accordion-color: var(--raspap-dark-text);
  --bs-accordion-bg: var(--bs-body-bg);
  --bs-accordion-border-color: var(--raspap-dark-border);
  --bs-accordion-btn-color: var(--raspap-dark-text);
  --bs-accordion-btn-bg: var(--bs-body-bg);
}
html[data-bs-theme="dark"] .toast {
  --bs-toast-color: var(--raspap-dark-text);
  --bs-toast-bg: rgba(33, 37, 41, 0.85);
  --bs-toast-header-color: var(--bs-secondary-color);
  --bs-toast-header-bg: rgba(33, 37, 41, 0.85);
}
html[data-bs-theme="dark"] .h-underlined,
html[data-bs-theme="dark"] .logoutput {
  border-color: var(--raspap-dark-border);
}

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
  color: var(--raspap-dark-text) !important;
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
  color: var(--raspap-dark-text) !important;
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
  --bs-nav-tabs-link-active-color: var(--raspap-dark-text);
  --bs-nav-tabs-link-active-bg: var(--bs-dark);
  --bs-nav-tabs-link-active-border-color: var(--bs-secondary) var(--bs-secondary) var(--bs-dark);
  --bs-nav-tabs-border-color: var(--bs-secondary);
  --bs-nav-tabs-link-hover-border-color: var(--bs-secondary);
}

html[data-bs-theme="dark"] .ip-info-toggle {
  background: transparent;
  border: 2px solid var(--raspap-text-light);
}
