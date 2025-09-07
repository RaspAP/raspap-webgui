<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-cube me-2"></i><?php echo _("System"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="system_info" method="POST">
        <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="nav-item"><a class="nav-link active" id="basictab" href="#basic" aria-controls="basic" role="tab" data-bs-toggle="tab"><?php echo _("Basic"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="languagetab" href="#language" aria-controls="language" role="tab" data-bs-toggle="tab"><?php echo _("Language"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="themetab" href="#theme" aria-controls="theme" role="tab" data-bs-toggle="tab"><?php echo _("Theme"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="advancedtab" href="#advanced" aria-controls="advanced" role="tab" data-bs-toggle="tab"><?php echo _("Advanced"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="toolstab" href="#tools" aria-controls="tools" role="tab" data-bs-toggle="tab"><?php echo _("Tools"); ?></a></li>
          <?php if (RASPI_PLUGINS_ENABLED) : ?>
          <li role="presentation" class="nav-item"><a class="nav-link" id="pluginstab" href="#plugins" aria-controls="plugins" role="tab" data-bs-toggle="tab"><?php echo _("Plugins"); ?></a></li>
          <?php endif ?>
        </ul>
          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("system/basic", $__template_data) ?>
            <?php echo renderTemplate("system/language", $__template_data) ?>
            <?php echo renderTemplate("system/theme", $__template_data) ?>
            <?php echo renderTemplate("system/advanced", $__template_data) ?>
            <?php echo renderTemplate("system/tools", $__template_data) ?>
            <?php if (RASPI_PLUGINS_ENABLED) : ?>
            <?php echo renderTemplate("system/plugins", $__template_data) ?>
            <?php endif ?>
          </div><!-- /.tab-content -->
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"><?php echo _("Information provided by raspap.sysinfo"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<!-- modal confirm-reset-->
<div class="modal fade" id="system-confirm-reset" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-history me-2"></i><?php echo _("System reset"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" data-message="<?php echo _("Reset complete. Restart the hotspot for the changes to take effect."); ?>" id="system-reset-message"><?php echo _("Reset RaspAP to its initial configuration? This action cannot be undone."); ?></div>
      </div>
      <div class="modal-footer">
      <button type="button" id="js-system-reset-cancel" data-message="<?php echo _("Close"); ?>" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo _("Cancel"); ?></button>
      <button type="button" id="js-system-reset-confirm" data-message="<?php echo _("System reset in progress..."); ?>" class="btn btn-outline-danger btn-delete"><?php echo _("Reset"); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- modal confirm-reboot-->
<div class="modal fade" id="system-confirm-reboot" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-sync me-2"></i><?php echo _("System reboot"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" id="system-reboot-message"><?php echo _("Reboot now? The system will be temporarily unavailable."); ?></div>
      </div>
      <div class="modal-footer">
      <button type="button" data-message="<?php echo _("Close"); ?>" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo _("Cancel"); ?></button>
      <button type="button" id="js-sys-reboot" data-action="reboot" class="btn btn-outline-danger btn-delete"><?php echo _("Reboot"); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- modal confirm-shutdown-->
<div class="modal fade" id="system-confirm-shutdown" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-power-off me-2"></i><?php echo _("System shutdown"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" id="system-reboot-message"><?php echo _("Shutdown now? The system will be unavailable."); ?></div>
      </div>
      <div class="modal-footer">
      <button type="button" data-message="<?php echo _("Close"); ?>" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo _("Cancel"); ?></button>
      <button type="button" id="js-sys-shutdown" data-action="shutdown" class="btn btn-outline-danger btn-delete"><?php echo _("Shutdown"); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- modal progress-debug-->
<div class="modal fade" id="debugModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-sync-alt fa-spin me-2"></i><?php echo _("Generate debug log"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" id="system-debug-message"><?php echo _("Debug log generation in progress..."); ?></div>
      </div>
      <div class="modal-footer">
      <button type="button" data-message="<?php echo _("Close"); ?>" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo _("Close"); ?></button>
      </div>
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

