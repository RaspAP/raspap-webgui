<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
	      <div class="col">
            <i class="fas fa-plug-circle-bolt me-2"></i><?php echo _("Plugins"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
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

<!-- modal install-plugin -->
<div class="modal fade" id="install-user-plugin" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-plug me-2"></i><?php echo _("Plugin details"); ?></div>
      </div>
      <div class="modal-body">

        <i id="plugin-icon" class="fas fa-plug link-secondary me-2"></i><span id="plugin-name" class="h4 mb-0"></span>
        <p id="plugin-description" class="mb-1"></p>
        <p id="plugin-additional" class="mb-3"></p>

        <table class="table table-bordered">
          <tbody>
            <tr>
              <th><?php echo _("Plugin docs"); ?></th>
              <td><span id="plugin-docs"></span></td>
            </tr>
            <tr>
              <th><?php echo _("Version"); ?></th>
              <td><span id="plugin-version"></span></td>
            </tr>
            <tr>
              <th><?php echo _("Author"); ?></th>
              <td><span id="plugin-author"></span></td>
            </tr>
            <tr>
              <th><?php echo _("License"); ?></th>
              <td><span id="plugin-license"></span></td>
            </tr>
            <tr>
              <th><?php echo _("Language locale"); ?></th>
              <td><small><code><span id="plugin-locale"></span></span></code></td>
            </tr>
            <tr>
              <th><?php echo _("Configuration files"); ?></th>
              <td><small><code><span id="plugin-configuration" class="mb-0"></span></code></small></td>
            </tr>
            <tr>
              <th><?php echo _("Signed Packages"); ?></th>
              <td><small><code><span id="plugin-packages" class="mb-0"></span></code></small></td>
            </tr>
            <tr>
              <th><?php echo _("Dependencies"); ?></th>
              <td><small><code><span id="plugin-dependencies" class="mb-0"></span></code></small></td>
            </tr>
            <tr>
              <th><?php echo _("JavaScript"); ?></th>
              <td><small><code><span id="plugin-javascript" class="mb-0"></span></code></small></td>
            </tr>
            <tr>
              <th><?php echo _("Sudoers"); ?></th>
              <td><small><code><span id="plugin-sudoers" class="mb-0"></span></code></small></td>
            </tr>
            <tr>
              <th><?php echo _("Non-privileged users"); ?></th>
              <td><small><code><span id="plugin-user-name"></span></small></code></p></td>
            </tr>
          </tbody>
        </table>

      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo _("Cancel"); ?></button>
      <button type="button" id="js-install-plugin-confirm" class="btn btn-outline-success btn-activate"></button>
      </div>
    </div>
  </div>
</div>

<!-- modal plugin-install-progress -->
<div class="modal fade" id="install-plugin-progress" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-download me-2"></i><?php echo _("Installing plugin"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" data-message="<?php echo _("Plugin install completed."); ?>" id="plugin-install-message"><?php echo _("Plugin installation in progress..."); ?><i class="fas fa-cog fa-spin link-secondary ms-2"></i></div>
      </div>
      <div class="modal-footer">
      <button type="button" id="js-install-plugin-ok" class="btn btn-outline-success btn-activate" disabled><?php echo _("OK"); ?></button>
      </div>
    </div>
  </div>
</div>

