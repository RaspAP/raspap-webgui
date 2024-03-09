<div class="tab-pane active" id="restapisettings">
  <h4 class="mt-3"><?php echo ("RestAPI settings") ;?></h4>
  <div class="row">
    <div class="form-group col-lg-12 mt-2">
      <div class="row">
        <div class="col-md-6">
          <?php echo $docMsg; ?>
        </div>
      </div>
      <div class="row mt-3">
        <div class="form-group col-md-6" required>
          <label for="txtapikey"><?php echo _("API Key"); ?></label>
          <div class="input-group">
              <input type="text" class="form-control" id="txtapikey" name="txtapikey" value="<?php echo htmlspecialchars($apiKey, ENT_QUOTES); ?>" required />
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="gen_apikey"><i class="fas fa-magic"></i></button>
              </div>
              <div class="invalid-feedback">
                <?php echo _("Please provide a valid API key."); ?>
              </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div><!-- /.tab-pane | general tab -->

