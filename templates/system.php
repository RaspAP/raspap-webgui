<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-cube mr-2"></i><?php echo _("System"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="?page=system_info" method="POST">
        <?php echo CSRFTokenFieldTag() ?>
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="nav-item"><a class="nav-link active" id="basictab" href="#basic" aria-controls="basic" role="tab" data-toggle="tab"><?php echo _("Basic"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="languagetab" href="#language" aria-controls="language" role="tab" data-toggle="tab"><?php echo _("Language"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="advancedtab" href="#advanced" aria-controls="advanced" role="tab" data-toggle="tab"><?php echo _("Advanced"); ?></a></li>
        </ul>
          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("system/basic", $__template_data) ?>
            <?php echo renderTemplate("system/language", $__template_data) ?>
            <?php echo renderTemplate("system/advanced", $__template_data) ?>
          </div><!-- /.tab-content -->
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
