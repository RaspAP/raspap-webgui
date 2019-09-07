<div class="row">
<div class="col-lg-12">
  <div class="panel panel-primary">
  <div class="panel-heading">
    <i class="fa fa-exchange fa-fw"></i> <?php echo _("Configure DHCP"); ?>
    <span class="label pull-right service-status-<?php echo $serviceStatus ?>">dnsmasq <?php echo _($serviceStatus) ?></span>
  </div>
    <!-- /.panel-heading -->
    <div class="panel-body">
    <?php $status->showMessages(); ?>
    <form method="POST" action="?page=dhcpd_conf" class="js-dhcp-settings-form">
    <?php echo CSRFTokenFieldTag() ?>
    <!-- Nav tabs -->
        <ul class="nav nav-tabs">
            <li class="active"><a href="#server-settings" data-toggle="tab"><?php echo _("Server settings"); ?></a>
            </li>
            <li><a href="#static-leases" data-toggle="tab"><?php echo _("Static Leases") ?></a></li>
            <li><a href="#client-list" data-toggle="tab"><?php echo _("Client list"); ?></a>
            </li>
        </ul>
    <!-- Tab panes -->
    <div class="tab-content">
<div class="tab-pane fade in active" id="server-settings">
<h4>DHCP server settings</h4>
<div class="row">
  <div class="form-group col-md-4">
    <label for="code">Interface</label>
    <select class="form-control" name="interface">
    <?php foreach ($interfaces as $if) : ?>
        <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
        <?php $selected  = $if === $conf['interface'] ? ' selected="selected"' : '' ?>
      <option value="<?php echo $if_quoted ?>"<?php echo $selected ?>><?php echo $if_quoted ?></option>
    <?php endforeach ?>
    </select>
  </div>
</div>
<div class="row">
  <div class="form-group col-md-4">
    <label for="code"><?php echo _("Starting IP Address"); ?></label>
    <input type="text" class="form-control"name="RangeStart" value="<?php echo htmlspecialchars($RangeStart, ENT_QUOTES); ?>" />
  </div>
</div>

<div class="row">
  <div class="form-group col-md-4">
    <label for="code"><?php echo _("Ending IP Address"); ?></label>
    <input type="text" class="form-control" name="RangeEnd" value="<?php echo htmlspecialchars($RangeEnd, ENT_QUOTES); ?>" />
  </div>
</div>

<div class="row">
  <div class="form-group col-xs-2 col-sm-2">
    <label for="code"><?php echo _("Lease Time"); ?></label>
    <input type="text" class="form-control" name="RangeLeaseTime" value="<?php echo htmlspecialchars($arrRangeLeaseTime[1], ENT_QUOTES); ?>" />
  </div>
  <div class="col-xs-2 col-sm-2">
    <label for="code"><?php echo _("Interval"); ?></label>
    <select name="RangeLeaseTimeUnits" class="form-control" >
      <option value="m"<?php echo $mselected; ?>><?php echo _("Minute(s)"); ?></option>
      <option value="h"<?php echo $hselected; ?>><?php echo _("Hour(s)"); ?></option>
      <option value="d"<?php echo $dselected; ?>><?php echo _("Day(s)"); ?></option>
      <option value="infinite"<?php echo $infiniteselected; ?>><?php echo _("Infinite"); ?></option>
    </select>
  </div>
</div>
<?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" class="btn btn-outline btn-primary" value="<?php echo _("Save settings"); ?>" name="savedhcpdsettings" />
    <?php if ($dnsmasq_state) : ?>
      <input type="submit" class="btn btn-warning" value="<?php echo _("Stop dnsmasq") ?>" name="stopdhcpd" />
    <?php else : ?>
      <input type="submit" class="btn btn-success" value="<?php echo _("Start dnsmasq") ?>" name="startdhcpd" />
    <?php endif ?>
<?php endif ?>
</div><!-- /.tab-pane -->

<div class="tab-pane fade in" id="client-list">
<h4>Client list</h4>
<div class="row">
<div class="col-lg-12">
  <div class="panel panel-default">
  <div class="panel-heading"><?php echo _("Active DHCP leases"); ?></div>
  <!-- /.panel-heading -->
  <div class="panel-body">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th><?php echo _("Expire time"); ?></th>
            <th><?php echo _("MAC Address"); ?></th>
            <th><?php echo _("IP Address"); ?></th>
            <th><?php echo _("Host name"); ?></th>
            <th><?php echo _("Client ID"); ?></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($leases as $lease) : ?>
            <?php foreach (explode(' ', $lease) as $prop) : ?>
            <tr>
              <td><?php echo htmlspecialchars($prop, ENT_QUOTES) ?></td>
            </tr>
            <?php endforeach ?>
        <?php endforeach ?>
        </tbody>
      </table>
    </div><!-- /.table-responsive -->
  </div><!-- /.panel-body -->
  </div><!-- /.panel -->
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->
</div><!-- /.tab-pane -->

<div class="tab-pane fade in" id="static-leases">
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

    <h5><?php echo _("Add static DHCP lease") ?></h5>
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
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
        <input type="submit" class="btn btn-outline btn-primary" value="<?php echo _("Save settings"); ?>" name="savedhcpdsettings" />
        <?php
        if ($dnsmasq_state) {
            echo '<input type="submit" class="btn btn-warning" value="' . _("Stop dnsmasq") . '" name="stopdhcpd" />';
        } else {
            echo'<input type="submit" class="btn btn-success" value="' .  _("Start dnsmasq") . '" name="startdhcpd" />';
        }
        ?>
    <?php endif ?>
</div>

</div><!-- /.tab-content -->
</form>
</div><!-- ./ Panel body -->
<div class="panel-footer"> <?php echo _("Information provided by Dnsmasq"); ?></div>
    </div><!-- /.panel-primary -->
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->
