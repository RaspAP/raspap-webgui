<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" class="btn btn-outline-primary" value="<?php echo _("Save settings"); ?>" name="save-settings" />
  <?php endif; ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<div class="row">
  <div class="col-lg-12">
    <div class="card shadow">

      <div class="card-header page-card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <i class="<?php echo $__template_data['icon']; ?> me-2"></i><?php echo _($__template_data['title']); ?>
          </div>
          <form id="frm-portal" action="<?php echo $__template_data['action']; ?>" method="POST">
            <?php echo \RaspAP\Tokens\CSRF::hiddenField();?>
            <input type="hidden" name="portal-action" id="portal-action" value="" />
            <div class="btn-group" role="group">
              <?php if (!RASPI_MONITOR_ENABLED) : ?>
                <?php if ($__template_data['serviceStatus'] === "up") : ?>
                  <button type="button" class="btn btn-sm btn-danger" title="<?php echo _("Stop portal") ?>" data-portal-action="portal-stop" data-bs-toggle="modal" data-bs-target="#portalModal">
                    <i class="fas fa-stop"></i>
                  </button>
                <?php else : ?>
                  <button type="button" class="btn btn-sm btn-light" title="<?php echo _("Start portal") ?>" data-portal-action="portal-start" data-bs-toggle="modal" data-bs-target="#portalModal">
                    <i class="fas fa-play"></i>
                  </button>
                <?php endif; ?>
              <?php endif; ?>
              <button type="button" class="btn btn-light btn-icon-split btn-sm service-status float-end">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $__template_data['serviceStatus'] ?>"></i></span>
                <span class="text service-status"><?php echo $__template_data['serviceName'];?> <?php echo $__template_data['serviceStatus'] ?></span>
              </button>
            </div>
          </form>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form id="frm-portal" action="<?php echo $__template_data['action']; ?>" method="POST">
          <?php echo \RaspAP\Tokens\CSRF::hiddenField();?>

          <!-- Nav tabs -->
          <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs">
              <li class="nav-item"><a class="nav-link active" id="captivebasictab" href="#captivebasic" aria-controls="basic" data-bs-toggle="tab"><?php echo _("Basic"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="captiveclientstab" href="#captiveclients" aria-controls="clients" data-bs-toggle="tab"><?php echo _("Clients"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="captiveadvancedtab" href="#captiveadvanced" aria-controls="advanced" data-bs-toggle="tab"><?php echo _("Advanced"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="captivestatustab" href="#captivestatus" aria-controls="status" data-bs-toggle="tab"><?php echo _("Status"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="captiveclientlisttab" href="#captiveclientlist" aria-controls="clientlist" data-bs-toggle="tab"><?php echo _("Active clients"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="captiveabouttab" href="#captiveabout" aria-controls="about" data-bs-toggle="tab"><?php echo _("About"); ?></a></li>
            </ul>
          </div>

          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("tabs/basic", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/clients", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/advanced", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/status", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/clientlist", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/about", $__template_data, $__template_data['pluginName']) ?>
          </div><!-- /.tab-content -->

          <div class="d-flex flex-wrap gap-2">
            <?php echo $buttons ?>
          </div>
        </form>
      </div><!-- /.card-body -->

      <form id="frm-portal-clients" action="<?php echo $__template_data['action']; ?>" method="POST" style="display:none;">
        <?php echo \RaspAP\Tokens\CSRF::hiddenField();?>
        <input type="hidden" name="portal-action" id="portal-client-action" value="">
        <input type="hidden" name="client_mac" id="portal-client-mac" value="">
      </form>

      <div class="card-footer"> <?php echo _("Information provided by nodogsplash"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<!-- Modal -->
<div class="modal fade" id="portalModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title" id="ModalLabel">
           <i class="<?php echo $__template_data['icon']; ?> me-2"></i><?php if($__template_data['serviceStatus'] === "up") echo _("Stop portal service"); else echo _("Start portal service"); ?>
        </div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1">
          <?php echo _("Changing the portal service will momentarily disrupt client traffic. Choose <strong>Proceed</strong> to continue."); ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo _("Cancel"); ?></button>
        <button type="button" id="js-portal" data-action="execute" class="btn btn-outline-primary"><?php echo _("Proceed"); ?></button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle MAC list visibility based on mechanism
    const macMechanism = document.getElementById('cbxmacmechanism');
    const blockedGroup = document.getElementById('blockedMACGroup');
    const allowedGroup = document.getElementById('allowedMACGroup');

    const portalModal = document.getElementById('portalModal');
    const jsPortal = document.getElementById('js-portal');
    const portalAction = document.getElementById('portal-action');
    const frmPortal = document.getElementById('frm-portal');
    const frmPortalClients = document.getElementById('frm-portal-clients');
    const portalClientAction = document.getElementById('portal-client-action');
    const portalClientMac = document.getElementById('portal-client-mac');

    if (macMechanism) {
        macMechanism.addEventListener('change', function() {
            if (this.value === 'block') {
                blockedGroup.style.display = 'flex';
                allowedGroup.style.display = 'none';
            } else {
                blockedGroup.style.display = 'none';
                allowedGroup.style.display = 'flex';
            }
        });
    }

    if (portalModal) {
        portalModal.addEventListener('show.bs.modal', function (event) {
            const triggerButton = event.relatedTarget;
            if (triggerButton) {
                const action = triggerButton.getAttribute('data-portal-action');
                if (portalAction) {
                    portalAction.value = action;
                }
            }
        });
    }

    if (jsPortal && frmPortal) {
        jsPortal.addEventListener('click', function () {
            frmPortal.submit();
        });
    }

    document.querySelectorAll('.js-client-deauth').forEach(function(btn) {
      btn.addEventListener('click', function () {
        const mac = this.getAttribute('data-client-mac') || '';
        const ok = window.confirm(`Are you sure you want to deauthenticate this client (${mac})?`);
        if (!ok) {
          return;
        }
        if (!frmPortalClients || !portalClientAction || !portalClientMac) {
          return;
        }
        portalClientAction.value = 'portal-client-deauth';
        portalClientMac.value = mac;
        frmPortalClients.submit();
      });
    });

    const refreshButton = document.getElementById('js-clients-refresh');
    if (refreshButton) {
      refreshButton.addEventListener('click', function () {
        window.location.reload();
      });
    }
});
</script>

