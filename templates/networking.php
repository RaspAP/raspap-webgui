<div class="row">
  <div class="col-lg-12">
   <div class="panel panel-primary">
    <div class="panel-heading"><i class="fa fa-sitemap fa-fw"></i> <?php echo _("Configure networking"); ?></div>
      <div class="panel-body">
        <div id="msgNetworking"></div>
        <ul class="nav nav-tabs">
          <li role="presentation" class="active"><a href="#summary" aria-controls="summary" role="tab" data-toggle="tab"><?php echo _("Summary"); ?></a></li>
          <?php foreach ($interfaces as $if): ?>
          <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
          <li role="presentation"><a href="#<?php echo $if_quoted ?>" aria-controls="<?php echo $if_quoted ?>" role="tab" data-toggle="tab"><?php echo $if_quoted ?></a></li>
          <?php endforeach ?>
        </ul>
        <div class="tab-content">

          <div role="tabpanel" class="tab-pane active" id="summary">
            <h4><?php echo _("Current settings") ?></h4>
            <div class="row">
              <?php foreach ($interfaces as $if): ?>
              <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
              <div class="col-md-6">
                <div class="panel panel-default">
                  <div class="panel-heading"><?php echo $if_quoted ?></div>
                  <div class="panel-body">
                    <pre class="unstyled" id="<?php echo $if_quoted ?>-summary"></pre>
                  </div>
                </div>
              </div>
              <?php endforeach ?>
            </div><!-- /.row -->
            <div class="col-lg-12">
              <div class="row">
                <a href="#" class="btn btn-outline btn-primary" id="btnSummaryRefresh"><i class="fa fa-refresh"></i> <?php echo _("Refresh"); ?></a>
              </div><!-- /.row -->
            </div><!-- /.col-lg-12 -->
          </div><!-- /.tab-pane -->

          <?php foreach ($interfaces as $if): ?>
          <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
          <div role="tabpanel" class="tab-pane fade in" id="<?php echo $if_quoted ?>">
            <div class="row">
              <div class="col-lg-6">

                <form id="frm-<?php echo $if_quoted ?>">
                  <?php echo CSRFTokenFieldTag() ?>
                  <div class="form-group">
                    <h4><?php echo _("Adapter IP Address Settings") ?></h4>
                    <div class="btn-group" data-toggle="buttons">
                      <label class="btn btn-primary">
                        <input type="radio" name="<?php echo $if_quoted ?>-addresstype" id="<?php echo $if_quoted ?>-dhcp" autocomplete="off"><?php echo _("DHCP") ?>
                      </label>
                      <label class="btn btn-primary">
                        <input type="radio" name="<?php echo $if_quoted ?>-addresstype" id="<?php echo $if_quoted ?>-static" autocomplete="off"><?php echo _("Static IP") ?>
                      </label>
                    </div><!-- /.btn-group -->
                    <h4><?php echo _("Enable Fallback to Static Option") ?></h4>
                    <div class="btn-group" data-toggle="buttons">
                      <label class="btn btn-primary">
                        <input type="radio" name="<?php echo $if_quoted ?>-dhcpfailover" id="<?php echo $if_quoted ?>-failover" autocomplete="off"><?php echo _("Enabled") ?>
                      </label>
                      <label class="btn btn-warning">
                        <input type="radio" name="<?php echo $if_quoted ?>-dhcpfailover" id="<?php echo $if_quoted ?>-nofailover" autocomplete="off"><?php echo _("Disabled") ?>
                      </label>
                    </div><!-- /.btn-group -->
                  </div><!-- /.form-group -->

                  <hr />

                  <h4><?php echo _("Static IP Options") ?></h4>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-ipaddress"><?php echo _("IP Address") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-ipaddress" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-netmask"><?php echo _("Subnet Mask") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-netmask" placeholder="255.255.255.0">
                  </div>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-gateway"><?php echo _("Default Gateway") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-gateway" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-dnssvr"><?php echo _("DNS Server") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-dnssvr" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="<?php echo $if_quoted ?>-dnssvralt"><?php echo _("Alternate DNS Server") ?></label>
                    <input type="text" class="form-control" id="<?php echo $if_quoted ?>-dnssvralt" placeholder="0.0.0.0">
		  </div>
                  <?php if (!RASPI_MONITOR_ENABLED): ?>
                      <a href="#" class="btn btn-outline btn-primary intsave" data-int="<?php echo $if_quoted ?>"><?php echo _("Save settings") ?></a>
		      <a href="#" class="btn btn-warning intapply" data-int="<?php echo $if_quoted ?>"><?php echo _("Apply settings") ?></a>
                  <?php endif ?>
                </form>

              </div>
            </div><!-- /.tab-panel -->
          </div>
          <?php endforeach ?>

        </div><!-- /.tab-content -->
      </div><!-- /.panel-body -->
      <div class="panel-footer"><?php echo _("Information provided by /sys/class/net"); ?></div>
    </div><!-- /.panel-primary -->
  </div><!-- /.col-lg-12 -->
</div>
