<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
      <input type="submit" class="btn btn-outline btn-primary" value="<?php echo _("Save settings"); ?>" name="savedhcpdsettings" />
  <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<div class="row">
  <div class="col-lg-12">
    <div class="card">

      <div class="card-header page-card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <i class="fas fa-exchange-alt me-2"></i><?php echo _("DHCP Server"); ?>
          </div>
          <form method="POST" action="dhcpd_conf">
            <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
            <div class="btn-group" role="group">
              <?php if (!RASPI_MONITOR_ENABLED) : ?>
                  <?php if ($dnsmasq_state) : ?>
                    <button type="submit" class="btn btn-sm btn-danger" title="<?php echo _("Stop dnsmasq") ?>" name="stopdhcpd" >
                      <i class="fas fa-stop"></i>
                    </button>
                    <button type="submit" class="btn btn-sm btn-warning" title="<?php echo _("Restart dnsmasq") ?>" name="restartdhcpd" >
                      <i class="fas fa-sync-alt"></i>
                    </button>
                  <?php else : ?>
                    <button type="submit" class="btn btn-sm btn-light" title="<?php echo _("Start dnsmasq") ?>" name="startdhcpd" >
                      <i class="fas fa-play"></i>
                    </button>
                  <?php endif ?>
              <?php endif ?>
              <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
                <span class="text service-status">dnsmasq <?php echo _($serviceStatus) ?></span>
              </button>
            </div>
          </form>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form method="POST" action="dhcpd_conf" class="js-dhcp-settings-form needs-validation" novalidate>
          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>

          <!-- Nav tabs -->
          <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs mb-3">
              <li class="nav-item"><a class="nav-link active" href="#server-settings" data-bs-toggle="tab"><?php echo _("Server settings"); ?></a></li>
              <li class="nav-item"><a class="nav-link" href="#advanced" data-bs-toggle="tab"><?php echo _("Advanced"); ?></a></li>
              <li class="nav-item"><a class="nav-link" href="#static-leases" data-bs-toggle="tab"><?php echo _("Static Leases") ?></a></li>
              <li class="nav-item"><a class="nav-link" href="#client-list" data-bs-toggle="tab"><?php echo _("Client list"); ?></a></li>
              <li class="nav-item"><a class="nav-link" href="#logging" data-bs-toggle="tab"><?php echo _("Logging"); ?></a></li>
            </ul>
          </div>

          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("dhcp/general", $__template_data) ?>
            <?php echo renderTemplate("dhcp/advanced", $__template_data) ?>
            <?php echo renderTemplate("dhcp/clients", $__template_data) ?>
            <?php echo renderTemplate("dhcp/static_leases", $__template_data) ?>
            <?php echo renderTemplate("dhcp/logging", $__template_data) ?>
          </div><!-- /.tab-content -->

          <div class="d-flex flex-wrap gap-2">
            <?php echo $buttons ?>
          </div>
        </form>
      </div><!-- ./ card-body -->

      <div class="card-footer"> <?php echo _("Information provided by Dnsmasq"); ?></div>

    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
