<!-- static leases tab -->
<div class="tab-pane fade" id="static-leases">
  <h4 class="mt-3 mb-3"><?php echo _("Static leases") ?></h4>

  <div class="dhcp-static-leases js-dhcp-static-lease-container">
    <?php foreach ($dhcpHost as $host) : ?>
      <?php list($mac, $ip) = array_map("trim", explode(",", $host)); ?>
      <div class="row dhcp-static-lease-row js-dhcp-static-lease-row">
        <div class="col-md-5 col-xs-5">
          <input type="text" name="static_leases[mac][]" value="<?php echo htmlspecialchars($mac, ENT_QUOTES) ?>" placeholder="<?php echo _("MAC address") ?>" class="form-control">
        </div>
        <div class="col-md-5 col-xs-4">
          <input type="text" name="static_leases[ip][]" value="<?php echo htmlspecialchars($ip, ENT_QUOTES) ?>" placeholder="<?php echo _("IP address") ?>" class="form-control">
        </div>
        <div class="col-md-2 col-xs-3">
          <button type="button" class="btn btn-danger js-remove-dhcp-static-lease"><?php echo _("Remove") ?></button>
        </div>
      </div>
    <?php endforeach ?>
  </div>

  <h5 class="mt-3 mb-3"><?php echo _("Add static DHCP lease") ?></h5>
  <div class="row dhcp-static-lease-row js-new-dhcp-static-lease">
    <div class="col-md-5 col-xs-5">
      <input type="text" name="mac" value="" placeholder="<?php echo _("MAC address") ?>" class="form-control" autofocus="autofocus">
    </div>
    <div class="col-md-5 col-xs-4">
      <input type="text" name="ip" value="" placeholder="<?php echo _("IP address") ?>" class="form-control">
    </div>
    <div class="col-md-2 col-xs-3">
      <button type="button" class="btn btn-success js-add-dhcp-static-lease"><?php echo _("Add") ?></button>
    </div>
  </div>

  <template id="js-dhcp-static-lease-row">
    <div class="row dhcp-static-lease-row js-dhcp-static-lease-row">
      <div class="col-md-5 col-xs-5">
        <input type="text" name="static_leases[mac][]" value="{{ mac }}" placeholder="<?php echo _("MAC address") ?>" class="form-control">
      </div>
      <div class="col-md-5 col-xs-4">
        <input type="text" name="static_leases[ip][]" value="{{ ip }}" placeholder="<?php echo _("IP address") ?>" class="form-control">
      </div>
      <div class="col-md-2 col-xs-3">
        <button type="button" class="btn btn-warning js-remove-dhcp-static-lease"><?php echo _("Remove") ?></button>
      </div>
    </div>
  </template>
</div><!-- /.tab-pane -->
