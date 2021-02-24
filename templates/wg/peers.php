<!-- wireguard peers tab -->
<div class="tab-pane fade" id="wgpeers">

  <div class="row">
    <div class="col-md-6">
      <h4 class="mt-3"><?php echo _("Peer"); ?></h4>
        <div class="input-group">
          <input type="hidden" name="endpoint-enable" value="0">
          <input type="hidden" name="peer_id" value="1">
          <div class="custom-control custom-switch">
            <input class="custom-control-input" id="endpoint_enable" type="checkbox" name="endpoint-enable" value="1" <?php echo $enabled ? ' checked="checked"' : "" ?> aria-describedby="endpoint-description">
          <label class="custom-control-label" for="endpoint_enable"><?php echo _("Enable endpoint") ?></label>
        </div>
     </div>

    <div class="row">
      <div class="form-group col-xs-3 col-sm-6 mt-3">
        <label for="code"><?php echo _("Endpoint address"); ?></label>
        <input type="text" class="form-control" name="wg_pendpoint" value="<?php echo htmlspecialchars($wg_pendpoint, ENT_QUOTES); ?>" />
      </div>
    </div>

    <div class="row">
      <div class="col-xs-3 col-sm-6">
        <label for="code"><?php echo _("Allowed IPs"); ?></label>
        <input type="text" class="form-control mb-3" name="wg_pallowedips" placeholder="0.0.0.0/0" value="<?php echo htmlspecialchars($wg_pallowedips, ENT_QUOTES); ?>" />
      </div>
    </div>

    <div class="row">
      <div class="col-xs-3 col-sm-6">
        <label for="code"><?php echo _("Persistent keepalive"); ?></label>
        <input type="text" class="form-control col-sm-3 mb-3" name="wg_pkeepalive" placeholder="25" value="<?php echo htmlspecialchars($wg_pkeepalive, ENT_QUOTES); ?>" />
      </div>
    </div>

    <div class="row">
      <div class="col-xs-3 col-sm-6">
          <label for="code"><?php echo _("Peer public key"); ?></label>
      </div>
      <div class="input-group col-md-12 mb-3">
        <input type="text" class="form-control" name="wg-peer" id="wg-peerpubkey" value="<?php echo htmlspecialchars($wg_peerpubkey, ENT_QUOTES); ?>" />
        <div class="input-group-append">
          <button class="btn btn-sm btn-outline-secondary rounded-right" type="button" onclick="generateWgKey()"><?php echo _("Generate key"); ?></button>
          <span id="cbxblocklist-status" class="input-group-addon check-hidden ml-2 mt-1"><i class="fas fa-check"></i></span>
        </div>
      </div>
    </div>

    </div>
  </div><!-- /.row -->
</div><!-- /.tab-pane | peers tab -->

