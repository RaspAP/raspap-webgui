<!-- active clients tab -->
<div class="tab-pane fade" id="captiveclientlist">
  <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
    <h4 class="mb-0"><?php echo _("Active clients"); ?></h4>
    <button type="button" id="js-clients-refresh" class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-rotate-right me-1"></i><?php echo _("Refresh"); ?>
    </button>
  </div>

  <?php if (!empty($portalClients)) : ?>
    <div class="row">
      <?php foreach ($portalClients as $client) : ?>
        <div class="col-lg-6 mb-3">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><?php echo _("Client"); ?> <?php echo htmlspecialchars($client['mac'], ENT_QUOTES); ?></h5>
                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($client['state'], ENT_QUOTES); ?></span>
              </div>

              <div class="small text-muted mb-1"><strong><?php echo _("IP"); ?>:</strong> <?php echo htmlspecialchars($client['ip'], ENT_QUOTES); ?></div>
              <div class="small text-muted mb-1"><strong><?php echo _("Token"); ?>:</strong> <?php echo htmlspecialchars($client['token'], ENT_QUOTES); ?></div>
              <div class="small text-muted mb-1"><strong><?php echo _("Download"); ?>:</strong> <?php echo htmlspecialchars($client['download'], ENT_QUOTES); ?></div>
              <div class="small text-muted mb-1"><strong><?php echo _("Upload"); ?>:</strong> <?php echo htmlspecialchars($client['upload'], ENT_QUOTES); ?></div>
              <div class="small text-muted mb-1"><strong><?php echo _("Last activity"); ?>:</strong> <?php echo htmlspecialchars($client['last_activity'], ENT_QUOTES); ?></div>
              <div class="small text-muted mb-1"><strong><?php echo _("Session start"); ?>:</strong> <?php echo htmlspecialchars($client['session_start'], ENT_QUOTES); ?></div>
              <div class="small text-muted mb-3"><strong><?php echo _("Session end"); ?>:</strong> <?php echo htmlspecialchars($client['session_end'], ENT_QUOTES); ?></div>

              <button
                type="button"
                class="btn btn-sm btn-outline-danger js-client-deauth"
                data-client-mac="<?php echo htmlspecialchars($client['mac'], ENT_QUOTES); ?>"
                <?php if ($client['state'] !== 'Authenticated') { echo 'disabled'; } ?>
              >
                <i class="fas fa-plug-circle-xmark me-1"></i><?php echo _("Deauthenticate"); ?>
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <div class="border p-3 text-center mb-3 rounded">
      <?php echo _("No active clients connected."); ?>
    </div>
  <?php endif; ?>
</div>
