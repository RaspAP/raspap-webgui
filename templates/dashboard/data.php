<div class="tab-pane" id="data">
  <h4 class="card-title mt-3">
    <?php echo _("Hourly traffic"); ?>
  </h4>

  <div class="col-md-12">
    <div class="col dbChart">
      <canvas id="divDBChartBandwidthhourly"></canvas>
    </div>
  </div>

</div><!-- /.tab-pane | data tab -->

<script type="text/javascript"<?php //echo ' nonce="'.$csp_page_nonce.'"'; ?>>
  // js translations:
  var t = new Array();
  t['send'] = '<?php echo addslashes(_('Send')); ?>';
  t['receive'] = '<?php echo addslashes(_('Receive')); ?>';
</script>
