<div class="tab-pane fade show active" id="general">
  <h4 class="mt-3 mb-3"><?php echo _("General"); ?></h4>
  
  <div class="card mb-3">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center">
        <span><?php echo _("Interface Configurations"); ?></span>
        <button class="btn btn-primary btn-sm" type="button" onclick="window.location.reload();">
          <i class="fas fa-sync-alt"></i><span class="sr-only"><?php echo _("Refresh"); ?></span>
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th class="text-nowrap"><?php echo _("Interface"); ?></th>
              <th class="text-nowrap"><?php echo _("Static IP"); ?></th>
              <th class="text-nowrap"><?php echo _("Subnet / Gateway"); ?></th>
              <th class="text-nowrap"><?php echo _("Default Route"); ?></th>
              <th class="text-nowrap"><?php echo _("Disable Hook"); ?></th>
              <th class="text-nowrap"><?php echo _("DHCP Range (lease)"); ?></th>
              <th class="text-nowrap"><?php echo _("DNS Servers"); ?></th>
              <th class="text-nowrap"><?php echo _("Metric"); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($interface_configs)): ?>
              <?php foreach ($interface_configs as $iface => $data): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($iface); ?></strong></td>

                  <td>
                    <?php
                      if ($data['StaticIP']) {
                        echo $data['FallbackEnabled'] === true ? 'DHCP (' . htmlspecialchars($data['StaticIP']) . ')' : htmlspecialchars($data['StaticIP']);
                      } else {
                        echo '<span class="text-muted">—</span>';
                      }
                    ?>
                  </td>

                  <td>
                    <?php
                      $parts = [];
                      if ($data['SubnetMask']) $parts[] = htmlspecialchars($data['SubnetMask']);
                      if ($data['StaticRouters']) $parts[] = htmlspecialchars($data['StaticRouters']);
                      echo $parts ? implode(' / ', $parts) : '<span class="text-muted">—</span>';
                    ?>
                  </td>
                  
                  <td>
                    <?php
                      echo isset($data['DefaultRoute']) && $data['DefaultRoute'] ? _("Yes") : '<span class="text-muted">—</span>';
                    ?>
                  </td>

                  <td>
                    <?php
                      echo isset($data['NoHookWPASupplicant']) && $data['NoHookWPASupplicant'] ? _("Yes") : '<span class="text-muted">—</span>';
                    ?>
                  </td>

                  <td>
                    <?php
                      $parts = [];
                      if ($data['RangeStart']) $parts[] = htmlspecialchars($data['RangeStart']);
                      if ($data['RangeEnd']) $parts[] = htmlspecialchars($data['RangeEnd']);
                      echo $parts
                        ? implode(' - ', $parts) . (
                          $data['leaseTime']
                            ? ' (' . ($data['leaseTime'] . $data['leaseTimeInterval'] ?? '—') . ')'
                            : ''
                        )
                        : '<span class="text-muted">—</span>';
                    ?>
                  </td>

                  <td>
                    <?php
                      $parts = [];
                      if ($data['DNS1']) $parts[] = htmlspecialchars($data['DNS1']);
                      if ($data['DNS2']) $parts[] = htmlspecialchars($data['DNS2']);
                      echo $parts ? implode('<br>', $parts) : '<span class="text-muted">—</span>';
                    ?>
                  </td>

                  <td class="small text-muted">
                    <?php echo isset($data['Metric']) ? htmlspecialchars($data['Metric']) : '<span class="text-muted">—</span>'; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted">
                  <?php echo _("No configuration found in dhcpcd.conf or dnsmasq.d for any interfaces."); ?>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- /.tab-pane -->
