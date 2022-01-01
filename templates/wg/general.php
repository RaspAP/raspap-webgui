<!-- wireguard settings tab -->
<div class="tab-pane active" id="wgsettings">
  <div class="row">
    <div class="col-lg-8">
      <h4 class="mt-3"><?php echo _("Tunnel settings"); ?></h4>
        <div class="col-lg-12 mt-2">
          <div class="row mt-3 mb-2">
            <div class="info-item col-xs-3"><?php echo _("IPv4 Address"); ?></div>
            <div class="info-value col-xs-3"><?php echo htmlspecialchars($public_ip, ENT_QUOTES); ?><a class="text-gray-500" href="https://ipapi.co/<?php echo($public_ip); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt ml-2"></i></a></div>
          </div>
        </div>
        <h5><?php echo _("Configuration Method"); ?></h5>
        <div class="col-sm-12 mt-2 mb-2 form-check">
          <input class="form-check-input" id="wg-upload" name="wgCnfOpt" value="upload" data-toggle="" data-parent="#serversettings" data-target="#wgUpload" type="radio" checked>
          <label class="form-check-label"><?php echo _("Upload file"); ?></label>
        </div>
        <div class="col-sm-12 mt-2 mb-2 form-check">
          <input class="form-check-input" id="wg-manual" name="wgCnfOpt" value="manual" data-toggle="" data-parent="#serversettings" data-target="#wgManual" type="radio">
          <label class="form-check-label"><?php echo _("Create manually"); ?></label>
        </div>

        <div class="col-sm-12 ml-2">
          <div class="panel-group" id="serversettings">

            <div class="panel panel-default panel-collapse" id="PanelUpload">
              <div class="panel-heading">
                <h5 class="panel-title"><?php echo _("Upload a WireGuard config"); ?></h5>
                <p id="wg-description">
                  <small><?php echo _("This option uploads and installs an existing WireGuard <code>.conf</code> file on this device.") ?></small>
                </p>
              </div>
              <div class="panel-body">

                <div class="form-group">
                  <div class="custom-control custom-switch">
                    <?php $checked = $optRules == 1 ? 'checked="checked"' : '' ?>
                    <input class="custom-control-input" id="chxwgrules" name="wgRules" type="checkbox" value="1" <?php echo $checked ?> />
                    <label class="custom-control-label" for="chxwgrules"><?php echo _("Apply iptables rules for AP interface"); ?></label>
                    <i class="fas fa-question-circle text-muted" data-toggle="tooltip" data-placement="auto" title="<?php echo _("Recommended if you wish to forward network traffic from the wg0 interface to clients connected on the AP interface."); ?>"></i>
                    <p id="wg-description">
                      <small><?php printf(_("This option adds <strong>iptables</strong> <code>Postup</code> and <code>PostDown</code> rules for the configured AP interface (%s)."), $_SESSION['ap_interface']) ?></small>
                    </p>
                  </div>
                </div>

                <div class="form-group">
                  <h5 class="panel-title"><?php echo _("Configuration File"); ?></h4>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" name="wgFile" id="wgFile">
                    <label class="custom-file-label" for="wgFile"><?php echo _("Select WireGuard configuration file (.conf)"); ?></label>
                  </div>
                </div>
                <div class="row mb-2"></div>

              </div><!-- /.panel-body -->
            </div><!-- /.panel -->

            <div class="panel panel-default panel-collapse" id="PanelManual">
              <div class="panel-heading">
                <h5 class="panel-title"><?php echo _("Create a local WireGuard config"); ?></h5>
                <div class="input-group">
                  <div class="custom-control custom-switch">
                    <input class="custom-control-input" id="server_enabled" type="checkbox" name="wgSrvEnable" value="1" <?php echo $wg_senabled ? ' checked="checked"' : "" ?> aria-describedby="server-description">
                    <label class="custom-control-label" for="server_enabled"><?php echo _("Enable server") ?></label>
                  </div>
                  <p id="wg-description">
                    <small>
                      <?php echo _("Enable this option to secure network traffic by creating an encrypted tunnel between RaspAP and configured peers.") ?>
                      <?php echo _("This setting generates a new WireGuard <code>.conf</code> file on this device.") ?>
                    </small>
                  </p>
                </div>
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
              <div class="row mb-3"></div>

            </div><!-- /.panel-body -->
          </div><!-- /.panel -->

        </div><!-- /.panel-group -->
      </div><!-- /.col -->
    </div><!-- /.row -->

</div><!-- /.tab-pane | settings tab -->

