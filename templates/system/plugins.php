<!-- plugins tab -->
<div role="tabpanel" class="tab-pane" id="plugins">
  <h4 class="mt-3"><?php echo _("Plugins") ;?></h4>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <?php echo CSRFTokenFieldTag() ?>

      <div class="row">
        <div class="form-group col-lg-8 col-md-8">
          <label>
            <?php echo _("The following user plugins are available to extend RaspAP's functionality."); ?>
          </label>
          <div class="small mt-3">
          <?php echo _("Choose <strong>Install</strong> to download and activate a plugin from the list. <strong>Uninstall</strong> removes an existing plugin."); ?>
          </div>
          <?php echo $pluginsTable; ?>
        </div>
      </div>

    <?php endif ?>
</div>

