<div class="tab-pane fade" id="wgconfigs">
  <div class="row">
    <div class="col-md">
      <h4 class="mt-3 mb-3"><?php echo _("Configurations"); ?></h4>
        <p id="wgconfigs-description" class="mb-3">
          <small><?php echo _("Currently available WireGuard file configurations are displayed below.") ?></small>
          <br><small class="text-muted"><?php echo _("Activating a configuration will restart the <code>wg-quick</code> service.") ?></small>
        </p>
        <div class="wg-configs js-wg-configs-container">
          <?php foreach ($configs as $config) :
                if ($config === $conf_default) {
                    $btn_class = "active";
                } else {
                    $btn_class = "";
                }
                $label = preg_replace('/.conf/','',pathinfo($config, PATHINFO_FILENAME));
                $config = $label;
          ?>
            <div class="row mt-2" id="wg-config-row-<?php echo htmlspecialchars($config, ENT_QUOTES); ?>" >
              <div class="col-md-6 col-xs-4">
                <label><?php echo htmlspecialchars($label, ENT_QUOTES); ?></label>
              </div>
              <div class="col-md-auto px-lg-3 col-xs-2">
                <button type="button" class="btn btn-outline-success <?php echo $btn_class; ?> js-activate-wg-config" data-record-id="<?php echo htmlspecialchars($config, ENT_QUOTES); ?>" data-bs-toggle="modal" data-bs-target="#wg-confirm-activate" /><i class="far fa-check-circle"></i></button>
              </div>
              <div class="col-md-auto col-xs-2">
                <button type="button" class="btn btn-outline-danger js-remove-wg-config" data-record-id="<?php echo htmlspecialchars($config, ENT_QUOTES); ?>" data-bs-toggle="modal" data-bs-target="#wg-confirm-delete" /><i class="far fa-trash-alt"></i></button>
              </div>
            </div><!-- ./row wg-config -->
          <?php endforeach ?>
       </div><!-- /.wg-configs -->
      <div class="mb-3"></div>
    </div><!-- /.tab-pane | manage configs tab -->
  </div>
</div>
