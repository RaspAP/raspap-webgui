<?php if (empty($networks)) { ?>
  <div class="col-md-6 ml-6">
    <p class="lead text-center"><?php echo _('No Wifi stations found') ?></p>
    <p class="text-center"><?php echo _('Click "Rescan" to search for nearby Wifi stations.') ?></p>
  </div>
<?php } elseif (count($networks) == 1) { 
    $prop_col = "col-sm-12";
    $prop_w = "w-50";
} else {
    $prop_col = "col-sm-6";
    $prop_w = "w-100";
}
?>
<?php $index = 0; ?>
<div class="row ml-1 mr-1 w-100">
 <?php foreach ($networks as $ssid => $network) : ?>
    <div class="<?php echo $prop_col; ?> align-items-stretch mb-3">
    <div class="card h-100 <?php echo $prop_w; ?>">
      <div class="card-body">
        <input type="hidden" name="ssid<?php echo $index ?>" value="<?php echo htmlentities($ssid, ENT_QUOTES) ?>" />
        <?php if (strlen($ssid) == 0) {
            $ssid = "(unknown)";
        } ?>
        <h5 class="card-title"><?php echo htmlspecialchars($ssid, ENT_QUOTES); ?></h5>

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
        <?php
        if (isset($network['RSSI']) && $network['RSSI'] >= -200) {
            echo htmlspecialchars($network['RSSI'], ENT_QUOTES);
            echo "dB (";
            if ($network['RSSI'] >= -50) {
                echo 100;
            } elseif ($network['RSSI'] <= -100) {
                echo 0;
            } else {
                echo  2*($network['RSSI'] + 100);
            }
            echo "%)";
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
        <div><?php echo $network['protocol'] ?></div>

        <div class="form-group">
          <div class="info-item-wifi"><?php echo _("Passphrase"); ?></div>
              <div class="input-group">
            <?php if ($network['protocol'] === 'Open') { ?>
              <input type="password" disabled class="form-control" aria-describedby="passphrase" name="passphrase<?php echo $index ?>" value="" />
            <?php } else { ?>
              <input type="password" class="form-control js-validate-psk" aria-describedby="passphrase" name="passphrase<?php echo $index ?>" value="<?php echo $network['passphrase'] ?>" data-target="#update<?php echo $index ?>" data-colors="#ffd0d0,#d0ffd0">
              <div class="input-group-append">
            <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="[name=passphrase<?php echo $index ?>]" data-toggle-with="<?php echo _("Hide") ?>">Show</button>
              </div>
            <?php } ?>
          </div>
        </div>

        <div class="btn-group btn-block ">
            <?php if ($network['configured']) { ?>
              <input type="submit" class="col-xs-4 col-md-4 btn btn-warning" value="<?php echo _("Update"); ?>" id="update<?php echo $index ?>" name="update<?php echo $index ?>"<?php echo ($network['protocol'] === 'Open' ? ' disabled' : '')?> />
              <button type="submit" class="col-xs-4 col-md-4 btn btn-info" value="<?php echo $index?>" name="connect"><?php echo _("Connect"); ?></button>
            <?php } else { ?>
              <input type="submit" class="col-xs-4 col-md-4 btn btn-info" value="<?php echo _("Add"); ?>" id="update<?php echo $index ?>" name="update<?php echo $index ?>" <?php echo ($network['protocol'] === 'Open' ? '' : ' disabled')?> />
            <?php } ?>
              <input type="submit" class="col-xs-4 col-md-4 btn btn-danger" value="<?php echo _("Delete"); ?>" name="delete<?php echo $index ?>"<?php echo ($network['configured'] ? '' : ' disabled')?> />
        </div><!-- /.btn-group -->
      </div><!-- /.card-body -->
    </div><!-- /.card -->
  </div><!-- /.col-sm -->
  <?php $index += 1; ?>
  <?php endforeach ?>
</div><!-- /.row -->

