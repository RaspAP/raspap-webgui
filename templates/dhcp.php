<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
      <input type="submit" class="btn btn-outline btn-primary" value="<?php echo _("Save settings"); ?>" name="savedhcpdsettings" />
      <?php if ($dnsmasq_state) : ?>
        <input type="submit" class="btn btn-warning" value="<?php echo _("Stop dnsmasq") ?>" name="stopdhcpd" />
      <?php else : ?>
        <input type="submit" class="btn btn-success" value="<?php echo _("Start dnsmasq") ?>" name="startdhcpd" />
      <?php endif ?>
  <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<div class="row">
  <div class="col-lg-12">
    <div class="card">

      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-exchange-alt mr-2"></i><?php echo _("DHCP Server"); ?>
          </div>
          <div class="col">
            <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
              <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
              <span class="text service-status">dnsmasq <?php echo _($serviceStatus) ?></span>
            </button>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form method="POST" action="?page=dhcpd_conf" class="js-dhcp-settings-form">
          <?php echo CSRFTokenFieldTag() ?>

          <!-- Nav tabs -->
          <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link active" href="#server-settings" data-toggle="tab"><?php echo _("Server settings"); ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#advanced" data-toggle="tab"><?php echo _("Advanced"); ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#static-leases" data-toggle="tab"><?php echo _("Static Leases") ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#client-list" data-toggle="tab"><?php echo _("Client list"); ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#logging" data-toggle="tab"><?php echo _("Logging"); ?></a></li>
          </ul>

          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("dhcp/general", $__template_data) ?>
            <?php echo renderTemplate("dhcp/advanced", $__template_data) ?>
            <?php echo renderTemplate("dhcp/clients", $__template_data) ?>
            <?php echo renderTemplate("dhcp/static_leases", $__template_data) ?>
            <?php echo renderTemplate("dhcp/logging", $__template_data) ?>
          </div><!-- /.tab-content -->

          <?php echo $buttons ?>
        </form>
      </div><!-- ./ card-body -->

      <div class="card-footer"> <?php echo _("Information provided by Dnsmasq"); ?></div>

    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
