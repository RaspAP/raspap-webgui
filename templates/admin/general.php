<?php ob_start() ?>
  <input type="button" class="btn btn-outline-primary" id="js-auth-save-logout" value="<?php echo _("Save settings"); ?>" />
  <input type="button" class="btn btn-warning" name="authchangepw" data-bs-toggle="modal" data-bs-target="#auth-change-password" value="<?php echo _("Change password"); ?>" />
  <input type="submit" class="btn btn-warning" name="adminlogout" value="<?php echo _("Logout") ?>" onclick="disableValidation(this.form)"/>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<!-- general settings tab -->
<div class="tab-pane fade show active" id="authgeneral">
  <form role="form" action="auth_conf" method="POST" id="auth-basic" class="needs-validation" novalidate>
  <input type="hidden" name="authsavesettings" value="1">
  <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
  <div class="row">
    <div class="mb-3 col-md-6">
    <h4 class="mt-3"><?php echo _("Basic settings"); ?></h4>
      <label for="username"><?php echo _("Username"); ?></label>
      <input type="text" class="form-control" name="username" id="admin_user" value="<?php echo htmlspecialchars($username, ENT_QUOTES); ?>" required />
      <div class="invalid-feedback">
        <?php echo _("Please provide a valid username."); ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 mb-2">
      <div class="form-check form-switch">
        <?php $checked = $nonprivEnabled == 1 ? 'checked="checked"' : '' ?>
        <input class="form-check-input" id="chxnonprivenable" name="nonprivEnabled" type="checkbox" value="1" <?php echo $checked ?> />
        <label class="form-check-label" for="chxnonprivenable"><?php echo _("Enable limited privilege user"); ?></label>
        <div class="mb-3">
          <small><?php echo _("This option enables a non-admin user who can access RaspAP's management interface, but has <strong>limited ability to modify the existing configuration</strong>. This user becomes active when the current admin user is logged-out.") ?></small>
        </div>
        <div class="row">
          <div class="mb-3 col-md-12">
            <label for="limited_user"><?php echo _("Limited user login"); ?></label>
            <input type="text" class="form-control" id="limited_user" name="limited_user" value="<?php echo $limited_user; ?>" disabled required />
            <div class="invalid-feedback">
              <?php echo _("Please provide a valid username."); ?>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="mb-3 col-md-12">
            <div class="mb-2"><?php echo _("Limited user password"); ?></div>
            <div class="input-group has-validation">
              <input type="password" class="form-control" id="limited_pass" name="limited_pass"  disabled required />
              <div class="input-group-text js-toggle-password" data-bs-target="[name=limited_pass]" data-toggle-with="fas fa-eye-slash"><i class="fas fa-eye mx-2"></i></div>
                <div class="invalid-feedback">
                  <?php echo _("Please enter a valid password."); ?>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2">
    <?php echo $buttons ?>
  </div>
  </form>
</div><!-- /.tab-pane | general tab -->

