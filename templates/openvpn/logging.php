<!-- logging tab -->
<div class="tab-pane fade" id="openvpnlogging">
  <h4 class="mt-3 mb-3"><?php echo _("Logging") ?></h4>
  <p><?php echo _("Enable this option to log <code>openvpn</code> activity.") ?></p>

  <div class="custom-control custom-switch">
    <input class="custom-control-input" id="log-openvpn" type="checkbox" name="log-openvpn" value="1" <?php echo $logEnable ? ' checked="checked"' : "" ?> aria-describedby="log-openvpn">
    <label class="custom-control-label align-middle" for="log-openvpn"><?php echo _("Enable logging") ?></label>
    <input type="button" class="btn btn-outline btn-warning btn-sm align-top ml-2" id="js-clearopenvpn-log" value="<?php echo _("Clear log"); ?>" />
  </div>
  <div class="row">
    <div class="form-group col-md-8 mt-2">
      <textarea class="logoutput" id="openvpn-log"><?php echo htmlspecialchars($logOutput, ENT_QUOTES); ?></textarea>
    </div>
  </div>
</div><!-- /.tab-pane -->

