<div class="row">
  <div class="col-lg-12">
    <div class="panel panel-primary">
      <div class="panel-heading"><i class="fa fa-lock fa-fw"></i><?php echo _("Configure Auth"); ?></div>
      <div class="panel-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="?page=auth_conf" method="POST">
            <?php echo CSRFTokenFieldTag() ?>
          <div class="row">
            <div class="form-group col-md-4">
              <label for="username"><?php echo _("Username"); ?></label>
              <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES); ?>"/>
            </div>
          </div>
          <div class="row">
            <div class="form-group col-md-4">
              <label for="password"><?php echo _("Old password"); ?></label>
              <input type="password" class="form-control" name="oldpass"/>
            </div>
          </div>
          <div class="row">
            <div class="form-group col-md-4">
              <label for="password"><?php echo _("New password"); ?></label>
              <input type="password" class="form-control" name="newpass"/>
            </div>
          </div>
          <div class="row">
            <div class="form-group col-md-4">
              <label for="password"><?php echo _("Repeat new password"); ?></label>
              <input type="password" class="form-control" name="newpassagain"/>
            </div>
          </div>
          <input type="submit" class="btn btn-outline btn-primary" name="UpdateAdminPassword" value="<?php echo _("Save settings"); ?>" />
        </form>
      </div><!-- /.panel-body -->
    </div><!-- /.panel-default -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
