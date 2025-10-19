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

    <!-- static IP settings -->
    <div class="row" id="bridgeStaticIpSection" style="display: <?php echo $arrHostapdConf['BridgedEnable'] == 1 ? 'block' : 'none' ?>;">
      <div class="col-md-12 mb-3">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title"><?php echo _("Bridge interface configuration"); ?></h6>
            <p class="text-muted small mb-3">
              <?php echo _("Configure a static IP address for the <code>br0</code> interface to maintain connectivity during bridge mode activation."); ?>
            </p>
            
            <div class="row g-3">
              <div class="col-md-6">
                <label for="bridgeStaticIp" class="form-label"><?php echo _("Static IP Address"); ?></label>
                <div class="input-group has-validation">
                  <input type="text" class="form-control ip_address" id="bridgeStaticIp" name="bridgeStaticIp"
                         value="<?php echo htmlspecialchars($arrConfig['bridgeStaticIP'] ?? '', ENT_QUOTES); ?>"
                         placeholder="192.168.1.100" />
                  <div class="invalid-feedback">
                    <?php echo _("Please enter a valid IPv4 address"); ?>
                  </div>
                </div>
                <div class="form-text"><?php echo _("Example: 192.168.1.100"); ?></div>
              </div>
              
              <div class="col-md-6">
                <label for="bridgeNetmask" class="form-label"><?php echo _("Netmask / CIDR"); ?></label>
                <div class="input-group has-validation">
                  <input type="text" class="form-control" id="bridgeNetmask" name="bridgeNetmask"
                         value="<?php echo htmlspecialchars($arrConfig['bridgeNetmask'] ?? '24', ENT_QUOTES); ?>"
                         placeholder="24" />
                  <div class="invalid-feedback">
                    <?php echo _("Please enter a valid netmask"); ?>
                  </div>
                </div>
                <div class="form-text"><?php echo _("CIDR notation (e.g., 24 for 255.255.255.0)"); ?></div>
              </div>
              
              <div class="col-md-6">
                <label for="bridgeGateway" class="form-label"><?php echo _("Gateway"); ?></label>
                <div class="input-group has-validation">
                  <input type="text" class="form-control ip_address" id="bridgeGateway" name="bridgeGateway"
                         value="<?php echo htmlspecialchars($arrConfig['bridgeGateway'] ?? '', ENT_QUOTES); ?>"
                         placeholder="192.168.1.1" />
                  <div class="invalid-feedback">
                    <?php echo _("Please enter a valid IPv4 address"); ?>
                  </div>
                </div>
                <div class="form-text"><?php echo _("Your router's IP address"); ?></div>
              </div>

              <div class="col-md-6">
                <label for="bridgeDNS" class="form-label"><?php echo _("DNS Server"); ?></label>
                <div class="input-group has-validation">
                  <input type="text" class="form-control ip_address" id="bridgeDNS" name="bridgeDNS"
                         value="<?php echo htmlspecialchars($arrConfig['bridgeDNS'] ?? '', ENT_QUOTES); ?>"
                         placeholder="192.168.1.1" />
                  <div class="invalid-feedback">
                    <?php echo _("Please enter a valid IPv4 address"); ?>
                  </div>
                </div>
                <div class="form-text"><?php echo _("Usually same as gateway"); ?></div>
              </div>
            </div>

          </div>
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
