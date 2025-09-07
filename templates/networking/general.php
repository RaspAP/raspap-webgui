  <div role="tabpanel" class="tab-pane active" id="summary">
    <h4 class="mt-3"><?php echo _("Internet connection"); ?></h4>
    <div class="row">
      <div class="col-sm-12">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th><?php echo _("Interface"); ?></th>
                <th><?php echo _("IP Address"); ?></th>
                <th><?php echo _("Gateway"); ?></th>
                <th colspan="2"><?php echo _("Internet Access"); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (isset($routeInfo["error"]) || empty($routeInfo)): ?>
              <tr><td colspan=5>No route to the internet found</td></tr>
              <?php else: ?>
                <?php foreach($routeInfo as $route): ?>
                  <tr>
                    <td><?php echo $route['interface'] ?></td>
                    <td><?php echo $route['ip-address'] ?></td>
                    <td><?php echo $route['gateway'] ?><br><?php $route['gw-name'] ?></td>
                    <?php if ($route['isAP']): ?>
                      <td>
                        <p class="m-0">Access point</p>
                      </td>
                    <?php else: ?>
                      <td>
                        <p class="m-0"  data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo "ping ".RASPI_ACCESS_CHECK_IP ?>">
                          <i class="fas <?php echo $route["access-ip"] ? "fa-check" : "fa-times"; ?>" ></i> <?php echo "IP" ?>
                        </p>
                        <p class="m-0" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo "ping ".RASPI_ACCESS_CHECK_DNS ?>">
                          <i class="fas <?php echo $route["access-dns"] ? "fa-check" : "fa-times"; ?>" ></i> <?php echo "DNS" ?>
                        </p>
                        <p class="m-0" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo RASPI_ACCESS_CHECK_URL ?>">
                          <i class="fas <?php echo $route["access-url"] ? "fa-check" : "fa-times"; ?>" ></i> <?php echo "HTTP" ?>
                        </p>
                      </td>
                    <?php endif ?>
                  </tr>
                <?php endforeach ?>
              <?php endif ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <h4 class="mt-3"><?php echo _("Routing table"); ?></h4>
    <div class="card h-100 w-100">
      <div class="card-header"><?php echo _("raw output") ?></div>
      <div class="card-body">
        <div class="row">
          <div class="col-sm-12">
            <div class="table-responsive">
              <table class="table">
                <tbody>
                  <?php foreach($routeInfoRaw as $route): ?>
                    <tr>
                      <pre class="unstyled"><?php echo $route; ?></pre>
                    </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <h4 class="mt-3"><?php echo _("Current settings") ?></h4>
    <div class="row">
      <?php if (!$bridgedEnabled) : // No interface details when bridged ?>
        <?php foreach ($interfaces as $interface): ?>
          <?php $if_quoted = htmlspecialchars($interface, ENT_QUOTES) ?>
          <div class="col-md mb-3">
            <div class="card h-100 w-100">
              <div class="card-header"><?php echo $if_quoted ?></div>
              <div class="card-body">
                <pre class="unstyled" id="<?php echo $if_quoted ?>-summary"></pre>
              </div>
            </div>
          </div>
        <?php endforeach ?>
      <?php endif ?>
    </div><!-- /.row -->

    <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>

  </div><!-- /.tabpanel -->

