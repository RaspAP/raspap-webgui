<?php

require_once 'app/lib/Parsedown.php';

?>
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-info-circle mr-2"></i><?php echo _("About RaspAP"); ?>
          </div>
        </div><!-- ./row -->
      </div><!-- ./card-header -->
      <div class="card-body">

        <!-- Nav tabs -->
        <ul class="nav nav-tabs">
          <li class="nav-item"><a class="nav-link active" href="#aboutgeneral" data-toggle="tab"><?php echo _("About"); ?></a></li>
          <li class="nav-item"><a class="nav-link" href="#aboutsponsors" data-toggle="tab"><?php echo _("Sponsors"); ?></a></li>
        </ul>
        <!-- /.nav-tabs -->

        <!-- Tab panes -->
        <div class="tab-content">
          <?php echo renderTemplate("about/general", $__template_data) ?>
          <?php echo renderTemplate("about/sponsors", $__template_data) ?>
        </div>
        <!-- /.tab-content -->

      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
