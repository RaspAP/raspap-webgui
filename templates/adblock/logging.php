<!-- logging tab -->
<div class="tab-pane fade" id="adblocklogfileoutput">
  <h4 class="mt-3"><?php echo _("Logging"); ?></h4>
    <div class="row">
      <div class="form-group col-md-8">
        <?php echo '<textarea class="logoutput">'.htmlspecialchars($adblock_log, ENT_QUOTES).'</textarea>'; ?>
    </div>
  </div>
</div><!-- /.tab-pane -->

