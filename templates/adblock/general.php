<!-- blocklist settings tab -->
<div class="tab-pane active" id="adblocklistsettings">
  <div class="row">
    <div class="col-md-6">
      <h4 class="mt-3"><?php echo _("Blocklist settings"); ?></h4>
        <div class="input-group">
          <input type="hidden" name="adblock-enable" value="0">
          <div class="form-check form-switch">
            <input class="form-check-input" id="adblock-enable" type="checkbox" name="adblock-enable" value="1" <?php echo $enabled ? ' checked="checked"' : "" ?> aria-describedby="adblock-description">
          <label class="form-check-label" for="adblock-enable"><?php echo _("Enable blocklists") ?></label>
        </div>
        <p id="adblock-description">
          <small><?php echo _("Enable this option if you want RaspAP to <b>block DNS requests for ads, tracking and other virtual garbage</b>. Blocklists are gathered from multiple, actively maintained sources and automatically updated, cleaned, optimized and moderated on a daily basis.") ?></small>
          <div>
            <small class="text-muted"><?php echo _("This option adds <code>conf-file</code> and <code>addn-hosts</code> to the dnsmasq configuration.") ?></small>
          </div>
        </p>
        </div>
        <div class="row">
          <div class="col-md-12">
            <p id="blocklist-updated">
              <div><small><?php echo _("Hostnames blocklist last updated") ?>: <span class="fw-bold" id="blocklist-hostnames">
                <?php echo blocklistUpdated('hostnames.txt') ?></span></small></div>
              <div><small><?php echo _("Domains blocklist last updated") ?>: <span class="fw-bold" id="blocklist-domains">
                <?php echo blocklistUpdated('domains.txt') ?></b></small></div>
            </p>
            <div class="input-group col-md-12 mb-4">
              <select class="form-select form-select-sm" id="cbxblocklist" onchange="clearBlocklistStatus()">
                <option value=""><?php echo _("Choose a blocklist provider") ?></option>
                <option disabled="disabled"></option>
                <?php echo optionsForSelect(blocklistProviders()) ?>
              </select>
              <button class="btn btn-sm btn-outline-secondary rounded-end" type="button" onclick="updateBlocklist()"><?php echo _("Update now"); ?></button>
              <span id="cbxblocklist-status" class="input-group-addon check-hidden ms-2 mt-1"><i class="fas fa-check"></i></span>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /.row -->
  </div><!-- /.tab-pane | advanded tab -->

