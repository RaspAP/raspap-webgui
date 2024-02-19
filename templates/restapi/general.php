<div class="tab-pane active" id="restapisettings">
  <h4 class="mt-3"><?php echo ("RestAPI settings") ;?></h4>
  <div class="row">
    <div class="form-group col-lg-12 mt-3">
      <div class="row">
        <div class="form-group col-md-6" required>
          <label for="txtapikey"><?php echo _("API Key"); ?></label>
          <input type="text" id="txtapikey" class="form-control" name="txtapikey" value="<?php echo htmlspecialchars($apiKey, ENT_QUOTES); ?>" required />
          <div class="invalid-feedback">
            <?php echo _("Please provide a valid API key."); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div><!-- /.tab-pane | general tab -->

