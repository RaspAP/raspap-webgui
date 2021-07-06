<!-- wireguard settings tab -->
<div class="tab-pane active" id="wgsettings">
  <div class="row">
    <div class="col-md-6">
      <h4 class="mt-3"><?php echo _("Tunnel settings"); ?></h4>
        <div class="input-group">
          <div class="custom-control custom-switch">
            <input class="custom-control-input" id="server_enabled" type="checkbox" name="wg_senabled" value="1" <?php echo $wg_senabled ? ' checked="checked"' : "" ?> aria-describedby="server-description">
          <label class="custom-control-label" for="server_enabled"><?php echo _("Enable server") ?></label>
        </div>
        <p id="wg-description">
          <small><?php echo _("Enable this option to secure network traffic by creating an encrypted tunnel between RaspAP and configured peers.") ?></small>
        </p>
        </div>
        <h5><?php echo _("Configuration Method"); ?></h5>
        <div class="col-sm-12 mt-2 mb-2 form-check">
          <input class="form-check-input" id="wg-manual" name="sel1" value="manual" data-toggle="" data-parent="#serversettings" data-target="#wgManual" type="radio" checked>
          <label class="form-check-label"><?php echo _("Manual settings"); ?></label>
        </div>
        <div class="col-sm-12 mt-2 mb-2 form-check">
          <input class="form-check-input" id="wg-upload" name="sel1" value="upload" data-toggle="" data-parent="#serversettings" data-target="#wgUpload" type="radio">
          <label class="form-check-label"><?php echo _("Upload <code>wg0.conf</code> file"); ?></label>
        </div>

        <div class="col-sm-12 ml-2">
          <div class="panel-group" id="serversettings">
            <div class="panel panel-default panel-collapse" id="PanelManual">
              <div class="panel-heading">
                <h5 class="panel-title"><?php echo _("Create a local WireGuard config"); ?></h5>
                <p id="wg-description">
                  <small><?php echo _("This option generates a new <code>wg0.conf</code> WireGuard configuration on this device.") ?></small>
                </p>
              </div>
              <div class="panel-body">
                <label for="code"><?php echo _("Local public key"); ?></label>
                <div class="input-group col-md-12 mb-3">
                  <input type="text" class="form-control" name="wg-server" id="wg-srvpubkey" value="<?php echo htmlspecialchars($wg_srvpubkey, ENT_QUOTES); ?>" />
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary rounded-right wg-keygen" type="button"><i class="fas fa-magic"></i></button>
                    <span id="wg-server-pubkey-status" class="input-group-addon check-hidden ml-2 mt-1"><i class="fas fa-check"></i></span>
                  </div>
                </div>
              </div>

              <div class="form-group col-xs-3 col-sm-3">
                <label for="code"><?php echo _("Local Port"); ?></label>
                <input type="text" class="form-control" name="wg_srvport" value="<?php echo htmlspecialchars($wg_srvport, ENT_QUOTES); ?>" />
              </div>

              <div class="form-group col-md-6">
                <label for="code"><?php echo _("IP Address"); ?></label>
                <input type="text" class="form-control" name="wg_srvipaddress" value="<?php echo htmlspecialchars($wg_srvipaddress, ENT_QUOTES); ?>" />
              </div>

              <div class="form-group col-md-6">
                <label for="code"><?php echo _("DNS"); ?></label>
                <input type="text" class="form-control" name="wg_srvdns" value="<?php echo htmlspecialchars($wg_srvdns, ENT_QUOTES); ?>" />
              </div>
            </div><!-- /.panel-body -->
          </div><!-- /.panel -->

          <div class="panel panel-default panel-collapse" id="PanelUpload">
            <div class="panel-heading">
              <h5 class="panel-title"><?php echo _("Upload a WireGuard config"); ?></h5>
              <p id="wg-description">
                <small><?php echo _("This option uploads an existing WireGuard <code>.conf</code> file to this device.") ?></small>
              </p>
            </div>
            <div class="panel-body">
              <div class="form-group">
                <h5 class="panel-title"><?php echo _("Configuration File"); ?></h4>
                <div class="custom-file">
                 <input type="file" class="custom-file-input" name="wgFile" id="wgFile">
                 <label class="custom-file-label" for="customFile"><?php echo _("Select WireGuard configuration file (.conf)"); ?></label>
               </div>
             </div>
           </div><!-- /.panel-body -->
          </div><!-- /.panel -->
        </div><!-- /.panel-group -->
      </div><!-- /.col -->
    </div>

</div><!-- /.tab-pane | settings tab -->


