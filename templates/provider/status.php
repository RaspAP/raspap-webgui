<!-- logging tab -->
<div class="tab-pane fade" id="providerstatus">
  <h4 class="mt-3 mb-3"><?php echo _("Status") ?></h4>
  <p><?php echo _("Current <code>".strtolower($providerName)."</code> connection status is displayed below.") ?></p>

  <div class="row">
    <div class="form-group col-md-8 mt-2">
      <textarea class="logoutput"><?php echo htmlspecialchars($providerLog, ENT_QUOTES); ?></textarea>
    </div>
  </div>
</div><!-- /.tab-pane -->

