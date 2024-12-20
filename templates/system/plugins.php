<!-- plugins tab -->
<div role="tabpanel" class="tab-pane" id="plugins">
  <h4 class="mt-3"><?php echo _("Plugins") ;?></h4>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <?php echo CSRFTokenFieldTag() ?>

      <div class="row">
        <div class="form-group col-lg-8 col-md-8">
          <label>
            <?php echo _("Available plugins"); ?>
          </label>
          <div class="small">
          <?php echo _("The plugins below have been verified by RaspAP."); ?>
          </div>
        </div>
      </div>

      <div class="row mt-3">
        <div class="form-group col-lg-8 col-md-8">
          <label for="reset">
            <?php echo _("Upload a user plugin"); ?>
          </label>
          <div class="small">
          <?php echo _("Custom user plugins may also be uploaded."); ?>
          </div>
        </div>
      </div>
      <button type="button" class="btn btn-outline btn-primary mt-3" name="plugin-upload" data-toggle="modal" data-target="#system-plugin-upload" />
        <i class="fas fa-upload ml-1"></i> <?php echo _("Upload plugin"); ?>
      </button>

    <?php endif ?>
</div>

