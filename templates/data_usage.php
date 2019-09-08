<div class="row">
  <div class="col-lg-12">
    <div class="panel panel-primary">
      <div class="panel-heading"><i class="fa fa-bar-chart fa-fw"></i> <?php echo _("Data usage monitoring"); ?></div>
        <div class="panel-body">

          <ul id="tabbarBandwidth" class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#hourly" aria-controls="hourly" role="tab" data-toggle="tab"><?php echo _("Hourly"); ?></a></li>
            <li role="presentation" class=""><a href="#daily" aria-controls="daily" role="tab" data-toggle="tab"><?php echo _("Daily"); ?></a></li>
            <li role="presentation" class=""><a href="#monthly" aria-controls="monthly" role="tab" data-toggle="tab"><?php echo _("Monthly"); ?></a></li>
          </ul>

          <div id="tabsBandwidth" class="tabcontenttraffic tab-content">
            <div role="tabpanel" class="tab-pane active fade in" id="hourly">
              <div class="row">
                <div class="col-lg-12">
                  <h4><?php echo _('Hourly traffic amount'); ?></h4>
                  <label for="cbxInterfacehourly"><?php echo _('interface'); ?></label>
                  <select id="cbxInterfacehourly" class="form-control" name="interfacehourly">
                    <?php foreach ($interfaces as $if) : ?>
                        <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
                      <option value="<?php echo $if_quoted ?>"><?php echo $if_quoted ?></option>
                    <?php endforeach ?>
                  </select>
                  <div class="hidden alert alert-info" id="divLoaderBandwidthhourly">
                    <?php echo sprintf(_("Loading %s bandwidth chart"), _('hourly')); ?>
                  </div>
                  <div id="divChartBandwidthhourly"></div>
                  <div id="divTableBandwidthhourly"></div>
                </div>
              </div>
            </div><!-- /.tab-pane -->
            <div role="tabpanel" class="tab-pane fade" id="daily">
              <div class="row">
                <div class="col-lg-12">
                  <h4><?php echo _('Daily traffic amount'); ?></h4>
                  <label for="cbxInterfacedaily"><?php echo _('interface'); ?></label>
                  <select id="cbxInterfacedaily" class="form-control" name="interfacedaily">
                    <?php foreach ($interfaces as $if) : ?>
                        <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
                      <option value="<?php echo $if_quoted ?>"><?php echo $if_quoted ?></option>
                    <?php endforeach ?>
                  </select>
                  <div class="hidden alert alert-info" id="divLoaderBandwidthdaily">
                    <?php echo sprintf(_("Loading %s bandwidth chart"), _('daily')); ?>
                  </div>
                  <div id="divChartBandwidthdaily"></div>
                  <div id="divTableBandwidthdaily"></div>
                </div>
              </div>
            </div><!-- /.tab-pane -->
            <div role="tabpanel" class="tab-pane fade" id="monthly">
              <div class="row">
                <div class="col-lg-12">
                  <h4><?php echo _("Monthly traffic amount"); ?></h4>
                  <label for="cbxInterfacemonthly"><?php echo _('interface'); ?></label>
                  <select id="cbxInterfacemonthly" class="form-control" name="interfacemonthly">
                    <?php foreach ($interfaces as $if) : ?>
                        <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
                      <option value="<?php echo $if_quoted ?>"><?php echo $if_quoted ?></option>
                    <?php endforeach ?>
                  </select>
                  <div class="hidden alert alert-info" id="divLoaderBandwidthmonthly">
                    <?php echo sprintf(_("Loading %s bandwidth chart"), _('monthly')); ?>
                  </div>
                  <div id="divChartBandwidthmonthly"></div>
                  <div id="divTableBandwidthmonthly"></div>
                </div>
              </div>
            </div><!-- /.tab-pane -->
          </div><!-- /.tabsBandwidth -->

         </div><!-- /.panel-body -->
       <div class="panel-footer"><?php echo _("Information provided by vnstat"); ?></div>
     </div><!-- /.panel-primary -->
    </div><!-- /.panel-primary -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
<script type="text/javascript"<?php //echo ' nonce="'.$csp_page_nonce.'"'; ?>>
// js translations:
var t = new Array();
t['send'] = '<?php echo addslashes(_('Send')); ?>';
t['receive'] = '<?php echo addslashes(_('Receive')); ?>';
</script>
