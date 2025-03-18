<?php $_SESSION['lastActivity'] = time(); ?>

<div class="d-flex align-items-center justify-content-between small">
  <div class="text-muted">
    <span class="pe-2"><a href="/about">v<?php echo RASPI_VERSION; ?></a></span>  |
    <span class="ps-2"><?php echo sprintf(_('Created by the <a href="%s" target="_blank" rel="noopener">%s</a>'), 'https://github.com/RaspAP', _('RaspAP Team')); ?></span>
  </div>
  <div class="text-muted">
    <i class="fas fa-heart heart"></i> <a href="https://docs.raspap.com/insiders" target="_blank" rel="noopener"><?php echo _("Get Insiders"); ?></a>
  </div>
</div>

<div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" id="sessionTimeoutModal" tabindex="-1" aria-labelledby="sessionTimeoutLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title" id="sessionTimeoutLabel"><i class="fa fa-clock me-2"></i><?php echo _("Session Expired"); ?></div>
      </div>
      <div class="modal-body">
        <?php echo _("Your session has expired. Please login to continue.") ?>
      </div>
      <div class="modal-footer">
        <button type="button" id="js-session-expired-login" class="btn btn-outline btn-primary"><?php echo _("Login"); ?></button>
      </div>
    </div>
  </div>
</div>

