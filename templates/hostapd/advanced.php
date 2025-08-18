<div class="tab-pane fade" id="advanced">
  <h4 class="mt-3"><?php echo _("Advanced settings"); ?></h4>
    <div class="row">
      <div class="col-md-6 mb-2">
        <div class="form-check form-switch">
          <?php $checked = $arrHostapdConf['BridgedEnable'] == 1 ? 'checked="checked"' : '' ?>
          <?php $disabled = strpos(strtolower($operatingSystem),'ubuntu') !== false ? 'disabled="disabled"' : '' ?>
          <input class="form-check-input" id="chxbridgedenable" name="bridgedEnable" type="checkbox" value="1" <?php echo $checked ?> <?php echo $disabled ?> />
          <label class="form-check-label" for="chxbridgedenable"><?php echo _("Bridged AP mode"); ?></label>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-2">
        <div class="form-check form-switch">
          <?php $checked = $arrHostapdConf['WifiAPEnable'] == 1 ? 'checked="checked"' : '' ?>
          <?php $disabled = $managedModeEnabled == false && $arrHostapdConf['WifiAPEnable'] != 1 || $arrHostapdConf['BridgedEnable'] == 1 ? 'disabled="disabled"' : '' ?>
          <input class="form-check-input" id="chxwificlientap" name="wifiAPEnable" type="checkbox" value="1" <?php echo $checked ?> <?php echo $disabled ?> />
          <label class="form-check-label" for="chxwificlientap"><?php echo _("WiFi client AP mode"); ?></label>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-2">
        <div class="form-check form-switch">
          <?php $checked = $arrConfig['ignore_broadcast_ssid'] == 1 || $arrConfig['ignore_broadcast_ssid'] == 2 ? 'checked="checked"' : '' ?>
          <input class="form-check-input" id="chxhiddenssid" name="hiddenSSID" type="checkbox" value="1" <?php echo $checked ?> />
          <label class="form-check-label" for="chxhiddenssid"><?php echo _("Hide SSID in broadcast"); ?></label>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-2">
        <div class="form-check form-switch">
          <?php $checked = $arrConfig['ap_isolate'] == 1 ? 'checked="checked"' : '' ?>
          <input class="form-check-input" id="chxapisolate" name="ap_isolate" type="checkbox" value="1" <?php echo $checked ?> />
          <label class="form-check-label" for="chxapisolate"><?php echo _("Enable AP isolation"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" title="<?php echo _("Blocks wireless clients from seeing or connecting to each other. Recommended for guest networks and public access points."); ?>"></i>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-3 col-sm-3">
        <div class="form-check form-switch">
          <?php $checked = $arrConfig['beacon_interval_bool'] == 1 ? 'checked="checked"' : '' ?>
          <input class="form-check-input" id="chxbeaconinterval" name="beaconintervalEnable" type="checkbox" value="1" <?php echo $checked ?> />
          <label class="form-check-label" for="chxbeaconinterval"><?php echo _("Beacon interval"); ?></label>
        </div>
      </div>
      <div class="col-xs-3 col-sm-3">
        <input type="text" class="form-control" name="beacon_interval" value="<?php echo $arrConfig['beacon_int'] ?>">
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-2">
        <div class="form-check form-switch">
          <?php $checked = $arrConfig['disassoc_low_ack_bool'] == 1 ? 'checked="checked"' : '' ?>
          <input class="form-check-input" id="chxdisassoclowack" name="disassoc_low_ackEnable" type="checkbox" value="1" <?php echo $checked ?> />
          <label class="form-check-label" for="chxdisassoclowack"><?php echo _("Disable <code>disassoc_low_ack</code>"); ?></label>
        </div>
        <p id="disassoc_low_ack_help" class="mb-1 mt-0">
          <small id="disassoc_low_ack_help" class="text-muted"><?php echo _("Do not disassociate stations based on excessive transmission failures.") ?></small></label>
        </p>
      </div>
    </div>
    <div class="row">
      <div class="mb-3 col-md-6">
        <label for="cbxtxpower"><?php echo _("Transmit power (dBm)") ?></label>
        <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" title="<?php echo _("dBm is a unit of level used to indicate that a power ratio is expressed in decibels (dB) with reference to one milliwatt (mW). 30 dBm is equal to 1000 mW, while 0 dBm equals 1.25 mW."); ?>"></i>
        <?php SelectorOptions('txpower', $arrTxPower, $txpower, 'cbxtxpower'); ?>
        <small id="txpower_help" class="text-muted"><?php echo _("Sets the <code>txpower</code> option for the AP interface and the configured country."); ?></small>
      </div>
    </div>

    <div class="row">
      <div class="mb-3 col-md-6">
        <label for="max_num_sta"><?php echo _("Maximum number of clients") ?></label>
        <input type="text" id="max_num_sta" class="form-control" name="max_num_sta" placeholder="2007" value="<?php echo $arrConfig["max_num_sta"] ?>" aria-describedby="max_num_sta_help">
        <small id="max_num_sta_help" class="text-muted"><?php echo _("Configures the <code>max_num_sta</code> option of hostapd. The default and maximum is 2007. If empty or 0, the default applies.") ?></small>
      </div>
    </div>
    <div class="row">
      <div class="mb-3 col-md-6">
      <label for="cbxcountries"><?php echo _("Country Code"); ?></label>
      <input type="hidden" id="selected_country" value="<?php echo htmlspecialchars($arrConfig['country_code'], ENT_QUOTES); ?>">
      <?php SelectorOptions('country_code', $countryCodes, $arrConfig['country_code'], 'cbxcountries', 'loadChannelSelect'); ?>
      <script type="text/javascript">
      var country = document.getElementById("selected_country").value;
      var countries = document.getElementById("cbxcountries");
      var ops = countries.getElementsByTagName("option");
      for (var i = 0; i < ops.length; ++i) {
        if(ops[i].value == country){
          ops[i].selected=true;
          break;
        }
      }
      </script>
    </div>
  </div>
</div><!-- /.tab-pane | advanded tab -->
