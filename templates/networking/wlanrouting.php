<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" class="btn btn-outline-primary" name="SaveWlanSettings" <?php echo $state; ?> value="<?php echo _("Save settings"); ?>">
    <?php if ($routingEnabled) : ?>
      <input type="submit" class="btn btn-warning" name="RestartWlanRouting" value="<?php echo _("Restart WLAN routing"); ?>">
      <input type="submit" class="btn btn-warning" name="StopWlanRouting" value="<?php echo _("Stop WLAN routing"); ?>">
    <?php else : ?>
    <input type="submit" class="btn btn-success" name="StartWlanRouting" <?php echo $state; ?> value="<?php echo _("Start WLAN routing"); ?>">
    <?php endif ?>
  <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<!-- wlan routing settings tab -->
<div class="tab-pane" id="wlanrouting">
  <div class="row">
    <div class="col-md-6">
      <h4 class="mt-3"><?php echo _("Wireless LAN routing"); ?></h4>
      <div class="row">
        <div class="col-xs">
          <button class="btn btn-light btn-icon-split btn-sm service-status float-start ms-3">
            <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
            <span class="text service-status"><?php echo _("routing")?> <?php echo _($serviceLabel) ?></span>
          </button>
        </div>
      </div>
      <p class="mt-2" id="wlan-routing-description">
        <small><?php echo _("This option configures RaspAP to <b>route network traffic from your wireless client (STA) interface</b> to another available interface.") ?></small>
        <div>
          <small class="text-muted"><?php echo _("When an output interface is selected, <code>iptables</code> rules are added to route packets using network address translation (NAT). This is often done to share internet connectivity from a WLAN with devices on an <code>eth0</code>, <code>usb0</code> or predictable <code>enx</code> interface.") ?></small>
        </div>
      </p>
      <div class="row">
        <div class="mb-3 col-md-12">
          <label for="wifi"><?php echo _("Wireless client interface") ;?></label>
          <select class="form-select" id="staInterface" name="staInterface" <?php echo $state; ?>>
            <option selected value="<?php echo $staInterface ?>"><?php echo $staInterface ?></option>
          </select>
        </div>
      </div><!-- /.row -->
      <div class="row">
        <div class="mb-3 col-md-12">
          <label for="cbxOutputInterface"><?php echo _("Output interface") ;?></label>
          <?php SelectorOptions('outputInterface', $interfaces, $outputInterface, 'cbxOutputInterface'); ?>
        </div>
        <div class="form-check form-switch ms-3 mb-4">
          <input class="form-check-input" id="chkconfigiface" name="optConfigOutput" type="checkbox" value="1" <?php echo $configAdded ? ' checked="checked"' : "" ?>>
          <label class="form-check-label" for="chkconfigiface"><?php echo _("Configure a static IP address and DHCP for output interface"); ?></label>
        </div>
        <div class="col-md-12">
          <div class="d-flex flex-wrap gap-2">
            <?php echo $buttons ?>
          </div>
          </form>
        </div>
      </div><!-- /.row -->
    </div><!-- /.col -->
  </div><!-- /.row -->
</div><!-- /.tab-pane | wlanrouting tab -->

