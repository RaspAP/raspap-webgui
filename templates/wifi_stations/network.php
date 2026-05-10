<?php

use RaspAP\Networking\Hotspot\WiFiManager;
$wifi = new WiFiManager();

// fix: re-apply locale settings in this template context
if (!empty($_SESSION['locale'])) {
    putenv("LANG=" . $_SESSION['locale']);
    setlocale(LC_ALL, $_SESSION['locale']);
    bindtextdomain('messages', realpath(__DIR__ . '/../../locale'));
    bind_textdomain_codeset('messages', 'UTF-8');
    textdomain('messages');
}

// set defaults
$network = $network ?? [];
$network['ssid'] = $network['ssid'] ?? '';
$network['ssidutf8'] = $network['ssidutf8'] ?? $network['ssid'];
$network['configured'] = $network['configured'] ?? false;
$network['connected'] = $network['connected'] ?? false;
$network['visible'] = $network['visible'] ?? false;
$network['channel'] = $network['channel'] ?? '';
$network['protocol'] = $network['protocol'] ?? $wifi::SECURITY_OPEN;
$network['passphrase'] = $network['passphrase'] ?? '';
?>
<div class="card">
  <div class="card-body">
    <input type="hidden" name="ssid<?= $index ?>" value="<?= htmlentities($network['ssid'], ENT_QUOTES) ?>" />
    <!-- persistence inputs -->
    <input type="hidden" name="protocol<?= $index ?>" value="<?= htmlspecialchars($network['protocol'], ENT_QUOTES); ?>" />

    <?php if (strlen($network['ssid']) == 0) {
        $network['ssid'] = "(unknown)";
    } ?>
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="d-flex align-items-center gap-2">
        <h5 class="card-title mb-0"
          title="<?= _('Network ID: ') . $index ?>"
          data-bs-toggle="tooltip"
          data-bs-placement="bottom"
        ><?= htmlspecialchars($network['ssidutf8'], ENT_QUOTES); ?></h5>
        <?php if ($network['configured']) { ?>
          <i class="fas fa-bookmark"></i>
        <?php } ?>
        <?php if ($network['connected']) { ?>
          <i class="fas fa-wifi text-success"></i>
        <?php } ?>
        <?php if ($index == -1) { ?>
          <span title="<?= _('Error finding network'); ?>"
            data-bs-toggle="tooltip"
            data-bs-placement="bottom"
          ><i class="fas fa-triangle-exclamation"></i></span>
        <?php } ?>
      </div>
      <?php if ($network['configured']) { ?>
				<button type="submit" class="btn btn-sm btn-outline-danger border-0" name="delete<?= $index ?>" data-bs-toggle="modal" data-bs-target="#configureClientModal">
          <i class="fas fa-trash-can"></i>
        </button>
			<?php } ?>
    </div>

    <div class="d-flex justify-content-between w-100 mb-2">
      <div class="d-flex flex-column">
        <div class="text-muted"><?= _("Channel"); ?></div>
        <div>
          <?php if ($network['visible']) { ?>
            <?= htmlspecialchars($network['channel'], ENT_QUOTES) ?>
          <?php } else { ?>
            <span class="label label-warning">&mdash;</span>
          <?php } ?>
        </div>
      </div>

      <div class="d-flex flex-column">
        <div class="text-muted"><?= _("RSSI"); ?></div>
        <div>
          <?php if (isset($network['RSSI']) && $network['RSSI'] >= -200) : ?>
            <div class="d-flex justify-content-start">
              <?= $wifi->getSignalBars($network['RSSI']); ?>
              <div class="ms-2"><?= htmlspecialchars($network['RSSI'], ENT_QUOTES); ?>dB</div>
            </div>
          <?php else : ?>
            <span class="label label-warning">&mdash;</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="d-flex flex-column">
        <div class="text-muted"><?= _("Security"); ?></div>
        <div><?= empty($network['protocol']) ? "&mdash;" : $network['protocol'] ?></div>
      </div>
    </div>

    <div class="mb-3">
      <div class="info-item-wifi text-muted mb-1"><?= _("Passphrase"); ?></div>
      <div class="input-group input-group-sm">
        <?php if ($network['protocol'] === $wifi::SECURITY_OPEN) { ?>
          <input type="password" disabled class="form-control" aria-describedby="passphrase" name="passphrase<?= $index ?>" value="" />
        <?php } else { ?>
          <input type="password"
            class="form-control network-passphrase"
            aria-describedby="passphrase"
            name="passphrase<?= $index ?>"
            value="<?= htmlspecialchars($network['passphrase']); ?>"
            data-init-value="<?= htmlspecialchars($network['passphrase']); ?>"
            data-bs-target="#update<?= $index ?>"
            data-colors="#ffd0d0,#d0ffd0"
          />
          <div class="input-group-text js-toggle-password" data-bs-target="[name=passphrase<?= $index ?>]" data-toggle-with="fas fa-eye-slash">
            <i class="fas fa-eye mx-2"></i>
          </div>
        <?php } ?>
      </div>
      <?php if ($network['configured']) { ?>
        <button type="submit"
          id="update<?= $index ?>"
          class="btn btn-sm btn-warning w-100 mt-1 fade update-passphrase"
          style="display: none;"
          name="update<?= $index ?>"
          <?= ($network['protocol'] === 'Open' ? ' disabled' : '')?>
        >
          <?= _("Update"); ?>
        </button>
      <?php } ?>
    </div>

    <div class="d-flex">
      <div class="flex-grow-1 btn-group btn-block d-flex">
        <?php if ($network['configured']) { ?>
          <?php if ($network['connected']) { ?>
            <button type="submit" class="btn btn-primary" value="<?= $index?>" name="disconnect<?= $index ?>">
              <?= _("Disconnect"); ?>
            </button>
          <?php } else { ?>
            <button type="submit" class="btn btn-primary" value="<?= $index?>" name="connect">
              <?= _("Connect"); ?>
            </button>
          <?php } ?>
        <?php } else { ?>
          <button type="submit" id="update<?= $index ?>" class="btn btn-primary" name="update<?= $index ?>">
            <?= _("Add"); ?>
          </button>
        <?php } ?>
      </div>
      <?php if ($network['configured']) { ?>
        <button type="button" class="open-advanced btn btn-link"><i class="fas fa-cog"></i></button>
      <?php } ?>
    </div>

    <!-- Advanced pane -->
    <div id="advanced<?= $index ?>" class="network-advanced d-flex flex-column">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title mb-0"><?= htmlspecialchars($network['ssidutf8'], ENT_QUOTES); ?></h5>
        <button type="button" class="close-advanced btn btn-sm btn-outline-secondary border-0"><i class="fas fa-times"></i></button>
      </div>
      <div class="flex-grow-1 d-flex flex-column gap-2 mb-2">
        <div class="d-flex align-items-center gap-2">
          <div class="text-muted"><?= _("Priority"); ?></div>
          <div>
            <input type="number"
              class="form-control form-control-sm"
              min="0"
              max="100"
              name="priority<?= $index ?>"
              value="<?= htmlspecialchars(isset($network['priority']) ? $network['priority'] : '', ENT_QUOTES); ?>"
            />
          </div>
          <button type="button" class="btn btn-link clear-priority">
            <?= _('Clear'); ?>
          </button>
        </div>
      </div>
      <button type="submit"
        id="update<?= $index ?>"
        class="btn btn-sm btn-warning w-100"
        name="update<?= $index ?>"
      >
        <?= _("Update"); ?>
      </button>
    </div>
  </div><!-- /.card-body -->
</div><!-- /.card -->
