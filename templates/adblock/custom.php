<!-- logging tab -->
<div class="tab-pane fade" id="adblockcustom">
  <h4 class="mt-3"><?php echo _("Custom blocklist"); ?></h4>
    <div class="row">
      <div class="col-md-6">
        <div class="input-group">
          <input type="hidden" name="adblock-custom-enable" value="0">
          <div class="custom-control custom-switch">
            <input class="custom-control-input" id="adblock-custom-enable" type="checkbox" name="adblock-custom-enable" value="1" <?php echo $custom_enabled ? ' checked="checked"' : "" ?> aria-describedby="adblock-description">
          <label class="custom-control-label" for="adblock-custom-enable"><?php echo _("Enable custom blocklist") ?></label>
          </div>
        </div>
        <p id="adblock-description">
          <small><?php echo _("Define custom hosts to be blocked by entering an IPv4 or IPv6 address followed by any whitespace (spaces or tabs) and the host name.") ?></small>
          <small><?php echo _("<b>IPv4 example:</b> 0.0.0.0 badhost.com") ?></small>
          <div>
            <small class="text-muted"><?php echo _("This option adds an <code>addn-hosts</code> directive to the dnsmasq configuration.") ?></small>
          </div>
        </p>
      </div>
    </div>
    <div class="row">
      <div class="form-group col-md-8">
      <?php
      $adblock_custom = file_get_contents(RASPI_ADBLOCK_LISTPATH .'custom.txt');
      if (strlen($adblock_custom) == 0) {
        $adblock_custom = _("Custom blocklist not defined");
      }
      echo '<textarea class="logoutput" name="adblock-custom-hosts">'.htmlspecialchars($adblock_custom, ENT_QUOTES).'</textarea>';
      ?>
      </div>
  </div>
</div><!-- /.tab-pane -->
