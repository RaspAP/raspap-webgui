<?php if (empty($networks)): ?>
  <div class="col-md-6 ml-6">
    <p class="lead text-center"><?php echo _('No Wifi stations found') ?></p>
    <p class="text-center"><?php echo _("Click 'Rescan' to search for nearby Wifi stations.") ?></p>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <p class="text-center"><?php echo _("Click 'Reinitialize' to force reinitialize <code>wpa_supplicant</code>.") ?></p>
    <form method="POST" action="wpa_conf" name="wpa_conf_form" class="row">
      <?php echo CSRFTokenFieldTag() ?>
        <div class="col-xs mr-3 mb-3">
          <input type="submit" class="btn btn-warning btn-block float-right" name="wpa_reinit" value="<?php echo _("Re-initialize"); ?>" />
        </div>
    </form>
    <?php endif ?>
  </div>
<?php endif ?>

<?php $index = 0; ?>

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
