    <h5><?php echo _("Select an exit node"); ?></h5>
    <div class="my-3">
      <div class="row">
        <?php if (!empty($nodeOptions)) : ?> 
        <div class="col-md-8" required>
          <label for="cbxnodes" class="mb-3"><?php echo sprintf(_("To use <code>%s</code> as a VPN gateway, configure Tailscale to use an exit node. Tailscale's suggested node is indicated with a star."), htmlspecialchars($this->hostname)); ?></label>
          <?php SelectorOptions('exit_node', $nodeOptions, $suggested, 'cbxnodes', null, false); ?>

          <div class="form-check form-switch mt-3">
            <input class="form-check-input" id="chxadvertise" name="optSubnetRoute" type="checkbox" value="1" checked />
            <label class="form-check-label" for="chxadvertise"><?php echo sprintf(_("Advertise a <strong>subnet route</strong> for the active <code>%s</code> AP interface"), $_SESSION['ap_interface']); ?></label>
            <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto"
               title="<?php echo _("Subnet routes let you extend your Tailscale network (known as a tailnet) to include devices that don't or can't run the Tailscale client."); ?>">
            </i>
            <p id="advertise-description">
              <small>
                <?php echo _("A subnet route acts as a gateway between your tailnet and a physical subnet. The subnet of the active AP interface is preconfigured below; edit if necessary."); ?>
              </small>
            </p>
            <input type="text" class="form-control cidr" name="cidr" id="cidr" value="<?php echo htmlspecialchars($subnet, ENT_QUOTES); ?>" />
          </div>

          <div class="form-check form-switch mt-3">
            <input class="form-check-input" id="chxallowlan" name="optAllowLan" type="checkbox" value="1" checked />
            <label class="form-check-label" for="chxallowlan"><?php echo _("Route LAN traffic through the exit node."); ?></label>
            <p id="advertise-description">
              <small>
                <?php echo _("This will direct all LAN traffic to go through your exit node only."); ?>
              </small>
            </p>
          </div>

          <div class="form-check form-switch mt-3">
            <input class="form-check-input" id="chxallowdns" name="optAllowDNS" type="checkbox" value="1" checked />
            <label class="form-check-label" for="chxallowdns"><?php echo _("Use Tailscale DNS settings (default)."); ?></label>
            <p id="advertise-description">
              <small>
                <?php echo _("Uncheck to use local DNS. This sets <code>--accept-dns=false</code>."); ?>
              </small>
            </p>
          </div>

          <div class="form-check form-switch mt-3">
            <input class="form-check-input" id="chxallowroutes" name="optAllowRoutes" type="checkbox" value="1" />
            <label class="form-check-label" for="chxallowroutes"><?php echo _("Do not use Tailscale subnets (default on Linux)."); ?></label>
            <p id="advertise-description">
              <small>
                <?php echo _("If subnet routes exist for your tailnet, you can route your device's traffic to a subnet router. Enabling this sets <code>--accept-routes=true</code>."); ?>
              </small>
            </p>
          </div>

          <div class="form-check form-switch mt-3">
            <input class="form-check-input" id="chxforcereauth" name="optForceReauth" type="checkbox" value="1" />
            <label class="form-check-label" for="chxforcereauth"><?php echo _("If keys expire for a device, connections to/from the given endpoint will stop working."); ?></label>
            <p id="advertise-description">
              <small>
                <?php echo _("This option uses <code>--force-reauth</code> to renew the keys for this device."); ?>
              </small>
            </p>
          </div>

          <div class="mt-3"><?php echo sprintf(_("Choose <strong>Next</strong> to configure <code>%s</code> to use the selected exit node with these options."), htmlspecialchars($this->hostname)); ?></div>
        </div>
        <?php else: ?>
          <div><?php echo _("No exit nodes found on your tailnet. Choose <strong>Back</strong> to continue."); ?></div> 
        <?php endif; ?>
      </div>
    </div>


