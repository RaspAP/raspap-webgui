<?php

use RaspAP\Networking\Hotspot\WiFiManager;
$wifi = new WiFiManager();

?>
<div class="card">
	<div class="card-body">
		<input type="hidden" name="ssid<?php echo $index ?>" value="<?php echo htmlentities($network['ssid'], ENT_QUOTES) ?>" />
		<?php if (strlen($network['ssid']) == 0) {
			$network['ssid'] = "(unknown)";
		} ?>
		<h5 class="card-title"><i class="fas fa-wifi me-2"></i><?php echo htmlspecialchars($network['ssidutf8'], ENT_QUOTES); ?></h5>
		<div class="info-item-wifi"><?php echo _("Status"); ?></div>
		<div>
			<?php if ($network['configured']) { ?>
				<i class="fas fa-check-circle"></i>
			<?php } ?>
			<?php if ($network['connected']) { ?>
				<i class="fas fa-exchange-alt"></i>
			<?php } ?>
			<?php if (!$network['configured'] && !$network['connected']) {
				echo _("Not configured");
			} ?>
		</div>

		<div class="info-item-wifi"><?php echo _("Channel"); ?></div>
		<div>
			<?php if ($network['visible']) { ?>
				<?php echo htmlspecialchars($network['channel'], ENT_QUOTES) ?>
			<?php } else { ?>
				<span class="label label-warning"> X </span>
			<?php } ?>
		</div>

		<div class="info-item-wifi"><?php echo _("RSSI"); ?></div>
		<div>
			<?php if (isset($network['RSSI']) && $network['RSSI'] >= -200) {
                echo '<div class="d-flex justify-content-start">';
                echo $wifi->getSignalBars($network['RSSI']);
                echo '<div class="ms-2">' .htmlspecialchars($network['RSSI'], ENT_QUOTES) . "dB" . "</div>";
                echo '</div>';
			} else {
				echo " not found ";
            }
			?>
		</div>

		<?php if (array_key_exists('priority', $network)) { ?>
			<input type="hidden" name="priority<?php echo $index ?>" value="<?php echo htmlspecialchars($network['priority'], ENT_QUOTES); ?>" />
		<?php } ?>
		<input type="hidden" name="protocol<?php echo $index ?>" value="<?php echo htmlspecialchars($network['protocol'], ENT_QUOTES); ?>" />

        <div class="info-item-wifi"><?php echo _("Security"); ?></div>
        <div><?php echo empty($network['protocol']) ? "-" : $network['protocol'] ?></div>

		<div class="mb-3">
			<div class="info-item-wifi mb-2"><?php echo _("Passphrase"); ?></div>
			<div class="input-group">
				<?php if ($network['protocol'] === 'Open') { ?>
					<input type="password" disabled class="form-control" aria-describedby="passphrase" name="passphrase<?php echo $index ?>" value="" />
				<?php } else { ?>
					<input type="password" class="form-control" aria-describedby="passphrase" name="passphrase<?php echo $index ?>" value="<?php echo htmlspecialchars($network['passphrase']); ?>" data-bs-target="#update<?php echo $index ?>" data-colors="#ffd0d0,#d0ffd0">
					<div class="input-group-text js-toggle-password" data-bs-target="[name=passphrase<?php echo $index ?>]" data-toggle-with="fas fa-eye-slash"><i class="fas fa-eye mx-2"></i></div>
				<?php } ?>
			</div>
		</div>

		<div class="btn-group btn-block d-flex">
			<?php if ($network['configured']) { ?>
				<input type="submit" class="btn btn-warning" value="<?php echo _("Update"); ?>" id="update<?php echo $index ?>" name="update<?php echo $index ?>"<?php echo ($network['protocol'] === 'Open' ? ' disabled' : '')?> data-bs-toggle="modal" data-bs-target="#configureClientModal" />
				<button type="submit" class="btn btn-info" value="<?php echo $index?>" name="connect"><?php echo _("Connect"); ?></button>
			<?php } else { ?>
				<input type="submit" class="btn btn-info" value="<?php echo _("Add"); ?>" id="update<?php echo $index ?>" name="update<?php echo $index ?>" data-bs-toggle="modal" data-bs-target="#configureClientModal" />
			<?php } ?>
			<input type="submit" class="btn btn-danger" value="<?php echo _("Delete"); ?>" name="delete<?php echo $index ?>"<?php echo ($network['configured'] ? '' : ' disabled')?> data-bs-toggle="modal" data-bs-target="#configureClientModal" />
		</div><!-- /.btn-group -->
	</div><!-- /.card-body -->
</div><!-- /.card -->
