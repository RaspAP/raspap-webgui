
<div class="row">
  <div class="col-lg-12">
    <div class="card shadow">
      <div class="card-header page-card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-network-wired me-2"></i><?php echo _("Networking"); ?>
          </div>
        </div><!-- ./row -->
      </div><!-- ./card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="network_conf" method="POST" class="needs-validation" novalidate>
          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
          <div id="msgNetworking"></div>
          <!-- Nav tabs -->
          <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs">
              <li class="nav-item"><a class="nav-link active" href="#summary" aria-controls="summary" role="tab" data-bs-toggle="tab"><?php echo _("Summary"); ?></a></li>
              <?php if (!$bridgedEnabled) : // no interface details when bridged ?>
                <li class="nav-item"><a class="nav-link" href="#netdevices" aria-controls="netdevices" role="tab" data-bs-toggle="tab"><?php echo _("Devices"); ?></a></li>
                <li class="nav-item"><a class="nav-link" href="#mobiledata" aria-controls="mobiledata" role="tab" data-bs-toggle="tab"><?php echo _("Mobile Data"); ?></a></li>
                <li class="nav-item"><a class="nav-link" href="#wlanrouting" aria-controls="wlanrouting" role="tab" data-bs-toggle="tab"><?php echo _("WLAN Routing"); ?></a></li>
                <li class="nav-item"><a class="nav-link" href="#diagnostic" aria-controls="diagnostic" role="tab" data-bs-toggle="tab"><?php echo _("Diagnostics"); ?></a></li>
              <?php endif ?>
            </ul>
          </div>

          <!-- Tab panes -->
          <div class="tab-content">
              <?php echo renderTemplate("networking/general", $__template_data) ?>
              <?php echo renderTemplate("networking/devices", $__template_data) ?>
              <?php echo renderTemplate("networking/mobile", $__template_data) ?>
              <?php echo renderTemplate("networking/wlanrouting", $__template_data) ?>
              <?php echo renderTemplate("networking/diagnostics", $__template_data) ?>
          </div><!-- /.tab-content -->
        </form>
      </div><!-- /.card-body -->

      <div class="card-footer"><?php echo _("Information provided by /sys/class/net"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

