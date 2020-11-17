<!-- advanced tab -->
<div class="tab-pane" id="advanced">

  <div class="row">
    <div class="col-md-6">
      <h5><?php echo _("Upstream DNS servers") ?></h5>
      <div class="input-group">
        <input type="hidden" name="no-resolv" value="0">
        <div class="custom-control custom-switch">
          <input class="custom-control-input" id="no-resolv" type="checkbox" name="no-resolv" value="1" <?php echo $conf['no-resolv'] ? ' checked="checked"' : "" ?> aria-describedby="no-resolv-description">
          <label class="custom-control-label" for="no-resolv"><?php echo _("Only ever query DNS servers configured below") ?></label>
        </div>
        <p id="no-resolv-description">
          <small><?php echo _("Enable this option if you want RaspAP to <b>send DNS queries to the servers configured below exclusively</b>. By default RaspAP also uses its upstream DHCP server's name servers.") ?></small>
          <br><small class="text-muted"><?php echo _("This option adds <code>no-resolv</code> to the dnsmasq configuration.") ?></small>
        </p>
      </div>

      <div class="js-dhcp-upstream-servers">
        <?php foreach ($upstreamServers as $server): ?>
          <div class="form-group input-group input-group-sm js-dhcp-upstream-server">
            <input type="text" class="form-control" name="server[]" value="<?php echo $server ?>">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary js-remove-dhcp-upstream-server" type="button"><i class="fas fa-minus"></i></button>
            </div>
          </div>
        <?php endforeach ?>
      </div>

      <div class="form-group">
        <label for="add-dhcp-upstream-server-field"><?php echo _("Add upstream DNS server") ?></label>
        <div class="input-group">
          <input type="text" class="form-control" id="add-dhcp-upstream-server-field" aria-describedby="new-dhcp-upstream-server" placeholder="<?php printf(_("e.g. %s"), "208.67.222.222") ?>">
          <div class="input-group-append">
            <button type="button" class="btn btn-outline-secondary js-add-dhcp-upstream-server"><i class="fas fa-plus"></i></button>
          </div>
        </div>
        <p id="new-dhcp-upstream-server" class="form-text text-muted">
          <small>
            <?php echo _("Format: ") ?>
            <code class="text-muted"><?php echo htmlspecialchars("[/[<domain>]/[domain/]][<ipaddr>[#<port>][@<source-ip>|<interface>[#<port>]]"); ?></code>
          </small>
        </p>
        <select class="custom-select custom-select-sm js-field-preset" id="cbxdhcpupstreamserver" data-field-preset-target="#add-dhcp-upstream-server-field">
          <option value=""><?php echo _("Choose a hosted server") ?></option>
          <option disabled="disabled"></option>
          <?php echo optionsForSelect(dnsServers()) ?>
        </select>
      </div>
    </div>

    <template id="dhcp-upstream-server">
      <div class="form-group input-group input-group-sm js-dhcp-upstream-server">
        <input type="text" class="form-control" name="server[]" value="{{ server }}">
        <div class="input-group-append">
          <button class="btn btn-outline-secondary js-remove-dhcp-upstream-server" type="button"><i class="fas fa-minus"></i></button>
        </div>
      </div>
    </template>
  </div><!-- /.row -->

</div><!-- /.tab-pane | advanded tab -->
