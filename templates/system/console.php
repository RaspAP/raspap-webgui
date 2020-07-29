<!-- console tab -->
<div role="tabpanel" class="tab-pane" id="console">
  <div class="row">
    <div class="col-lg-12 mt-3">
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
      <iframe src="/includes/webconsole.php" class="webconsole"></iframe>
    <?php endif ?>
    </div>
  </div>
</div>

