<nav class="sb-topnav navbar navbar-expand navbar-light bg-light">
  <!-- Navbar Brand-->
  <a class="sidebar-brand-text navbar-brand ps-3" href="wlan0_info">RaspAP</a>
  <!-- Sidebar Toggle-->
  <button class="btn btn-link btn-lg order-1 order-lg-0 me-lg-auto py-2 px-3 bd-highlight" id="sidebarToggle" href="#!">
    <i class="fas fa-bars"></i>
  </button>
  <!-- Navbar-->
  <ul class="navbar-nav align-items-center gap-3 ms-auto ms-lg-0 me-2 me-lg-4">
    <!-- Display mode -->
    <li>
      <div class="form-check form-switch pl-6 mb-0" style="min-height: initial;">
        <input type="checkbox" class="form-check-input" id="night-mode" <?php echo getNightmode() ? 'checked' : null ; ?> >
        <label class="form-check-label" for="night-mode"><i class="far fa-moon mr-1 text-muted"></i></label>
      </div>
    </li>
    <!-- Auth user -->
    <li>
      <a class="d-flex flex-nowrap align-items-center gap-1" href="auth_conf">
        <span class="text-muted small"><?php echo htmlspecialchars($_SESSION['user_id'] ?? '', ENT_QUOTES); ?></span>
        <i class="fas fa-user-circle text-muted fa-3x"></i>
      </a>
    </li>
  </ul>
</nav>
