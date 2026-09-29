<!-- basic settings tab -->
<div class="tab-pane fade show active" id="captivebasic">
  <div class="row">
    <div class="col-lg-12">
      <h4 class="mt-3"><?php echo _("Basic settings"); ?></h4>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="cbxgatewayinterface"><?php echo _("Gateway interface") ;?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" title="<?php echo _("Interface to be managed by the portal"); ?>"></i>
          <?php SelectorOptions('gateway_interface', $interfaces, $arrConfig['gateway_interface'] ?? $_SESSION['ap_interface'], 'cbxgatewayinterface'); ?>
          <small class="form-text text-muted"><?php echo _("Defaults to the active AP interface, typically <code>wlan0</code>"); ?></small>
        </div>

        <div class="mb-3 col-md-6">
          <label for="txtgatewayname"><?php echo _("Gateway name"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Name of your gateway (available as \$gatewayname variable)"); ?>"></i>
          <input type="text" id="txtgatewayname" class="form-control" name="gateway_name" 
                  value="<?php echo htmlspecialchars($arrConfig['gateway_name'] ?? 'My Portal', ENT_QUOTES); ?>" 
                  placeholder="NoDogSplash" />
        </div>
      </div>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="txtgatewayaddress"><?php echo _("Gateway address"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("IP address of the router. Leave empty for auto-detection"); ?>"></i>
          <input type="text" id="txtgatewayaddress" class="form-control" name="gateway_address" 
                  value="<?php echo htmlspecialchars($arrConfig['gateway_address'] ?? '', ENT_QUOTES); ?>" 
                  placeholder="Auto-detected" />
          <small class="form-text text-muted"><?php echo _("Auto-detected from gateway interface if not specified"); ?></small>
        </div>

        <div class="mb-3 col-md-6">
          <label for="txtgatewayport"><?php echo _("Gateway port"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Port for Nodogsplash HTTP server"); ?>"></i>
          <input type="number" id="txtgatewayport" class="form-control" name="gateway_port" min="1" max="65535"
                  value="<?php echo htmlspecialchars($arrConfig['gateway_port'] ?? '2050', ENT_QUOTES); ?>" 
                  placeholder="2050" />
        </div>
      </div>                        

    </div><!-- /.row -->
  </div><!-- /.tab-pane | basic tab -->
</div>

