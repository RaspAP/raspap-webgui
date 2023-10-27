      <nav class="navbar navbar-expand navbar-light topbar mb-1 static-top">
        <!-- Sidebar Toggle (Topbar) -->
        <button id="sidebarToggleTopbar" class="btn btn-link d-md-none rounded-circle mr-3">
          <i class="fa fa-bars"></i>
        </button>
        <!-- Topbar Navbar -->
        <p class="text-left brand-title mt-3 ml-2"></p>
        <ul class="navbar-nav ml-auto">
          <!-- Nav Item - Insiders -->
          <div class="insiders mt-4">
            <a href="https://docs.raspap.com/insiders" target="_blank"><i class="fas fa-heart mr-3" style="color: #e63946"></i></a>
          </div>
          <!-- Nav Item - Night mode -->
          <div class="custom-control custom-switch mt-4">
            <input type="checkbox" class="custom-control-input" id="night-mode" <?php echo getNightmode() ? 'checked' : null ; ?> >
            <label class="custom-control-label" for="night-mode"><i class="far fa-moon mr-1 text-muted"></i></label>
          </div>
          <div class="topbar-divider d-none d-sm-block"></div>
          <!-- Nav Item - User -->
          <li class="nav-item dropdown no-arrow">
          <a class="nav-link" href="auth_conf">
            <span class="mr-2 d-none d-lg-inline small"><?php echo htmlspecialchars($_SESSION['user_id'], ENT_QUOTES); ?></span>
            <i class="fas fa-user-circle fa-3x"></i>
          </a>
          </li>
        </ul>
      </nav>
