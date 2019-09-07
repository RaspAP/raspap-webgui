<?php if (empty($networks)) : ?>
  <p class="lead text-center"><?php echo _('No Wifi stations found') ?></p>
  <p class="text-center"><?php echo _('Click "Rescan" to search for nearby Wifi stations.') ?></p>
<?php endif ?>
<?php $index = 0; ?>
<?php foreach ($networks as $ssid => $network) : ?>
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-body">

      <input type="hidden" name="ssid<?php echo $index ?>" value="<?php echo htmlentities($ssid, ENT_QUOTES) ?>" />
      <h4><?php echo htmlspecialchars($ssid, ENT_QUOTES); ?></h4>

      <div class="row">
        <div class="col-xs-4 col-md-4">Status</div>
        <div class="col-xs-4 col-md-4">
            <?php if ($network['configured']) { ?>
            <i class="fa fa-check-circle fa-fw"></i>
            <?php } ?>
            <?php if ($network['connected']) { ?>
            <i class="fa fa-exchange fa-fw"></i>
            <?php } ?>
         </div>
      </div>

      <div class="row">
        <div class="col-xs-4 col-md-4">Channel</div>
        <div class="col-xs-4 col-md-4">
            <?php if ($network['visible']) { ?>
                <?php echo htmlspecialchars($network['channel'], ENT_QUOTES) ?>
            <?php } else { ?>
              <span class="label label-warning"> X </span>
            <?php } ?>
        </div>
      </div>

      <div class="row">
        <div class="col-xs-4 col-md-4">RSSI</div>
        <div class="col-xs-6 col-md-6">
        <?php echo htmlspecialchars($network['RSSI'], ENT_QUOTES);
            echo "dB (";
        if ($network['RSSI'] >= -50) {
            echo 100;
        } elseif ($network['RSSI'] <= -100) {
            echo 0;
        } else {
            echo  2*($network['RSSI'] + 100);
        }
            echo "%)";
        ?>
        </div>
      </div>

        <?php if (array_key_exists('priority', $network)) { ?>
          <input type="hidden" name="priority<?php echo $index ?>" value="<?php echo htmlspecialchars($network['priority'], ENT_QUOTES); ?>" />
        <?php } ?>
      <input type="hidden" name="protocol<?php echo $index ?>" value="<?php echo htmlspecialchars($network['protocol'], ENT_QUOTES); ?>" />

      <div class="row">
        <div class="col-xs-4 col-md-4">Security</div>
        <div class="col-xs-6 col-md-6"><?php echo $network['protocol'] ?></div>
      </div>

      <div class="form-group">
        <div class="input-group col-xs-12 col-md-12">
          <span class="input-group-addon" id="passphrase">Passphrase</span>
            <?php if ($network['protocol'] === 'Open') { ?>
              <input type="password" disabled class="form-control" aria-describedby="passphrase" name="passphrase<?php echo $index ?>" value="" />
            <?php } else { ?>
              <input type="password" class="form-control js-validate-psk" aria-describedby="passphrase" name="passphrase<?php echo $index ?>" value="<?php echo $network['passphrase'] ?>" data-target="#update<?php echo $index ?>" data-colors="#ffd0d0,#d0ffd0">
              <span class="input-group-btn">
                <button class="btn btn-default js-toggle-password" type="button" data-target="[name=passphrase<?php echo $index ?>]" data-toggle-with="<?php echo _("Hide") ?>">Show</button>
              </span>
            <?php } ?>
        </div>
      </div>

      <div class="btn-group btn-block ">
        <?php if ($network['configured']) { ?>
            <input type="submit" class="col-xs-4 col-md-4 btn btn-warning" value="<?php echo _("Update"); ?>" id="update<?php echo $index ?>" name="update<?php echo $index ?>"<?php echo ($network['protocol'] === 'Open' ? ' disabled' : '')?> />
            <button type="submit" class="col-xs-4 col-md-4 btn btn-info" value="<?php echo $index?>" ><?php echo _("Connect"); ?></button>
        <?php } else { ?>
            <input type="submit" class="col-xs-4 col-md-4 btn btn-info" value="<?php echo _("Add"); ?>" id="update<?php echo $index ?>" name="update<?php echo $index ?>" <?php echo ($network['protocol'] === 'Open' ? '' : ' disabled')?> />
        <?php } ?>
            <input type="submit" class="col-xs-4 col-md-4 btn btn-danger" value="<?php echo _("Delete"); ?>" name="delete<?php echo $index ?>"<?php echo ($network['configured'] ? '' : ' disabled')?> />
      </div><!-- /.btn-group -->

    </div><!-- /.panel-body -->
  </div><!-- /.panel-default -->
</div><!-- /.col-md-6 -->

    <?php $index += 1; ?>
<?php endforeach ?>