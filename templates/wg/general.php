<!-- wireguard settings tab -->
<div class="tab-pane active" id="wgsettings">
  <div class="row">
    <div class="col-md-6">
      <h4 class="mt-3"><?php echo _("Tunnel settings"); ?></h4>
        <div class="input-group">
          <input type="hidden" name="tunnel-enable" value="0">
          <div class="custom-control custom-switch">
            <input class="custom-control-input" id="tunnel-enable" type="checkbox" name="tunnel-enable" value="1" <?php echo $enabled ? ' checked="checked"' : "" ?> aria-describedby="tunnel-description">
          <label class="custom-control-label" for="tunnel-enable"><?php echo _("Enable tunnel") ?></label>
        </div>
        <p id="wg-description">
          <small><?php echo _("Enable this option to encrypt traffic by creating a tunnel between RaspAP and configured peers.") ?></small>
        </p>
        </div>

        <div class="row">
          <div class="col-xs-3 col-sm-6">
            <label for="code"><?php echo _("Local public key"); ?></label>
          </div>
          <div class="input-group col-md-12 mb-3">
            <input type="text" class="form-control" name="wg-server" id="wg-srvpubkey" value="<?php echo htmlspecialchars($wg_srvpubkey, ENT_QUOTES); ?>" />
            <div class="input-group-append">
              <button class="btn btn-outline-secondary rounded-right wg-keygen" type="button"><i class="fas fa-magic"></i></button>
              <span id="wg-server-pubkey-status" class="input-group-addon check-hidden ml-2 mt-1"><i class="fas fa-check"></i></span>
            </div>
            <input type="hidden" name="wg_srvprivkey" value="" />
          </div>
        </div>

        <div class="row">
          <div class="form-group col-xs-3 col-sm-3">
            <label for="code"><?php echo _("Local Port"); ?></label>
            <input type="text" class="form-control" name="wg_srvport" value="<?php echo htmlspecialchars($wg_srvport, ENT_QUOTES); ?>" />
          </div>
        </div>

        <div class="row">
          <div class="form-group col-md-6">
            <label for="code"><?php echo _("IP Address"); ?></label>
            <input type="text" class="form-control" name="wg_srvipaddress" value="<?php echo htmlspecialchars($wg_srvipaddress, ENT_QUOTES); ?>" />
          </div>
        </div>

    </div>
  </div><!-- /.row -->
</div><!-- /.tab-pane | settings tab -->

