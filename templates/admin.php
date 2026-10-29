<div class="row">
  <div class="col-lg-12">
    <div class="card shadow">
      <div class="card-header page-card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-user-lock me-2"></i><?php echo _("Authentication"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>

        <!-- Nav tabs -->
        <div class="nav-tabs-wrapper">
          <ul class="nav nav-tabs">
            <?php if ($nonprivEnabled): ?>
            <li class="nav-item"><a class="nav-link active" id="authlogintab" href="#authlogin" aria-controls="login" data-bs-toggle="tab"><?php echo _("Login"); ?></a></li>
            <?php else: ?>
            <li class="nav-item"><a class="nav-link active" id="authgeneraltab" href="#authgeneral" aria-controls="basic" data-bs-toggle="tab"><?php echo _("Basic"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="authavatartab" href="#authavatar" data-bs-toggle="tab"><?php echo _("Avatar"); ?></a></li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Tab panes -->
        <div class="tab-content">
          <?php if ($nonprivEnabled): ?>
          <?php echo renderTemplate("admin/login", $__template_data) ?>
          <?php else: ?>
          <?php echo renderTemplate("admin/general", $__template_data) ?>
          <?php echo renderTemplate("admin/avatar", $__template_data) ?>
          <?php endif; ?>
        </div><!-- /.tab-content -->
          
      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<!-- modal change password -->
<div class="modal fade" id="auth-change-password" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title" id="ModalLabel"><i class="fas fa-lock me-2"></i><?php echo _("Update admin password"); ?></div>
      </div>
      <div class="modal-body">
        <form role="form" action="auth_conf" method="POST" class="needs-validation" novalidate>
        <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
        <div class="row">
          <div class="mb-3 col-md-12">
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
          <div class="mb-3 col-md-12">
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
          <div class="mb-3 col-md-12">
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
      </div><!-- modal-body -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo _("Cancel"); ?></button>
        <button type="submit" class="btn btn-outline-primary" name="authchangepw"><?php echo _("Save"); ?></button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- modal logout -->
<div class="modal fade" tabindex="-1" role="dialog" id="auth-save-logout" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title" id="ModalLabel"><i class="fas fa-sign-out-alt me-2"></i><?php echo _("Logout and enable limited user mode"); ?></div>
      </div>
      <div class="modal-body">
        <form role="form" id="auth-logout" action="auth_conf" method="POST">
        <input type="hidden" name="authlogout" value="1">
        <input type="hidden" name="nonprivEnabled" value="1">
        <input type="hidden" id="modal_limited_user" name="modal_limited_user">
        <input type="hidden" id="modal_limited_pass" name="modal_limited_pass">
        <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
        <div class="row">
          <div class="col-md-12 mb-3 mt-1">
            <?php echo _("This action will save the limited user's credentials and logout the current admin user. Save and enable limited privilege mode?"); ?>
          </div>
        </div>
      </div><!-- modal-body -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo _("Cancel"); ?></button>
        <button type="button" class="btn btn-outline-primary" name="authlogout" id="authlogout"><?php echo _("Save and logout"); ?></button>
      </div>
      </form>
    </div>
  </div>
</div>

