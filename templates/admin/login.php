<?php ob_start() ?>
  <input type="submit" class="btn btn-outline-primary" name="authlogin" value="<?php echo _("Submit"); ?>" />
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<!-- login tab -->
<div class="tab-pane active" id="authlogin">
  <form role="form" action="auth_conf" method="POST" class="needs-validation" novalidate>
  <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
  <div class="row">
    <div class="mb-3 col-md-6">
    <h4 class="mt-3"><?php echo _("Admin login"); ?></h4>
      <label for="username"><?php echo _("Username"); ?></label>
      <input type="text" class="form-control" name="username" required />
      <div class="invalid-feedback">
        <?php echo _("Please provide a valid username."); ?>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="mb-3 col-md-6">
      <div class="mb-2"><?php echo _("Password"); ?></div>
      <div class="input-group has-validation">
        <input type="password" class="form-control" name="password" required />
        <div class="input-group-text js-toggle-password" data-bs-target="[name=password]" data-toggle-with="fas fa-eye-slash"><i class="fas fa-eye mx-2"></i></div>
        <div class="invalid-feedback">
          <?php echo _("Please enter your password."); ?>
        </div>
      </div>
    </div>
  </div>

  <?php echo $buttons ?>
  </form>
</div><!-- /.tab-pane | login tab -->

