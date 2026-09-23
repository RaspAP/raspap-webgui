    <h5><?php echo _("Configure device"); ?></h5>
    <div class="mt-3">
      <?php echo sprintf(
        _("The device <code>%s</code> is connected to your tailnet with the address <code>%s</code>."), htmlspecialchars($this->hostname), trim(htmlspecialchars($address))
      ); ?>
    </div>
    <div class="mt-3">
      <?php echo _("By default, Tailscale only routes traffic between the devices on which it's been installed. You can also route all your public internet traffic by configuring a device on your network as an <strong>exit node</strong>"); ?>
        <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto"
            title="<?php echo _("When you route all traffic through an exit node, you're effectively using default routes (0.0.0.0/0, ::/0), similar to how you would if you were using a typical VPN."); ?>">
        </i> 
    </div>
    <div class="mt-3">
      <?php echo _("You have the option of configuring this device as an exit node, or using another exit node in your tailnet."); ?>
    </div>

    <div class="mt-3">
        <div class="form-check">
          <input class="form-check-input" type="radio" name="configOpt" id="existingNode" value="existing" checked>
          <label class="form-check-label" for="existingNode">
            <?php echo _("Select an existing exit node on your tailnet"); ?>
              <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto"
                title="<?php echo _("This is a typical configuration if you're using this device as a VPN travel router, for example."); ?>">
              </i> 
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="configOpt" id="createNode" value="create">
          <label class="form-check-label" for="createNode">
            <?php echo _("Configure this device as a new exit node"); ?>
              <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto"
                title="<?php echo _("By configuring this device as an exit node, public internet traffic from devices connected in your tailnet  will be routed through it."); ?>">
              </i> 
          </label>
        </div>
    </div>

    <div class="mt-3">
      <?php echo _("Choose <strong>Next</strong> to continue."); ?>
    </div>

