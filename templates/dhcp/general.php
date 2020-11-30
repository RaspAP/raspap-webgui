<div class="tab-pane active" id="server-settings">
  <h4 class="mt-3">DHCP server settings</h4>
  <div class="row">
    <div class="form-group col-md-6">
      <label for="code">Interface</label>
        <?php SelectorOptions('interface', $interfaces, $ap_iface, 'cbxdhcpiface', 'loadInterfaceDHCPSelect', $DHCPDisabled); ?>
    </div>
  </div>

  <h5 class="mt-1"><?php echo _("Adapter IP Address Settings"); ?></h5>
  <div class="row">
    <div class="form-group col-md-6">
      <div class="btn-group" data-toggle="buttons">
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
    <div class="form-group col-md-6">
        <div class="custom-control custom-switch">
          <input class="custom-control-input" id="chkfallback" type="checkbox" name="Fallback" value="1" aria-describedby="fallback-description">
          <label class="custom-control-label" for="chkfallback"><?php echo _("Enable fallback to static option") ?></label>
        </div>
        <p class="mb-0" id="fallback-description">
          <small><?php echo _("Enable this option to configure a static profile and fall back to it when DHCP lease fails.") ?></small>
        </p>
    </div>
  </div>

  <h5 class="mt-1">Static IP options</h5>
  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("IP Address"); ?></label>
      <input type="text" class="form-control" id="txtipaddress" name="StaticIP" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("Subnet Mask"); ?></label>
      <input type="text" class="form-control" id="txtsubnetmask" name="SubnetMask" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("Default gateway"); ?></label>
      <input type="text" class="form-control" id="txtgateway" name="DefaultGateway" />
    </div>
  </div>

  <h5 class="mt-1">DHCP options</h5>
  <div class="row">
    <div class="form-group col-md-6">
      <div class="input-group">
        <div class="custom-control custom-switch">
          <input class="custom-control-input" id="dhcp-iface" type="checkbox" name="dhcp-iface" value="1" aria-describedby="dhcp-iface-description">
          <label class="custom-control-label" for="dhcp-iface"><?php echo _("Enable DHCP for this interface") ?></label>
        </div>
        <p class="mb-0" id="dhcp-iface-description">
          <small><?php echo _("Enable this option if you want RaspAP to assign IP addresses to clients on the selected interface. A static IP address is required for this option.") ?></small>
        </p>
      </div>
     </div>
  </div>
  
  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("Starting IP Address"); ?></label>
      <input type="text" class="form-control" id="txtrangestart" name="RangeStart" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("Ending IP Address"); ?></label>
      <input type="text" class="form-control" id="txtrangeend" name="RangeEnd" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-xs-3 col-sm-3">
      <label for="code"><?php echo _("Lease Time"); ?></label>
      <input type="text" class="form-control" id="txtrangeleasetime" name="RangeLeaseTime" />
    </div>
    <div class="col-xs-3 col-sm-3">
      <label for="code"><?php echo _("Interval"); ?></label>
      <select id="cbxrangeleasetimeunits" name="RangeLeaseTimeUnits" class="form-control" >
        <option value="m"><?php echo _("Minute(s)"); ?></option>
        <option value="h"><?php echo _("Hour(s)"); ?></option>
        <option value="d"><?php echo _("Day(s)"); ?></option>
        <option value="infinite"><?php echo _("Infinite"); ?></option>
      </select>
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("DNS Server"); ?> 1</label>
      <input type="text" class="form-control" id="txtdns1" name="DNS1" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("DNS Server"); ?> 2</label>
      <input type="text" class="form-control" id="txtdns2" name="DNS2" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="<metric"><?php echo _("Metric") ?></label>
      <input type="text" class="form-control" id="txtmetric" name="Metric">
    </div>
  </div>

</div><!-- /.tab-pane -->
