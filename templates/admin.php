<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" class="btn btn-outline btn-primary" name="UpdateAdminPassword" value="<?php echo _("Save settings"); ?>" />
    <input type="submit" class="btn btn-warning" name="logout" value="<?php echo _("Logout") ?>" onclick="disableValidation(this.form)"/>
  <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
	      <div class="col">
            <i class="fas fa-user-lock me-2"></i><?php echo _("Authentication"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <h4><?php echo _("Authentication settings") ;?></h4>
        <form role="form" action="auth_conf" method="POST" class="needs-validation" novalidate>
            <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
          <div class="row">
            <div class="mb-3 col-md-6">
              <label for="username"><?php echo _("Username"); ?></label>
              <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES); ?>" required />
              <div class="invalid-feedback">
                <?php echo _("Please provide a valid username."); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="mb-3 col-md-6">
              <div class="mb-2"><?php echo _("Old password"); ?></div>
              <div class="input-group has-validation">
                <input type="password" class="form-control" name="oldpass" required />
                <div class="input-group-text js-toggle-password" data-bs-target="[name=oldpass]" data-toggle-with="fas fa-eye-slash"><i class="fas fa-eye mx-2"></i></div>
                <div class="invalid-feedback">
                  <?php echo _("Please enter your old password."); ?>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="mb-3 col-md-6">
              <div class="mb-2"><?php echo _("New password"); ?></div>
              <div class="input-group has-validation">
                <input type="password" class="form-control" name="newpass" required />
                <div class="input-group-text js-toggle-password" data-bs-target="[name=newpass]" data-toggle-with="fas fa-eye-slash"><i class="fas fa-eye mx-2"></i></div>
                <div class="invalid-feedback">
                  <?php echo _("Please enter a new password."); ?>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="mb-3 col-md-6">
              <div class="mb-2"><?php echo _("Repeat new password"); ?></div>
              <div class="input-group has-validation">
                <input type="password" class="form-control" name="newpassagain" required />
                <div class="input-group-text js-toggle-password" data-bs-target="[name=newpassagain]" data-toggle-with="fas fa-eye-slash"><i class="fas fa-eye mx-2"></i></div>
                <div class="invalid-feedback">
                  <?php echo _("Please re-enter your new password."); ?>
                </div>
              </div>
            </div>
          </div>
          <?php echo $buttons ?>
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
