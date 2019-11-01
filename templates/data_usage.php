<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-chart-bar mr-2"></i><?php echo _("Data usage monitoring"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
			<div class="card-body">
				<ul id="tabbarBandwidth" class="nav nav-tabs" role="tablist">
					<li class="nav-item"><a class="nav-link active" href="#hourly" aria-controls="hourly" role="tab" data-toggle="tab"><?php echo _("Hourly"); ?></a></li>
					<li class="nav-item"><a class="nav-link" href="#daily" aria-controls="daily" role="tab" data-toggle="tab"><?php echo _("Daily"); ?></a></li>
					<li class="nav-item"><a class="nav-link" href="#monthly" aria-controls="monthly" role="tab" data-toggle="tab"><?php echo _("Monthly"); ?></a></li>
				</ul>
				<div id="tabsBandwidth" class="tabcontenttraffic tab-content">
					<div role="tabpanel" class="tab-pane active" id="hourly">
						<div class="row">
							<div class="col-lg-12">
								<h4 class="mt-3"><?php echo _('Hourly traffic amount'); ?></h4>
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
								<canvas id="divChartBandwidthhourly"></canvas>
								<div id="divTableBandwidthhourly"></div>
							</div>
						</div>
					</div><!-- /.tab-pane -->
					<div role="tabpanel" class="tab-pane fade" id="daily">
						<div class="row">
							<div class="col-lg-12">
								<h4 class="mt-3"><?php echo _('Daily traffic amount'); ?></h4>
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
								<canvas id="divChartBandwidthdaily"></canvas>
								<div id="divTableBandwidthdaily"></div>
							</div>
						</div>
					</div><!-- /.tab-pane -->
					<div role="tabpanel" class="tab-pane fade" id="monthly">
						<div class="row">
							<div class="col-lg-12">
								<h4 class="mt-3"><?php echo _("Monthly traffic amount"); ?></h4>
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
								<canvas id="divChartBandwidthmonthly"></canvas>
								<div id="divTableBandwidthmonthly"></div>
							</div>
						</div>
					</div><!-- /.tab-pane -->
				</div><!-- /.tabsBandwidth -->
			 </div><!-- /.card-body -->
			 <div class="card-footer"><?php echo _("Information provided by vnstat"); ?></div>
     </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
<script type="text/javascript"<?php //echo ' nonce="'.$csp_page_nonce.'"'; ?>>
// js translations:
var t = new Array();
t['send'] = '<?php echo addslashes(_('Send')); ?>';
t['receive'] = '<?php echo addslashes(_('Receive')); ?>';
</script>
