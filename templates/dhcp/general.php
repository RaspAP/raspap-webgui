<div class="tab-pane active" id="server-settings">
  <h4 class="mt-3">DHCP server settings</h4>
  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="code">Interface</label>
        <?php SelectorOptions('interface', $interfaces, $ap_iface, 'cbxdhcpiface', 'loadInterfaceDHCPSelect'); ?>
    </div>
  </div>

  <h5 class="mt-1"><?php echo _("Adapter IP Address Settings"); ?></h5>
  <div class="row">
    <div class="mb-3 col-md-6">
      <div class="btn-group" role="group" data-bs-toggle="buttons">
        <label class="btn btn-light active" checked onclick="setDHCPToggles(false)">
          <input type="radio" name="adapter-ip" id="chkdhcp" autocomplete="off"> DHCP
        </label>
        <label class="btn btn-light" onclick="setDHCPToggles(true)">
          <input type="radio" name="adapter-ip" id="chkstatic" autocomplete="off"> Static IP
        </label>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="mb-3 col-md-6">
        <div class="form-check form-switch">
          <input class="form-check-input" id="chkfallback" type="checkbox" name="Fallback" value="1" aria-describedby="fallback-description">
          <label class="form-check-label" for="chkfallback"><?php echo _("Enable fallback to static option") ?></label>
        </div>
        <p class="mb-0" id="fallback-description">
          <small><?php echo _("Enable this option to configure a static profile and fall back to it when DHCP lease fails.") ?></small>
        </p>
    </div>
  </div>

  <h5 class="mt-1">Static IP options</h5>
  <div class="row">
    <div class="mb-3 col-md-6" required>
      <label for="code"><?php echo _("IP Address"); ?></label>
      <input type="text" class="form-control ip_address" id="txtipaddress" name="StaticIP" maxlength="15" />
      <div class="invalid-feedback">
        <?php echo _("Please provide a valid IP Address."); ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="code"><?php echo _("Subnet Mask"); ?></label>
      <input type="text" class="form-control ip_address" id="txtsubnetmask" name="SubnetMask" maxlength="15" />
      <div class="invalid-feedback">
        <?php echo _("Please provide a valid Subnet mask."); ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="code"><?php echo _("Default gateway"); ?></label>
      <input type="text" class="form-control ip_address" id="txtgateway" name="DefaultGateway" maxlength="15" />
      <div class="invalid-feedback">
        <?php echo _("Please provide a valid Default gateway."); ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="mb-3 col-md-6">
        <div class="form-check form-switch">
          <input class="form-check-input" id="default-route" type="checkbox" name="DefaultRoute" value="1" aria-describedby="default-route-description">
          <label class="form-check-label" for="default-route"><?php echo _("Install a default route for this interface") ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" title="<?php echo _("Enable this only if you want your device to use this interface as its primary route to the internet."); ?>"></i>
        </div>
        <p class="mb-0" id="default-route-description">
          <small><?php echo _("This toggles the <code>gateway</code>/<code>nogateway</code> option for this interface in the dhcpcd.conf file.") ?></small>
        </p>
    </div>
  </div>

  <div class="row">
    <div class="mb-3 col-md-6">
        <div class="form-check form-switch">
          <input class="form-check-input" id="nohook-wpa-supplicant" type="checkbox" name="NoHookWPASupplicant" value="1" aria-describedby="hook-wpa-supplicant-description">
          <label class="form-check-label" for="nohook-wpa-supplicant"><?php echo _("Disable wpa_supplicant dhcp hook for this interface") ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" title="<?php echo _("If you manage wireless connections with wpa_supplicant itself, the hook may create unwanted connection events. This option disables the hook."); ?>"></i>
        </div>
        <p class="mb-0" id="hook-wpa-supplicant-description">
          <small><?php echo _("This toggles the <code>nohook wpa_supplicant</code> option for this interface in the dhcpcd.conf file.") ?></small>
        </p>
    </div>
  </div>

  <h5 class="mt-1">DHCP options</h5>
  <div class="row">
    <div class="mb-3 col-md-6">
      <div class="input-group">
        <div class="form-check form-switch">
          <input class="form-check-input" id="dhcp-iface" type="checkbox" name="dhcp-iface" value="1" aria-describedby="dhcp-iface-description">
          <label class="form-check-label" for="dhcp-iface"><?php echo _("Enable DHCP for this interface") ?></label>
        </div>
        <p class="mb-0" id="dhcp-iface-description">
          <small><?php echo _("Enable this option if you want RaspAP to assign IP addresses to clients on the selected interface. A static IP address is required for this option.") ?></small>
        </p>
      </div>
     </div>
  </div>
  
  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="code"><?php echo _("Starting IP Address"); ?></label>
      <input type="text" class="form-control ip_address" id="txtrangestart" name="RangeStart" maxlength="15" />
      <div class="invalid-feedback">
        <?php echo _("Please provide a valid Starting IP Address."); ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="code"><?php echo _("Ending IP Address"); ?></label>
      <input type="text" class="form-control ip_address" id="txtrangeend" name="RangeEnd" maxlength="15" />
      <div class="invalid-feedback">
        <?php echo _("Please provide a valid Ending IP Address."); ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="mb-3 col-xs-3 col-sm-3">
      <label for="code"><?php echo _("Lease Time"); ?></label>
      <input type="text" class="form-control" id="txtrangeleasetime" name="RangeLeaseTime" />
      <div class="invalid-feedback">
        <?php echo _("Please provide a valid Lease Time."); ?>
      </div>
    </div>
    <div class="col-xs-3 col-sm-3">
      <label for="code"><?php echo _("Interval"); ?></label>
      <select id="cbxrangeleasetimeunits" name="RangeLeaseTimeUnits" class="form-select" >
        <option value="m"><?php echo _("Minute(s)"); ?></option>
        <option value="h"><?php echo _("Hour(s)"); ?></option>
        <option value="d"><?php echo _("Day(s)"); ?></option>
        <option value="i"><?php echo _("Infinite"); ?></option>
      </select>
      <div class="invalid-feedback">
        <?php echo _("Please provide a valid Interval."); ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="code"><?php echo _("DNS Server"); ?> 1</label>
      <input type="text" class="form-control" id="txtdns1" name="DNS1" />
    </div>
  </div>

  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="code"><?php echo _("DNS Server"); ?> 2</label>
      <input type="text" class="form-control" id="txtdns2" name="DNS2" />
    </div>
  </div>

  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="<metric"><?php echo _("Metric") ?></label>
      <input type="text" class="form-control" id="txtmetric" name="Metric">
    </div>
  </div>

</div><!-- /.tab-pane -->
