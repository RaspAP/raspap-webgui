<?php if (empty($networks)): ?>
  <div class="col-md-6 ms-6">
    <p class="lead text-center"><?php echo _('No Wifi stations found') ?></p>
    <p class="text-center"><?php echo _("Click 'Rescan' to search for nearby Wifi stations.") ?></p>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <p class="text-center"><?php echo _("Click 'Reinitialize' to force reinitialize <code>wpa_supplicant</code>.") ?></p>
    <form method="POST" action="wpa_conf" name="wpa_conf_form" class="row">
      <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
        <div class="col-xs me-3 mb-3">
          <input type="submit" class="btn btn-warning btn-block float-end" name="wpa_reinit" value="<?php echo _("Reinitialize"); ?>" />
        </div>
    </form>
    <?php endif ?>
  </div>
<?php endif ?>

<?php if (!empty($connected)): ?>
<h4 class="h-underlined my-3"><?php echo _("Connected") ?></h4>
<div class="card-grid">
	<?php foreach ($connected as $network) : ?>
	<?php $index = isset($network['index']) ? $network['index'] : -1; ?>
	<?php echo renderTemplate("wifi_stations/network", compact('network', 'index')) ?>
	<?php $index++; ?>
	<?php endforeach ?>
</div>
<?php endif ?>

<?php if (!empty($known)): ?>
<h4 class="h-underlined my-3"><?php echo _("Known") ?></h4>
<div class="card-grid">
	<?php foreach ($known as $network) : ?>
	<?php $index = isset($network['index']) ? $network['index'] : -1; ?>
	<?php echo renderTemplate("wifi_stations/network", compact('network', 'index')) ?>
	<?php $index++; ?>
	<?php endforeach ?>
</div>
<?php endif ?>

<?php if (!empty($nearby)): ?>
<h4 class="h-underlined my-3"><?php echo _("Nearby") ?></h4>
<div class="card-grid">
	<?php foreach ($nearby as $network) : ?>
	<?php $index = isset($network['index']) ? $network['index'] : -1; ?>
	<?php echo renderTemplate("wifi_stations/network", compact('network', 'index')) ?>
	<?php $index++; ?>
	<?php endforeach ?>
</div>
<?php endif ?>

<?php if (!empty($nmProfiles)): ?>
<h4 class="h-underlined my-3"><?php echo _("Other NetworkManager connections") ?></h4>
<p class="small text-muted"><?php echo _("These connections were not created in RaspAP. RaspAP can connect to them, but does not change or delete them.") ?></p>
<div class="table-responsive">
  <table class="table">
    <thead>
      <tr>
        <th><?php echo _("SSID") ?></th>
        <th><?php echo _("Connect automatically") ?></th>
        <th><?php echo _("Priority") ?></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($nmProfiles as $profile) : ?>
      <tr>
        <td>
          <?php echo htmlspecialchars($profile['ssidutf8'], ENT_QUOTES) ?>
          <?php if (!empty($profile['duplicate'])) : ?>
            <br><small class="text-muted"><?php echo _("Same network as a RaspAP network") ?></small>
          <?php endif ?>
        </td>
        <td><?php echo $profile['autoconnect'] ? _("Yes") : _("No") ?></td>
        <td><?php echo (int)$profile['priority'] ?></td>
        <td>
          <?php if ($profile['active']) : ?>
            <span class="text-success"><i class="fas fa-check me-1"></i><?php echo _("Connected") ?></span>
          <?php else : ?>
            <button type="submit" class="btn btn-sm btn-info" name="nm_connect" value="<?php echo htmlspecialchars($profile['uuid'], ENT_QUOTES) ?>"><?php echo _("Connect") ?></button>
          <?php endif ?>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php endif ?>
