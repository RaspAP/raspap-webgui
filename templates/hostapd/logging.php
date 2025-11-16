<!-- logfile output tab -->
<div class="tab-pane fade" id="logoutput">
  <h4 class="mt-3"><?php echo _("Logging"); ?></h4>
  <p><?php echo _("Enable this option to log <code>hostapd</code> activity.") ?></p>

  <div class="form-check form-switch">
    <?php $checked = $arrHostapdConf['LogEnable'] == 1 ? 'checked="checked"' : '' ?>
    <input class="form-check-input" id="chxlogenable" name="logEnable" type="checkbox" value="1" <?php echo $checked ?> />
    <label class="form-check-label align-middle" for="chxlogenable"><?php echo _("Logfile output"); ?></label>
    <input type="button" class="btn btn-outline btn-warning btn-sm align-top ms-2" id="js-clearhostapd-log" value="<?php echo _("Clear log"); ?>" />
  </div>

  <div class="row">
    <div class="mb-3 col-md-8 mt-2">
      <?php
      if ($arrHostapdConf['LogEnable'] == 1) {
          echo '<textarea class="logoutput text-secondary" id="hostapd-log">'.htmlspecialchars(implode("\n", $logOutput), ENT_QUOTES).'</textarea>';
      } else {
          echo '<textarea class="logoutput my-3"></textarea>';
      }
      ?>
     </div>
  </div>
</div><!-- /.tab-pane | logging tab -->
