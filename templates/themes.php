<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-paint-brush mr-2"></i><?php echo _("Change Theme"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <h4><?php echo _("Theme settings"); ?></h4>
        <div class="row">
          <div class="form-group col-xs-3 col-sm-3">
            <label for="code"><?php echo _("Select a theme"); ?></label>
            <?php SelectorOptions("theme", $themes, $selectedTheme, "theme-select") ?>
          </div>
          <div class="col-xs-3 col-sm-3">
            <label for="code"><?php echo _("Color"); ?></label>
            <input class="form-control color-input" value="#d8224c" aria-label="color" />
          </div>
        </div>
        <form action="?page=system_info" method="POST">
            <?php echo CSRFTokenFieldTag() ?>
          <a href="?page=<?php echo $_GET['page'] ?>" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh"); ?></a>
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
