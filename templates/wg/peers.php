<!-- wireguard peers tab -->
<div class="tab-pane fade" id="wgpeers">
  <div class="row">
    <div class="col-md-6">
      <h4 class="mt-3"><?php echo _("Peer"); ?></h4>
        <div class="input-group">
          <input type="hidden" name="peer_id" value="1">
          <div class="form-check form-switch">
            <input class="form-check-input" id="peer_enabled" type="checkbox" name="wg_penabled" value="1" <?php echo $wg_penabled ? ' checked="checked"' : "" ?> aria-describedby="endpoint-description">
          <label class="form-check-label" for="peer_enabled"><?php echo _("Enable peer") ?></label>
        </div>
        <p id="wg-description">
          <small><?php echo _("Enable this option to encrypt traffic by creating a tunnel between RaspAP and this peer.") ?></small>
          <small><?php echo _("This option adds <code>client.conf</code> to the WireGuard configuration.") ?></small>
        </p>
     </div>

      <div class="row">
        <div class="col-xs-3 col-sm-6 mt-3">
          <label for="code"><?php echo _("Peer public key"); ?></label>
        </div>
        <div class="input-group col-md-12">
          <input type="text" class="form-control" name="wg-peer" id="wg-peerpubkey" value="<?php echo htmlspecialchars($wg_peerpubkey, ENT_QUOTES); ?>" />
          <div class="btn btn-outline-secondary rounded-end wg-keygen"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
          <span id="wg-peer-pubkey-status" class="input-group-addon check-hidden ms-2 mt-1"><i class="fas fa-check"></i></span>
        </div>
      </div>

      <div class="row">
        <div class="mb-3 col-xs-3 col-sm-3 mt-3">
          <label for="code"><?php echo _("Local Port"); ?></label>
          <input type="text" class="form-control" name="wg_plistenport" value="<?php echo htmlspecialchars($wg_plistenport, ENT_QUOTES); ?>" />
        </div>
      </div>

      <div class="row">
        <div class="mb-3 col-xs-3 col-sm-6">
          <label for="code"><?php echo _("IP Address"); ?></label>
          <input type="text" class="form-control" name="wg_pipaddress" value="<?php echo htmlspecialchars($wg_pipaddress, ENT_QUOTES); ?>" />
        </div>
      </div>

      <div class="row">
        <div class="mb-3 col-xs-3 col-sm-6">
          <label for="code"><?php echo _("Endpoint address"); ?></label>
          <input type="text" class="form-control" name="wg_pendpoint" value="<?php echo htmlspecialchars($wg_pendpoint, ENT_QUOTES); ?>" />
        </div>
      </div>

      <div class="row">
        <div class="col-xs-3 col-sm-6">
          <label for="code"><?php echo _("Allowed IPs"); ?></label>
          <input type="text" class="form-control mb-3" name="wg_pallowedips" value="<?php echo htmlspecialchars($wg_pallowedips, ENT_QUOTES); ?>" />
        </div>
      </div>

      <div class="row">
        <div class="col-xs-3 col-sm-6">
          <label for="code"><?php echo _("Persistent keepalive"); ?></label>
          <input type="text" class="form-control col-sm-3 mb-3" name="wg_pkeepalive" value="<?php echo htmlspecialchars($wg_pkeepalive, ENT_QUOTES); ?>" />
        </div>
      </div>
    </div>

    <div class="col-md-6 mt-5">
      <figure class="figure w-75 ms-3">
        <?php if ($wg_penabled == true ) : ?>
        <img src="app/img/wg-qr-code.php" class="figure-img img-fluid" alt="RaspAP Wifi QR code" style="width:100%;">
        <figcaption class="figure-caption">
          <?php echo _("Scan this QR code with your client to connect to this tunnel"); ?>
          <?php echo _("or download the <code>client.conf</code> file to your device."); ?>
        </figcaption>
        <button class="btn btn-outline-secondary rounded-end wg-client-dl mt-2" type="button"><?php echo _("Download"); ?> <i class="fas fa-download ms-1"></i></button> 
        <?php endif; ?>
      </figure>
    </div>

  </div><!-- /.row -->
</div><!-- /.tab-pane | peers tab -->

