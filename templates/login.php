
<!-- fullscreen modal -->
<div class="modal" id="modal-admin-login" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen" role="document">
    <div class="modal-content">
      <div class="modal-body">
        <div class="row h-100 justify-content-center align-items-center">
          <div class="col-12">
            <!-- branding -->
            <div class="text-center mb-3">
              <?php renderBrandIcon(70, 70, 'login-logo', false); ?>
              <h2 class="login-brand"><?php echo htmlspecialchars(RASPI_BRAND_TEXT); ?></h2>
              <div class="mt-2 admin-login"><?php echo $changeRequired ? _("Set a new password") : _("Administrator login") ?></div>
              <div class="text-center text-danger mt-1 mb-3"><?php echo $status ?></div>
            </div>
            <div class="text-center mb-4">
              <?php if ($changeRequired) : ?>
              <form id="admin-password-form" action="login" method="POST" class="needs-validation" novalidate>
                <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
                <input type="hidden" name="change-pass">
                <div class="small text-offwhite mb-3"><?php echo _("Please choose a new password to continue.") ?></div>
                <div class="input-group has-validation">
                  <input type="password" class="form-control rounded-start border-end-0 no-right-radius" id="newpass" name="newpass" placeholder="<?php echo _("New password") ?>" minlength="8" autocomplete="new-password" required>
                  <button class="btn bg-white btn-passwd-append border-start-0 js-toggle-password" type="button" data-bs-target="[name=newpass]" data-toggle-with="fas fa-eye-slash text-secondary text-opacity-50">
                    <i class="fas fa-eye text-secondary text-opacity-50"></i>
                  </button>
                </div>
                <div class="input-group has-validation mt-2">
                  <input type="password" class="form-control rounded-start border-end-0 no-right-radius" id="newpassagain" name="newpassagain" placeholder="<?php echo _("Repeat new password") ?>" minlength="8" autocomplete="new-password" required>
                  <button class="btn bg-white btn-passwd-append border-start-0 js-toggle-password" type="button" data-bs-target="[name=newpassagain]" data-toggle-with="fas fa-eye-slash text-secondary text-opacity-50">
                    <i class="fas fa-eye text-secondary text-opacity-50"></i>
                  </button>
                </div>
                <button type="submit" class="btn btn-primary rounded-pill w-75 mt-4"><?php echo _("Save password") ?></button>
              </form>
              <?php else : ?>
              <form id="admin-login-form" action="login" method="POST" class="needs-validation" novalidate>
                <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
                <div class="form-group">
                  <input type="hidden" name="login-auth">
                  <input type="hidden" id="redirect-url" name="redirect-url" value="<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="text" class="form-control" id="username" name="username" placeholder="<?php echo _("Username") ?>" required>
                </div>
                <div class="input-group has-validation mt-2">
                  <input type="password" class="form-control rounded-start border-end-0 no-right-radius" id="password" name="password" placeholder="<?php echo _("Password") ?>" required>
                  <button class="btn bg-white btn-passwd-append border-start-0 js-toggle-password" type="button" id="passwd-toggle" data-bs-target="[name=password]" data-toggle-with="fas fa-eye-slash text-secondary text-opacity-50">
                    <i class="fas fa-eye text-secondary text-opacity-50"></i>
                  </button>
                </div>
                <button type="submit" class="btn btn-primary rounded-pill w-75 mt-4"><?php echo _("Login") ?></button>
                <div class="small mt-2"><a href="https://docs.raspap.com/authentication/#restoring-defaults" target="_blank"><?php echo _("Forgot password") ?></a></div>
                <img src="app/img/uri-qr-code.php" class="figure-img img-fluid mt-5" alt="RaspAP docs" style="width:75px;">
                <div><span class="small text-offwhite"><?= _('Scan for Login Help'); ?></span></div>
              </form>
              <?php endif; ?>
            </div>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.modal-body -->
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>
