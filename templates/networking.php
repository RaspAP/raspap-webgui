<div class="row">
  <div class="col-lg-12">
   <div class="card">
    <div class="card-header">
      <div class="row">
        <div class="col">
          <i class="fas fa-network-wired mr-2"></i><?php echo _("Networking"); ?>
        </div>
      </div><!-- ./row -->
     </div><!-- ./card-header -->
      <div class="card-body">
        <div id="msgNetworking"></div>
        <ul class="nav nav-tabs">
          <li role="presentation" class="nav-item"><a class="nav-link active" href="#summary" aria-controls="summary" role="tab" data-toggle="tab"><?php echo _("Summary"); ?></a></li>
        </ul>
        <div class="tab-content">
          <div role="tabpanel" class="tab-pane active" id="summary">
            <h4 class="mt-3"><?php echo _("Internet connection"); ?></h4>
            <div class="row">
             <div class="col-sm-12">
              <div class="card ">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th><?php echo _("Interface"); ?></th>
                          <th><?php echo _("IP Address"); ?></th>
                          <th><?php echo _("Gateway"); ?></th>
                          <th colspan="2"><?php echo _("Internet Access"); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        if (isset($routeInfo["error"]) || empty($routeInfo)) {
                            echo "<tr><td colspan=5>No route to the internet found</td></tr>";
                        } else {
                            foreach($routeInfo as $route) {
                                echo "<tr>";
                                echo "<td>".$route["interface"]."</td>";
                                echo "<td>".$route["ip-address"]."</td>";
                                echo "<td>".$route["gateway"]."<br>".$route["gw-name"]."</td>";
                                $status = $route["access-ip"] ? "fa-check" : "fa-times";
                                echo '<td><i class="fas '.$status.'"></i><br>'.RASPI_ACCESS_CHECK_IP.'</td>';
                                $status = $route["access-dns"] ? "fa-check" : "fa-times";
                                echo '<td><i class="fas '.$status.'"></i><br>'.RASPI_ACCESS_CHECK_DNS.'</td>';
                                echo "</tr>";
                            }
                        }
                        ?>
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
              <?php foreach ($interfaces as $if): ?>
                    <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
              <div class="col-md-6 mb-3">
                <div class="card">
                  <div class="card-header"><?php echo $if_quoted ?></div>
                  <div class="card-body">
                    <pre class="unstyled" id="<?php echo $if_quoted ?>-summary"></pre>
                  </div>
                </div>
              </div>
              <?php endforeach ?>
            <?php endif ?>
            </div><!-- /.row -->
            <div class="col-lg-12">
              <div class="row">
                <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /.card-body -->
      <div class="card-footer"><?php echo _("Information provided by /sys/class/net"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

