<div class="row">
  <div class="col-lg-12">
    <div class="panel panel-primary">
      <div class="panel-heading"><i class="fa fa-wrench fa-fw"></i> <?php echo _("Change Theme"); ?></div>
      <div class="panel-body">

        <h4><?php echo _("Theme settings"); ?></h4>

        <div class="row">
          <div class="form-group col-md-6">
            <label for="code"><?php echo _("Select a theme"); ?></label>
            <?php SelectorOptions("theme", $themes, $selectedTheme, "theme-select") ?>
          </div>
        </div>

        <form action="?page=system_info" method="POST">
          <?php echo CSRFTokenFieldTag() ?>
          <input type="button" class="btn btn-outline btn-primary" value="<?php echo _("Refresh"); ?>" onclick="document.location.reload(true)" />
        </form>

      </div><!-- /.panel-body -->
    </div><!-- /.panel-primary -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
