
<!-- fullscreen modal -->
<div class="modal" id="modal-admin-login" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen" role="document">
    <div class="modal-content">
      <div class="modal-body">
        <div class="row h-100 justify-content-center align-items-center">
          <div class="col-12">
            <!-- branding -->
            <div class="text-center mb-3">
              <img src="app/img/raspAP-logo.php" class="login-logo" alt="RaspAP logo" class="img-fluid" style="max-width: 100px;">
              <h2 class="login-brand"><?php echo htmlspecialchars(RASPI_BRAND_TEXT); ?></h2>
              <div class="mt-2 admin-login"><?php echo _("Administrator login") ?></div>
              <div class="text-center text-danger mt-1 mb-3"><?php echo $status ?></div>
            </div>
            <div class="text-center mb-4">
              <form id="admin-login-form" action="login" method="POST" class="needs-validation" novalidate>
              <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
                <div class="form-group">
                  <input type="hidden" name="login-auth">
                  <input type="hidden" id="redirect-url" name="redirect-url" value="<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="text" class="form-control" id="username" name="username" placeholder="<?php echo _("Username") ?>" required>
                </div>
                <div class="mt-2">
                  <div class="input-group has-validation">
                    <input type="password" class="form-control rounded-start border-end-0 no-right-radius" id="password" name="password" placeholder="<?php echo _("Password") ?>" required>
                    <button class="btn bg-white btn-passwd-append border-start-0 js-toggle-password" type="button" id="passwd-toggle" data-bs-target="[name=password]" data-toggle-with="fas fa-eye-slash text-secondary text-opacity-50">
                      <i class="fas fa-eye text-secondary text-opacity-50"></i>
                    </button>
                  </div>

                </div>
                <button type="submit" class="btn btn-outline btn-admin-login rounded-pill w-75 mt-4"><?php echo _("Login") ?></button>
                <div class="small mt-2"><a href="https://docs.raspap.com/authentication/#restoring-defaults" target="_blank"><?php echo _("Forgot password") ?></a></div>
                <img src="app/img/uri-qr-code.php?uri=https://docs.raspap.com/authentication/" class="figure-img img-fluid mt-2" alt="RaspAP docs" style="width:75px;">
              </form>
            </div>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.modal-body -->
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>
