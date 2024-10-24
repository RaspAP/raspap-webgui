<!-- logging tab -->
<div class="tab-pane fade" id="providerstatus">
  <h4 class="mt-3 mb-3"><?php echo sprintf(_("%s status"), $providerName) ;?></h4>

  <p><?php echo sprintf(_("Installed Linux CLI: <code>%s</code>"), $providerVersion); ?></p>
  <p><?php echo sprintf(_("Current <code>%s</code> connection status is displayed below."), strtolower($providerName)); ?></p>

  <div class="row">
    <div class="mb-3 col-md-8 mt-2">
      <textarea class="logoutput text-secondary"><?php echo htmlspecialchars($providerLog, ENT_QUOTES); ?></textarea>
    </div>
  </div>
</div><!-- /.tab-pane -->

