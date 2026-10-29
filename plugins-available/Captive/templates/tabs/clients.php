<!-- client settings tab -->
<div class="tab-pane fade" id="captiveclients">
  <div class="row">
    <div class="col-lg-12">
      <h4 class="mt-3"><?php echo _("Client settings"); ?></h4>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="txtmaxclients"><?php echo _("Maximum clients"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Maximum number of concurrent authenticated users"); ?>"></i>
          <input type="number" id="txtmaxclients" class="form-control" name="max_clients" min="1" 
                  value="<?php echo htmlspecialchars($arrConfig['max_clients'] ?? '250', ENT_QUOTES); ?>" 
                  placeholder="250" />
          <small class="form-text text-muted"><?php echo _("Does not include users on the trusted MAC list"); ?></small>
        </div>

        <div class="mb-3 col-md-6">
          <label for="txtsessiontimeout"><?php echo _("Session timeout (minutes)"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Default session length in minutes. 0 = unlimited"); ?>"></i>
          <input type="number" id="txtsessiontimeout" class="form-control" name="session_timeout" min="0" 
                  value="<?php echo htmlspecialchars($arrConfig['session_timeout'] ?? '0', ENT_QUOTES); ?>" 
                  placeholder="0" />
        </div>
      </div>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="txtpreauthortimeout"><?php echo _("Pre-auth idle timeout (minutes)"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Time before unauthenticated idle users are removed"); ?>"></i>
          <input type="number" id="txtpreauthortimeout" class="form-control" name="preauth_idle_timeout" min="1" 
                  value="<?php echo htmlspecialchars($arrConfig['preauth_idle_timeout'] ?? '10', ENT_QUOTES); ?>" 
                  placeholder="10" />
        </div>

        <div class="mb-3 col-md-6">
          <label for="txtauthidletimeout"><?php echo _("Auth idle timeout (minutes)"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Time before authenticated idle users are deauthenticated"); ?>"></i>
          <input type="number" id="txtauthidletimeout" class="form-control" name="auth_idle_timeout" min="1" 
                  value="<?php echo htmlspecialchars($arrConfig['auth_idle_timeout'] ?? '120', ENT_QUOTES); ?>" 
                  placeholder="120" />
        </div>
      </div>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="txtcheckinterval"><?php echo _("Check interval (seconds)"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("How often to check client timeouts"); ?>"></i>
          <input type="number" id="txtcheckinterval" class="form-control" name="check_interval" min="1" 
                  value="<?php echo htmlspecialchars($arrConfig['check_interval'] ?? '30', ENT_QUOTES); ?>" 
                  placeholder="30" />
        </div>
      </div>

      <h5 class="mt-3"><?php echo _("MAC address control"); ?></h5>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="cbxmacmechanism"><?php echo _("MAC mechanism"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Block: blocklisted MACs are blocked. Allow: only allowlisted MACs are allowed"); ?>"></i>
          <select class="form-select" id="cbxmacmechanism" name="mac_mechanism">
            <option value="block" <?php echo (isset($arrConfig['mac_mechanism']) && $arrConfig['mac_mechanism'] === 'block') ? 'selected' : 'selected'; ?>>
              <?php echo _("Block (blocklist mode)"); ?>
            </option>
            <option value="allow" <?php echo (isset($arrConfig['mac_mechanism']) && $arrConfig['mac_mechanism'] === 'allow') ? 'selected' : ''; ?>>
              <?php echo _("Allow (allowlist mode)"); ?>
            </option>
          </select>
        </div>
      </div>

      <div class="row" id="blockedMACGroup" style="<?php echo (isset($arrConfig['mac_mechanism']) && $arrConfig['mac_mechanism'] === 'allow') ? 'display:none;' : ''; ?>">
        <div class="mb-3 col-md-12">
          <label for="txtblockedmacs"><?php echo _("Blocked MAC list"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Comma-separated MAC addresses to block (used when mechanism is 'block')"); ?>"></i>
          <textarea class="form-control" id="txtblockedmacs" name="blocked_mac_list" rows="3"
                    placeholder="00:00:DE:AD:BE:EF,00:00:C0:1D:F0:0D"><?php echo htmlspecialchars($arrConfig['blocked_mac_list'] ?? '', ENT_QUOTES); ?></textarea>
          <small class="form-text text-muted"><?php echo _("Example: <code>00:11:22:33:44:55,AA:BB:CC:DD:EE:FF</code>"); ?></small>
        </div>
      </div>

      <div class="row" id="allowedMACGroup" style="<?php echo (isset($arrConfig['mac_mechanism']) && $arrConfig['mac_mechanism'] === 'allow') ? '' : 'display:none;'; ?>">
        <div class="mb-3 col-md-12">
          <label for="txtallowedmacs"><?php echo _("Allowed MAC list"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Comma-separated MAC addresses to allow (used when mechanism is 'allow')"); ?>"></i>
          <textarea class="form-control" id="txtallowedmacs" name="allowed_mac_list" rows="3"
                    placeholder="00:00:12:34:56:78"><?php echo htmlspecialchars($arrConfig['allowed_mac_list'] ?? '', ENT_QUOTES); ?></textarea>
          <small class="form-text text-muted"><?php echo _("Example: <code>00:11:22:33:44:55,AA:BB:CC:DD:EE:FF</code>"); ?></small>
        </div>
      </div>

      <div class="row">
        <div class="mb-3 col-md-12">
          <label for="txttrustedmacs"><?php echo _("Trusted MAC list"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Comma-separated MAC addresses that bypass authentication entirely"); ?>"></i>
          <textarea class="form-control" id="txttrustedmacs" name="trusted_mac_list" rows="3"
                    placeholder="00:00:CA:FE:BA:BE,00:00:C0:01:D0:0D"><?php echo htmlspecialchars($arrConfig['trusted_mac_list'] ?? '', ENT_QUOTES); ?></textarea>
          <small class="form-text text-muted"><?php echo _("These devices are not subject to authentication or firewall rules"); ?></small>
        </div>
      </div>

    </div><!-- /.row -->
  </div><!-- /.tab-pane | clients tab -->
</div>

