<!-- plugins tab -->
<div role="tabpanel" class="tab-pane" id="plugins">
  <h4 class="mt-3"><?php echo _("Plugins") ;?></h4>
  <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
  <div class="row">
    <div class="form-group col-lg-8 col-md-8">
    <label>
      <?php echo _("The following user plugins are available to extend RaspAP's functionality."); ?>
    </label>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
      <div class="small mt-2">
        <?php echo _("Choose <strong>Details</strong> for more information and to install a plugin."); ?>
      </div>
    <?php endif ?>
    <?php echo $pluginsTable; ?>
    </div>
  </div>
</div>

