<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
	        <div class="col">
						<i class="fas fa-user-lock mr-2"></i><?php echo _("Authentication"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <h4><?php echo _("Authentication settings") ;?></h4>
        <form role="form" action="?page=auth_conf" method="POST">
            <?php echo CSRFTokenFieldTag() ?>
          <div class="row">
            <div class="form-group col-md-6">
              <label for="username"><?php echo _("Username"); ?></label>
              <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES); ?>"/>
            </div>
          </div>
          <div class="row">
            <div class="form-group col-md-6">
              <label for="password"><?php echo _("Old password"); ?></label>
              <input type="password" class="form-control" name="oldpass"/>
            </div>
          </div>
          <div class="row">
            <div class="form-group col-md-6">
              <label for="password"><?php echo _("New password"); ?></label>
              <input type="password" class="form-control" name="newpass"/>
            </div>
          </div>
          <div class="row">
            <div class="form-group col-md-6">
              <label for="password"><?php echo _("Repeat new password"); ?></label>
              <input type="password" class="form-control" name="newpassagain"/>
            </div>
          </div>
          <input type="submit" class="btn btn-outline btn-primary" name="UpdateAdminPassword" value="<?php echo _("Save settings"); ?>" />
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
