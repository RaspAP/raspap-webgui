<!-- advanced settings tab -->
<div class="tab-pane fade" id="captiveadvanced">
  <div class="row">
    <div class="col-lg-12">
      <h4 class="mt-3"><?php echo _("Advanced settings"); ?></h4>

        <div class="row">
          <div class="mb-3 col-md-6">
            <label for="txtgatewayiprange"><?php echo _("Gateway IP range"); ?></label>
            <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
               title="<?php echo _("IP range to manage in CIDR notation. Leave empty for all addresses"); ?>"></i>
            <input type="text" id="txtgatewayiprange" class="form-control" name="gateway_ip_range" 
                   value="<?php echo htmlspecialchars($arrConfig['gateway_ip_range'] ?? '', ENT_QUOTES); ?>" 
                   placeholder="0.0.0.0/0" />
            <small class="form-text text-muted"><?php echo _("Default: 0.0.0.0/0 (all addresses)"); ?></small>
          </div>
        </div>
    
        <div class="row">
          <div class="mb-3 col-md-6">
            <label for="cbxdebuglevel"><?php echo _("Debug level"); ?></label>
            <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
               title="<?php echo _("Amount of logging detail reported by the nodogsplash.service"); ?>"></i>
            <select class="form-select" id="cbxdebuglevel" name="debug_level">
              <option value="0" <?php echo (isset($arrConfig['debug_level']) && $arrConfig['debug_level'] == '0') ? 'selected' : ''; ?>>
                <?php echo _("0 - Errors only"); ?>
              </option>
              <option value="1" <?php echo (!isset($arrConfig['debug_level']) || $arrConfig['debug_level'] == '1') ? 'selected' : ''; ?>>
                <?php echo _("1 - Errors, warnings, infos"); ?>
              </option>
              <option value="2" <?php echo (isset($arrConfig['debug_level']) && $arrConfig['debug_level'] == '2') ? 'selected' : ''; ?>>
                <?php echo _("2 - Errors, warnings, infos, verbose"); ?>
              </option>
              <option value="3" <?php echo (isset($arrConfig['debug_level']) && $arrConfig['debug_level'] == '3') ? 'selected' : ''; ?>>
                <?php echo _("3 - Errors, warnings, infos, verbose, debug"); ?>
              </option>
            </select>
          </div>
        </div>

        <h5 class="mt-3"><?php echo _("Firewall settings"); ?></h5>

        <div class="row">
          <div class="col-md-6">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="chkallowallauthenticated" name="allow_all_authenticated" value="1" 
                     <?php echo (!isset($arrConfig['allow_all_authenticated']) || $arrConfig['allow_all_authenticated']) ? 'checked' : ''; ?> />
              <label class="form-check-label" for="chkallowallauthenticated">
                <?php echo _("Allow all traffic for authenticated users"); ?>
              </label>
              <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
                 title="<?php echo _("When enabled, authenticated users have unrestricted access"); ?>"></i>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="mb-3 col-md-6">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="chkallowdns" name="allow_preauth_dns" value="1" 
                     <?php echo (!isset($arrConfig['allow_preauth_dns']) || $arrConfig['allow_preauth_dns']) ? 'checked' : ''; ?> />
              <label class="form-check-label" for="chkallowdns">
                <?php echo _("Allow DNS for pre-authenticated users"); ?>
              </label>
              <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
                 title="<?php echo _("Required for clients to resolve domain names before authentication"); ?>"></i>
            </div>
          </div>
        </div>

    </div><!-- /.row -->
  </div><!-- /.tab-pane | basic tab -->
</div>

