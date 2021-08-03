<!-- static leases tab -->
<div class="tab-pane fade" id="static-leases">
  <div class="row">
    <div class="col-md-12">
      <h4 class="mt-3 mb-3"><?php echo _("Static leases") ?></h4>
      <p id="static-lease-description">
        <small><?php echo _("Clients with a particular hardware MAC address can always be allocated the same IP address.") ?></small>
        <small class="text-muted"><?php echo _("This option adds <code>dhcp-host</code> entries to the dnsmasq configuration.") ?></small>
      </p>
      <div class="dhcp-static-leases js-dhcp-static-lease-container">
        <?php foreach ($hosts as $host) : ?>
          <?php list($host, $comment) = array_map("trim", explode("#", $host)); ?>
          <?php list($mac, $ip) = array_map("trim", explode(",", $host)); ?>
          <div class="row dhcp-static-lease-row js-dhcp-static-lease-row">
            <div class="col-md-4 col-xs-3">
              <input type="text" name="static_leases[mac][]" value="<?php echo htmlspecialchars($mac, ENT_QUOTES) ?>" placeholder="<?php echo _("MAC address") ?>" class="form-control">
            </div>
            <div class="col-md-3 col-xs-3">
              <input type="text" name="static_leases[ip][]" value="<?php echo htmlspecialchars($ip, ENT_QUOTES) ?>" placeholder="<?php echo _("IP address") ?>" class="form-control">
            </div>
            <div class="col-md-3 col-xs-3">
              <input type="text" name="static_leases[comment][]" value="<?php echo htmlspecialchars($comment, ENT_QUOTES) ?>" placeholder="<?php echo _("Optional comment") ?>" class="form-control">
            </div>
            <div class="col-md-2 col-xs-3">
              <button type="button" class="btn btn-outline-danger js-remove-dhcp-static-lease"><i class="far fa-trash-alt"></i></button>
            </div>
          </div>
        <?php endforeach ?>
      </div>

      <div class="row dhcp-static-lease-row js-new-dhcp-static-lease">
        <div class="col-md-4 col-xs-3">
          <input type="text" name="mac" value="" placeholder="<?php echo _("MAC address") ?>" class="form-control" autofocus="autofocus">
        </div>
        <div class="col-md-3 col-xs-3">
          <input type="text" name="ip" value="" placeholder="<?php echo _("IP address") ?>" class="form-control">
        </div>
        <div class="col-md-3 col-xs-3">
          <input type="text" name="comment" value="" placeholder="<?php echo _("Optional comment") ?>" class="form-control">
        </div>
        <div class="col-md-2 col-xs-3">
          <button type="button" class="btn btn-outline-success js-add-dhcp-static-lease"><i class="far fa-plus-square"></i></button>
        </div>
      </div>

      <h5 class="mt-3 mb-3"><?php echo _("Restrict access") ?></h5>
        <div class="input-group">
          <input type="hidden" name="dhcp-ignore" value="0">
            <div class="custom-control custom-switch">
              <input class="custom-control-input" id="dhcp-ignore" type="checkbox" name="dhcp-ignore" value="1" <?php echo $conf['dhcp-ignore'] ? ' checked="checked"' : "" ?> aria-describedby="dhcp-ignore-description">
              <label class="custom-control-label" for="dhcp-ignore"><?php echo _("Limit network access to static clients") ?></label>
            </div>
            <p id="dhcp-ignore-description">
              <small><?php echo _("Enable this option if you want RaspAP to <b>ignore any clients</b> which are not specified in the static leases list.") ?></small>
              <small class="text-muted"><?php echo _("This option adds <code>dhcp-ignore</code> to the dnsmasq configuration.") ?></small>
            </p>
          </div>
        </div>
      </div>

    <template id="js-dhcp-static-lease-row">
      <div class="row dhcp-static-lease-row js-dhcp-static-lease-row">
        <div class="col-md-4 col-xs-3">
          <input type="text" name="static_leases[mac][]" value="{{ mac }}" placeholder="<?php echo _("MAC address") ?>" class="form-control">
        </div>
        <div class="col-md-3 col-xs-3">
          <input type="text" name="static_leases[ip][]" value="{{ ip }}" placeholder="<?php echo _("IP address") ?>" class="form-control">
        </div>
        <div class="col-md-3 col-xs-3">
          <input type="text" name="static_leases[comment][]" value="{{ comment }}" placeholder="<?php echo _("Optional comment") ?>" class="form-control">
        </div>
        <div class="col-md-2 col-xs-3">
          <button type="button" class="btn btn-outline-danger js-remove-dhcp-static-lease"><i class="far fa-trash-alt"></i></button>
        </div>
      </div>
    </template>

</div><!-- /.tab-pane -->

