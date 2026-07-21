<?php

$escape = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$selected = static fn($value, $expected): string => (string) $value === (string) $expected ? ' selected' : '';
$checked = static fn($value): string => !empty($value) ? ' checked' : '';
$formatTimestamp = static function ($value): string {
    $timestamp = is_string($value) ? strtotime($value) : false;
    return $timestamp === false ? (string) ($value ?? '') : date('Y-m-d H:i:s T', $timestamp);
};
$formatSmaFrequency = static function ($value): string {
    $frequency = (float) $value;
    if ($frequency === 1.0) {
        return '1 PPS';
    }
    if ($frequency >= 1000000 && fmod($frequency, 1000000) === 0.0) {
        return ($frequency / 1000000) . ' MHz';
    }
    if ($frequency >= 1000 && fmod($frequency, 1000) === 0.0) {
        return ($frequency / 1000) . ' kHz';
    }
    return round($frequency, 6) . ' Hz';
};
$badgeClass = static function ($state): string {
    $state = strtoupper((string) $state);
    if (in_array($state, ['ACTIVE', 'UP', 'OK', 'LOCKED', 'LOCK_ACQ', 'MASTER', 'SLAVE', 'COMPLETED'], true)) {
        return 'text-bg-success';
    }
    if (in_array($state, ['INACTIVE', 'DOWN', 'FAILED', 'NOT_OK', 'FREERUN'], true)) {
        return 'text-bg-danger';
    }
    return 'text-bg-warning';
};

$system = $switchberry['system'] ?? [];
$hardware = $switchberry['hardware'] ?? [];
$ports = $switchberry['ports'] ?? [];
$dpll = $switchberry['dpll'] ?? [];
$timing = $switchberry['timing'] ?? [];
$ptp = $switchberry['ptp'] ?? [];
$gnss = $switchberry['gnss'] ?? [];
$gnssDevice = $gnss['device'] ?? [];
$gnssReceiverLive = $gnss['receiver_configuration'] ?? [];
$gnssRf = $gnssReceiverLive['rf'] ?? [];
$gnssLiveConstellations = array_keys(array_filter(
    $gnssReceiverLive['constellations'] ?? [],
    static fn($enabled): bool => $enabled === true
));
$gnssSatellites = $gnss['satellites'] ?? [];
$gnssDop = $gnss['dop'] ?? [];
$smaIo = $switchberry['sma_io'] ?? [];
$clockmatrix = $switchberry['clockmatrix'] ?? [];
$services = $switchberry['services'] ?? [];
$clockPlane = $switchberry['clock_plane'] ?? [];
$tc = $config['tc'] ?? [];
$bc = $config['bc'] ?? [];
$clockmatrixConfig = $config['clockmatrix'] ?? ['channels' => []];
$gnssReceiverConfig = $config['gnss_receiver'] ?? [];
$controllerReady = !empty($switchberry['ok']);
$allHardwareReady = !empty($hardware['checks']) && count(array_filter(
    $hardware['checks'],
    static fn($check): bool => !empty($check['present']) || (($check['path'] ?? '') === '/dev/ptp0')
)) === count($hardware['checks']);

$smaByName = [];
foreach (($config['smas'] ?? []) as $sma) {
    if (isset($sma['name'])) {
        $smaByName[$sma['name']] = $sma;
    }
}
$smaStatusByHardware = [];
foreach (($smaIo['connectors'] ?? []) as $connector) {
    if (isset($connector['hardware'])) {
        $smaStatusByHardware[$connector['hardware']] = $connector;
    }
}
$tcPortsByNumber = [];
foreach (($tc['ports'] ?? []) as $portConfig) {
    $tcPortsByNumber[(int) ($portConfig['port'] ?? 0)] = $portConfig;
}
$bcPortsByNumber = [];
foreach (($bc['ports'] ?? []) as $portConfig) {
    $bcPortsByNumber[(int) ($portConfig['port'] ?? 0)] = $portConfig;
}
$clockmatrixConfigByChannel = [];
foreach (($clockmatrixConfig['channels'] ?? []) as $channelConfig) {
    $clockmatrixConfigByChannel[(int) ($channelConfig['channel'] ?? 0)] = $channelConfig;
}
$clockmatrixLiveByChannel = [];
foreach (($clockmatrix['channels'] ?? $dpll) as $channelState) {
    $clockmatrixLiveByChannel[(int) ($channelState['channel'] ?? 0)] = $channelState;
}
$clockmatrixInputsByChannel = [5 => [], 6 => []];
foreach (($clockmatrix['inputs'] ?? []) as $input) {
    $inputChannel = (int) ($input['channel'] ?? 0);
    if (isset($clockmatrixInputsByChannel[$inputChannel])) {
        $clockmatrixInputsByChannel[$inputChannel][] = $input;
    }
}
$smaRows = [
    ['rear' => 'SMA1', 'hardware' => 'SMA4', 'capability' => 'External timing input', 'route' => 'DPLL IN4 · dedicated input', 'input_conflict' => false, 'output' => false],
    ['rear' => 'SMA2', 'hardware' => 'SMA3', 'capability' => 'Reference input or frequency output', 'route' => 'DPLL IN3 / Q9 · shares GNSS input mux', 'input_conflict' => !empty($config['gps']['present']) && !empty($config['gps']['role']), 'output' => true, 'phase_output' => false],
    ['rear' => 'SMA3', 'hardware' => 'SMA2', 'capability' => 'Reference input or synthesized output', 'route' => 'DPLL IN2 / Q10 · shares CM4 input mux', 'input_conflict' => !empty($config['cm4']['used_as_source']), 'output' => true, 'phase_output' => true],
    ['rear' => 'SMA4', 'hardware' => 'SMA1', 'capability' => 'Reference input or channel-6 output', 'route' => 'DPLL IN1 / Q11 · shares SyncE and CM4 PPS paths', 'input_conflict' => !empty($config['synce']['used_as_source']), 'output' => true, 'phase_output' => true, 'output_conflict' => ($config['ptp_role'] ?? 'NONE') === 'GM']
];
$smaProfile = static function (array $sma): string {
    $direction = strtoupper((string) ($sma['direction'] ?? 'UNUSED'));
    $frequency = (int) ($sma['frequency_hz'] ?? 0);
    $role = strtoupper((string) ($sma['role'] ?? ''));
    if ($direction === 'UNUSED') {
        return 'OFF';
    }
    $suffix = $direction === 'INPUT' ? '_INPUT' : '_OUTPUT';
    if ($direction === 'INPUT' && $frequency === 1 && $role === 'TIME_ONLY') {
        return 'PPS_INPUT';
    }
    if ($frequency === 10000000 && ($direction === 'OUTPUT' || $role === 'FREQ_ONLY')) {
        return '10MHZ' . $suffix;
    }
    if ($frequency === 25000000 && ($direction === 'OUTPUT' || $role === 'FREQ_ONLY')) {
        return '25MHZ' . $suffix;
    }
    if ($direction === 'OUTPUT' && $frequency === 1 && ($sma['name'] ?? '') !== 'SMA3') {
        return 'PPS_OUTPUT';
    }
    return 'CUSTOM' . $suffix;
};
?>

<div class="row">
  <div class="col-lg-12">
    <div class="card shadow">
      <div class="card-header page-card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div><i class="fas fa-clock me-2"></i><?php echo _('Switchberry'); ?></div>
          <div class="d-flex align-items-center gap-2">
            <span id="switchberry-last-refresh" class="small text-muted">
              <?php echo $escape($switchberry['generated_at'] ?? 'Not available'); ?>
            </span>
            <button type="button" id="switchberry-refresh" class="btn btn-sm btn-light" title="Refresh hardware status">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="card-body">
        <?php $messages->showMessages(); ?>

        <?php if (!$controllerReady): ?>
          <div class="alert alert-danger">
            <strong>Switchberry controller unavailable.</strong>
            <?php echo $escape($switchberry['error'] ?? 'The board could not be detected or queried.'); ?>
          </div>
        <?php endif; ?>

        <div class="nav-tabs-wrapper">
          <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link active" href="#switchberry-overview" data-bs-toggle="tab">Overview</a></li>
            <li class="nav-item"><a class="nav-link" href="#switchberry-clockmatrix" data-bs-toggle="tab">ClockMatrix</a></li>
            <li class="nav-item"><a class="nav-link" href="#switchberry-gnss" data-bs-toggle="tab">GNSS</a></li>
            <li class="nav-item"><a class="nav-link" href="#switchberry-ptp" data-bs-toggle="tab">PTP clock</a></li>
            <li class="nav-item"><a class="nav-link" href="#switchberry-timing" data-bs-toggle="tab">References &amp; network</a></li>
            <li class="nav-item"><a class="nav-link" href="#switchberry-sma" data-bs-toggle="tab">SMA I/O</a></li>
            <li class="nav-item"><a class="nav-link" href="#switchberry-ports" data-bs-toggle="tab">Switch ports</a></li>
            <li class="nav-item"><a class="nav-link" href="#switchberry-services" data-bs-toggle="tab">Services &amp; diagnostics</a></li>
          </ul>
        </div>

        <div class="tab-content pt-3">
          <div class="tab-pane active" id="switchberry-overview">
            <?php if (!empty($clockPlane['reboot_required'])): ?>
              <div class="alert alert-warning d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                  <strong>Reboot required.</strong>
                  The requested <?php echo $escape($clockPlane['mode'] ?? 'PTP'); ?> mode needs the
                  <?php echo $escape($clockPlane['desired'] ?? 'selected'); ?> clock plane; the board is currently using
                  <?php echo $escape($clockPlane['active'] ?? 'an unknown'); ?>.
                </div>
                <?php if (!RASPI_MONITOR_ENABLED): ?>
                  <form method="POST" action="switchberry" class="flex-shrink-0">
                    <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
                    <button type="submit" name="RebootSwitchberry" class="btn btn-warning switchberry-reboot">
                      <i class="fas fa-power-off me-2"></i>Reboot and activate
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <div class="row g-3">
              <div class="col-xl-4">
                <div class="card h-100 border-0 bg-body-tertiary">
                  <div class="card-body text-center">
                    <img src="/app/img/devices/switchberry.png" class="img-fluid mb-3" style="max-height: 230px" alt="Switchberry carrier board">
                    <h5 class="mb-1"><?php echo $escape($system['hostname'] ?? 'Switchberry'); ?></h5>
                    <div class="small text-muted"><?php echo $escape($system['board_model'] ?? 'Raspberry Pi Compute Module 4'); ?></div>
                  </div>
                </div>
              </div>

              <div class="col-xl-8">
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="card h-100">
                      <div class="card-body">
                        <h6><i class="fas fa-microchip me-2"></i>Hardware</h6>
                        <dl class="row small mb-0">
                          <dt class="col-5">Board</dt><dd class="col-7"><?php echo $escape($system['board_model'] ?? 'Unknown'); ?></dd>
                          <dt class="col-5">Kernel</dt><dd class="col-7 text-break"><?php echo $escape($system['kernel'] ?? 'Unknown'); ?></dd>
                          <dt class="col-5">OS</dt><dd class="col-7"><?php echo $escape($system['operating_system'] ?? 'Unknown'); ?></dd>
                          <dt class="col-5">Prerequisites</dt>
                          <dd class="col-7"><span class="badge <?php echo $allHardwareReady ? 'text-bg-success' : 'text-bg-warning'; ?>">
                            <?php echo $allHardwareReady ? 'Ready' : 'Check hardware'; ?>
                          </span></dd>
                        </dl>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="card h-100">
                      <div class="card-body">
                        <h6><i class="fas fa-satellite-dish me-2"></i>Timing role</h6>
                        <dl class="row small mb-0">
                          <dt class="col-6">PTP role</dt><dd class="col-6"><span class="badge text-bg-primary"><?php echo $escape($config['ptp_role']); ?></span></dd>
                          <dt class="col-6">Clock plane</dt><dd class="col-6"><span id="switchberry-clock-plane"><?php echo $escape($clockPlane['active'] ?? 'Unknown'); ?></span></dd>
                          <dt class="col-6">Transport</dt><dd class="col-6"><?php echo $escape(($config['ptp_role'] ?? '') === 'BC' ? ($bc['network_transport'] ?? 'L2') : (($config['ptp_role'] ?? '') === 'TC' ? ($tc['delay_mechanism'] ?? 'E2E') : ($config['ptp']['transport'] ?? 'UNICAST'))); ?></dd>
                          <dt class="col-6">GNSS</dt><dd class="col-6">
                            <span id="switchberry-gnss-overview" class="badge <?php echo !empty($gnss['fix']) ? 'text-bg-success' : 'text-bg-warning'; ?>">
                              <?php echo !empty($gnss['fix']) ? 'Fix' : $escape($gnss['status'] ?? 'No fix'); ?>
                            </span>
                          </dd>
                          <dt class="col-6">PTP port</dt><dd class="col-6" id="switchberry-ptp-state">
                            <?php echo $escape($ptp['portState'] ?? $ptp['status'] ?? 'Not running'); ?>
                          </dd>
                        </dl>
                      </div>
                    </div>
                  </div>

                  <?php foreach ($dpll as $channel): ?>
                    <div class="col-md-6">
                      <div class="card h-100">
                        <div class="card-body">
                          <div class="d-flex justify-content-between">
                            <h6>DPLL channel <?php echo $escape($channel['channel'] ?? '?'); ?></h6>
                            <span data-dpll="<?php echo $escape($channel['channel'] ?? ''); ?>" class="badge <?php echo $badgeClass($channel['state'] ?? ''); ?>">
                              <?php echo $escape($channel['state'] ?? 'Unknown'); ?>
                            </span>
                          </div>
                          <div class="small text-muted"><?php echo $escape($channel['domain'] ?? ''); ?></div>
                          <div class="small mt-2"><?php echo $escape($channel['combo'] ?? ''); ?></div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="row g-3 mt-1">
              <?php foreach ($timing as $item): ?>
                <div class="col-sm-6 col-xl-3">
                  <div class="card h-100">
                    <div class="card-body py-3">
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="text-uppercase small fw-semibold"><?php echo $escape($item['name'] ?? ''); ?></span>
                        <span data-timing="<?php echo $escape($item['name'] ?? ''); ?>" class="badge <?php echo $badgeClass($item['state'] ?? ''); ?>">
                          <?php echo $escape($item['state'] ?? 'Unknown'); ?>
                        </span>
                      </div>
                      <div class="small text-muted mt-2 text-break"><?php echo $escape($item['detail'] ?? ''); ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="tab-pane" id="switchberry-clockmatrix">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
              <div>
                <h5 class="mb-1">Renesas 8A34004 ClockMatrix</h5>
                <div class="text-muted">Live monitoring and guarded control of the frequency and time DPLL channels.</div>
              </div>
              <div class="text-lg-end">
                <span id="switchberry-clockmatrix-overall" class="badge <?php echo !empty($clockmatrix['applied']) ? 'text-bg-success' : 'text-bg-warning'; ?>">
                  <?php echo $escape($clockmatrix['status'] ?? 'Status unavailable'); ?>
                </span>
                <div id="switchberry-clockmatrix-applied-at" class="small text-muted mt-1">
                  <?php echo !empty($clockmatrix['last_applied_at']) ? 'Last applied ' . $escape($formatTimestamp($clockmatrix['last_applied_at'])) : 'Automatic board tuning is active'; ?>
                </div>
              </div>
            </div>

            <div class="border border-info-subtle rounded bg-body-tertiary p-3 mb-3 d-flex gap-3 align-items-start">
              <i class="fas fa-wave-square mt-1"></i>
              <div>
                <strong>Automatic is the safe default.</strong> The board chooses loop tuning and combo-bus direction from the active GNSS, SyncE, CM4 and SMA references.
                Enable a custom override only when you know the required loop dynamics. Operating-state buttons act immediately and do not change the saved tuning profile.
              </div>
            </div>

            <form method="POST" action="switchberry#switchberry-clockmatrix" id="switchberry-clockmatrix-form" class="needs-validation" novalidate>
              <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
              <div class="row g-3">
                <?php foreach ([5, 6] as $channel):
                    $live = $clockmatrixLiveByChannel[$channel] ?? [];
                    $setting = $clockmatrixConfigByChannel[$channel] ?? [];
                    $inputs = $clockmatrixInputsByChannel[$channel] ?? [];
                    $prefix = 'clockmatrix_' . $channel . '_';
                    $otherChannel = $channel === 5 ? 6 : 5;
                    $phaseNs = $live['phase_nanoseconds'] ?? null;
                ?>
                  <div class="col-xl-6">
                    <div class="card h-100 switchberry-clockmatrix-channel" data-clockmatrix-channel="<?php echo $channel; ?>">
                      <div class="card-header d-flex justify-content-between align-items-start gap-3">
                        <div>
                          <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill text-bg-primary">CH<?php echo $channel; ?></span>
                            <span class="fw-semibold fs-5"><?php echo $channel === 5 ? 'Frequency DPLL' : 'Time / 1PPS DPLL'; ?></span>
                          </div>
                          <div class="small text-white-50 mt-1"><?php echo $channel === 5 ? 'Frequency references and Q9' : 'Phase references, Q11 and CM4 PPS'; ?></div>
                        </div>
                        <span data-clockmatrix-state="<?php echo $channel; ?>" class="badge <?php echo $badgeClass($live['state'] ?? 'Unknown'); ?>"><?php echo $escape($live['state'] ?? 'Unknown'); ?></span>
                      </div>
                      <div class="card-body">
                        <div class="row g-2 mb-3">
                          <div class="col-6 col-md-3">
                            <div class="rounded bg-body-tertiary p-2 h-100">
                              <div class="small text-muted">Phase</div>
                              <div class="fw-semibold" data-clockmatrix-phase="<?php echo $channel; ?>"><?php echo $phaseNs === null ? 'Unavailable' : $escape(number_format((float) $phaseNs, 3) . ' ns'); ?></div>
                            </div>
                          </div>
                          <div class="col-6 col-md-3">
                            <div class="rounded bg-body-tertiary p-2 h-100">
                              <div class="small text-muted">Bandwidth</div>
                              <div class="fw-semibold" data-clockmatrix-bandwidth="<?php echo $channel; ?>"><?php echo $escape(($live['loop_bandwidth_value'] ?? '?') . ' ' . ($live['loop_bandwidth_unit'] ?? '')); ?></div>
                            </div>
                          </div>
                          <div class="col-6 col-md-3">
                            <div class="rounded bg-body-tertiary p-2 h-100">
                              <div class="small text-muted">Phase slope</div>
                              <div class="fw-semibold" data-clockmatrix-psl="<?php echo $channel; ?>"><?php echo $escape(($live['phase_slope_limit_ns_per_s'] ?? '?') . ' ns/s'); ?></div>
                            </div>
                          </div>
                          <div class="col-6 col-md-3">
                            <div class="rounded bg-body-tertiary p-2 h-100">
                              <div class="small text-muted">Damping</div>
                              <div class="fw-semibold" data-clockmatrix-damping="<?php echo $channel; ?>"><?php echo $escape($live['damping_factor'] ?? '?'); ?></div>
                            </div>
                          </div>
                        </div>

                        <div class="mb-3">
                          <div class="small text-uppercase fw-semibold text-muted mb-2">Assigned references</div>
                          <div class="d-flex flex-wrap gap-2">
                            <?php if (empty($inputs)): ?>
                              <span class="badge text-bg-secondary">No direct input</span>
                            <?php else: foreach ($inputs as $input): ?>
                              <span class="badge text-bg-light border text-dark" title="Logical input <?php echo $escape($input['logical_input'] ?? ''); ?> / physical input <?php echo $escape($input['physical_input'] ?? ''); ?>">
                                <?php echo $escape(($input['label'] ?? 'Reference') . ' · ' . ($input['signal'] ?? '') . ' · P' . ($input['priority'] ?? '?')); ?>
                              </span>
                            <?php endforeach; endif; ?>
                          </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-3">
                          <span><i class="fas fa-random me-2 text-muted"></i>Combo bus</span>
                          <strong data-clockmatrix-combo="<?php echo $channel; ?>"><?php echo $escape($live['combo'] ?? 'Unavailable'); ?></strong>
                        </div>

                        <div class="form-check form-switch mb-3">
                          <input class="form-check-input switchberry-clockmatrix-override" type="checkbox" id="clockmatrix-override-<?php echo $channel; ?>" name="<?php echo $prefix; ?>override_tuning" data-channel="<?php echo $channel; ?>"<?php echo $checked($setting['override_tuning'] ?? false); ?>>
                          <label class="form-check-label fw-semibold" for="clockmatrix-override-<?php echo $channel; ?>">Override automatic loop tuning</label>
                        </div>

                        <div class="row g-3 mb-3">
                          <div class="col-md-6">
                            <label class="form-label" for="clockmatrix-bandwidth-<?php echo $channel; ?>">Loop bandwidth</label>
                            <div class="input-group">
                              <input id="clockmatrix-bandwidth-<?php echo $channel; ?>" name="<?php echo $prefix; ?>loop_bandwidth_value" type="number" min="0" max="16383" required class="form-control switchberry-clockmatrix-tuning switchberry-clockmatrix-dirty" data-channel="<?php echo $channel; ?>" value="<?php echo $escape($setting['loop_bandwidth_value'] ?? ''); ?>">
                              <select name="<?php echo $prefix; ?>loop_bandwidth_unit" class="form-select switchberry-clockmatrix-tuning switchberry-clockmatrix-dirty" data-channel="<?php echo $channel; ?>" style="max-width: 95px">
                                <?php foreach (['uHz', 'mHz', 'Hz', 'kHz'] as $unit): ?><option value="<?php echo $unit; ?>"<?php echo $selected($setting['loop_bandwidth_unit'] ?? 'mHz', $unit); ?>><?php echo $unit; ?></option><?php endforeach; ?>
                              </select>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label" for="clockmatrix-psl-<?php echo $channel; ?>">Phase-slope limit</label>
                            <div class="input-group">
                              <input id="clockmatrix-psl-<?php echo $channel; ?>" name="<?php echo $prefix; ?>phase_slope_limit_ns_per_s" type="number" min="0" max="65535" required class="form-control switchberry-clockmatrix-tuning switchberry-clockmatrix-dirty" data-channel="<?php echo $channel; ?>" value="<?php echo $escape($setting['phase_slope_limit_ns_per_s'] ?? ''); ?>">
                              <span class="input-group-text">ns/s</span>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label" for="clockmatrix-damping-<?php echo $channel; ?>">Damping factor</label>
                            <select id="clockmatrix-damping-<?php echo $channel; ?>" name="<?php echo $prefix; ?>damping_factor" class="form-select switchberry-clockmatrix-tuning switchberry-clockmatrix-dirty" data-channel="<?php echo $channel; ?>">
                              <?php for ($factor = 0; $factor <= 7; $factor++): ?>
                                <option value="<?php echo $factor; ?>"<?php echo $selected($setting['damping_factor'] ?? 0, $factor); ?>><?php echo $factor; ?><?php echo $factor === 0 ? ' — most damped' : ($factor === 7 ? ' — least damped' : ''); ?></option>
                              <?php endfor; ?>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label" for="clockmatrix-combo-<?php echo $channel; ?>">Combo-bus policy</label>
                            <select id="clockmatrix-combo-<?php echo $channel; ?>" name="<?php echo $prefix; ?>combo_mode" class="form-select switchberry-clockmatrix-dirty switchberry-clockmatrix-combo-mode">
                              <option value="AUTOMATIC"<?php echo $selected($setting['combo_mode'] ?? 'AUTOMATIC', 'AUTOMATIC'); ?>>Automatic from references</option>
                              <option value="INDEPENDENT"<?php echo $selected($setting['combo_mode'] ?? 'AUTOMATIC', 'INDEPENDENT'); ?>>Independent</option>
                              <option value="FOLLOW_OTHER"<?php echo $selected($setting['combo_mode'] ?? 'AUTOMATIC', 'FOLLOW_OTHER'); ?>>Follow channel <?php echo $otherChannel; ?></option>
                            </select>
                          </div>
                        </div>

                        <?php if (!RASPI_MONITOR_ENABLED): ?>
                          <div class="border-top pt-3">
                            <div class="small text-uppercase fw-semibold text-muted mb-2">Immediate operating state</div>
                            <div class="d-flex flex-wrap gap-2">
                              <button type="submit" name="ClockmatrixAction" value="reacquire:<?php echo $channel; ?>" class="btn btn-sm btn-outline-primary switchberry-clockmatrix-action" data-channel="<?php echo $channel; ?>" data-action="reacquire"<?php echo $controllerReady ? '' : ' disabled'; ?>><i class="fas fa-sync-alt me-1"></i>Reacquire</button>
                              <button type="submit" name="ClockmatrixAction" value="normal:<?php echo $channel; ?>" class="btn btn-sm btn-outline-success switchberry-clockmatrix-action" data-channel="<?php echo $channel; ?>" data-action="normal"<?php echo $controllerReady ? '' : ' disabled'; ?>>Normal</button>
                              <button type="submit" name="ClockmatrixAction" value="holdover:<?php echo $channel; ?>" class="btn btn-sm btn-outline-warning switchberry-clockmatrix-action" data-channel="<?php echo $channel; ?>" data-action="holdover"<?php echo $controllerReady ? '' : ' disabled'; ?>>Holdover</button>
                              <button type="submit" name="ClockmatrixAction" value="freerun:<?php echo $channel; ?>" class="btn btn-sm btn-outline-danger switchberry-clockmatrix-action" data-channel="<?php echo $channel; ?>" data-action="freerun"<?php echo $controllerReady ? '' : ' disabled'; ?>>Freerun</button>
                            </div>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <?php if (!RASPI_MONITOR_ENABLED): ?>
                <div id="switchberry-clockmatrix-conflict" class="border border-danger rounded text-danger p-3 mt-3 d-none">
                  Channels 5 and 6 cannot follow each other simultaneously. Make at least one channel independent or automatic.
                </div>
                <button type="submit" name="SaveSwitchberryClockmatrix" class="btn btn-primary mt-3 switchberry-clockmatrix-submit" data-controller-ready="<?php echo $controllerReady ? '1' : '0'; ?>"<?php echo $controllerReady ? '' : ' disabled'; ?>><i class="fas fa-save me-2"></i>Save and apply ClockMatrix settings</button>
              <?php endif; ?>
            </form>
          </div>

          <div class="tab-pane" id="switchberry-gnss">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
              <div>
                <h5 class="mb-1">GNSS receiver &amp; satellite sky view</h5>
                <div class="text-muted">Navigation, timing, signal quality and safe control for the M.2 u-blox receiver.</div>
              </div>
              <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                <span id="switchberry-gnss-online" class="badge <?php echo !empty($gnss['online']) ? 'text-bg-success' : 'text-bg-danger'; ?>">
                  <?php echo !empty($gnss['online']) ? 'Receiver online' : 'Receiver offline'; ?>
                </span>
                <span id="switchberry-gnss-fix" class="badge <?php echo !empty($gnss['fix']) ? 'text-bg-success' : 'text-bg-warning'; ?>">
                  <?php echo $escape($gnss['mode_label'] ?? 'No fix'); ?>
                </span>
                <span id="switchberry-gnss-source" class="badge <?php echo !empty($gnss['configured']) ? 'text-bg-primary' : 'text-bg-secondary'; ?>">
                  <?php echo !empty($gnss['configured']) ? 'Timing source enabled' : 'Monitor only'; ?>
                </span>
              </div>
            </div>

            <div id="switchberry-gnss-guidance" class="border border-warning-subtle rounded bg-body-tertiary p-3 mb-3 d-flex gap-3 align-items-start">
              <i class="fas fa-satellite-dish mt-1"></i>
              <div><?php echo $escape($gnss['guidance'] ?? 'Waiting for receiver status.'); ?></div>
            </div>

            <div class="row g-3">
              <div class="col-xl-7">
                <div class="card h-100">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-globe me-2"></i>Satellite sky view</span>
                    <span class="small text-white-50"><span id="switchberry-gnss-used-header"><?php echo $escape($gnss['satellites_used'] ?? 0); ?></span> used / <span id="switchberry-gnss-visible-header"><?php echo $escape($gnss['satellites_visible'] ?? 0); ?></span> visible</span>
                  </div>
                  <div class="card-body d-flex flex-column align-items-center">
                    <svg id="switchberry-gnss-skyplot" viewBox="0 0 420 420" role="img" aria-label="Satellite azimuth and elevation sky view" style="width:100%;max-width:520px;max-height:520px">
                      <circle cx="210" cy="210" r="178" fill="var(--bs-body-bg)" stroke="var(--bs-border-color)" stroke-width="2"></circle>
                      <circle cx="210" cy="210" r="119" fill="none" stroke="var(--bs-border-color)" stroke-dasharray="5 5"></circle>
                      <circle cx="210" cy="210" r="59" fill="none" stroke="var(--bs-border-color)" stroke-dasharray="5 5"></circle>
                      <line x1="32" y1="210" x2="388" y2="210" stroke="var(--bs-border-color)"></line>
                      <line x1="210" y1="32" x2="210" y2="388" stroke="var(--bs-border-color)"></line>
                      <g fill="var(--bs-secondary-color)" font-size="15" font-weight="600" text-anchor="middle">
                        <text x="210" y="23">N</text><text x="402" y="215">E</text><text x="210" y="414">S</text><text x="18" y="215">W</text>
                        <text x="210" y="84">30°</text><text x="210" y="143">60°</text><text x="210" y="202">90°</text>
                      </g>
                      <g id="switchberry-gnss-sky-satellites"></g>
                      <text id="switchberry-gnss-sky-empty" x="210" y="238" text-anchor="middle" fill="var(--bs-secondary-color)" font-size="16"<?php echo empty($gnssSatellites) ? '' : ' class="d-none"'; ?>>Waiting for satellites</text>
                    </svg>
                    <div class="d-flex flex-wrap justify-content-center gap-3 small mt-2" id="switchberry-gnss-legend">
                      <?php foreach (['GPS' => '#0d6efd', 'Galileo' => '#198754', 'GLONASS' => '#dc3545', 'BeiDou' => '#fd7e14', 'SBAS' => '#6f42c1', 'QZSS' => '#20c997'] as $label => $color): ?>
                        <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:<?php echo $color; ?>"></span><?php echo $label; ?></span>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-xl-5">
                <div class="card mb-3">
                  <div class="card-header"><i class="fas fa-crosshairs me-2"></i>Fix quality</div>
                  <div class="card-body">
                    <div class="row g-2">
                      <?php
                      $gnssMetrics = [
                          ['Mode', 'switchberry-gnss-mode', $gnss['mode_label'] ?? 'Unknown'],
                          ['Satellites', 'switchberry-gnss-count', ($gnss['satellites_used'] ?? 0) . ' / ' . ($gnss['satellites_visible'] ?? 0)],
                          ['HDOP', 'switchberry-gnss-hdop', $gnssDop['hdop'] ?? '—'],
                          ['PDOP', 'switchberry-gnss-pdop', $gnssDop['pdop'] ?? '—'],
                          ['Latitude', 'switchberry-gnss-latitude', $gnss['latitude'] ?? '—'],
                          ['Longitude', 'switchberry-gnss-longitude', $gnss['longitude'] ?? '—'],
                          ['Altitude', 'switchberry-gnss-altitude', isset($gnss['altitude']) ? $gnss['altitude'] . ' m' : '—'],
                          ['Position error', 'switchberry-gnss-position-error', isset($gnss['position_error_m']) ? $gnss['position_error_m'] . ' m' : '—']
                      ];
                      foreach ($gnssMetrics as [$label, $id, $value]):
                      ?>
                        <div class="col-6">
                          <div class="rounded bg-body-tertiary p-2 h-100">
                            <div class="small text-muted"><?php echo $label; ?></div>
                            <div id="<?php echo $id; ?>" class="fw-semibold text-break"><?php echo $escape($value); ?></div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                    <div class="border-top mt-3 pt-3">
                      <div class="small text-muted">Receiver time</div>
                      <div id="switchberry-gnss-time" class="fw-semibold text-break"><?php echo $escape(($gnss['time'] ?? 'Unavailable') . (!empty($gnss['time_stale']) ? ' · not valid yet' : '')); ?></div>
                      <a id="switchberry-gnss-location-link" class="small<?php echo !empty($gnss['fix']) ? '' : ' d-none'; ?>"<?php if (!empty($gnss['fix'])): ?> href="https://www.openstreetmap.org/?mlat=<?php echo rawurlencode((string) $gnss['latitude']); ?>&amp;mlon=<?php echo rawurlencode((string) $gnss['longitude']); ?>#map=16/<?php echo rawurlencode((string) $gnss['latitude']); ?>/<?php echo rawurlencode((string) $gnss['longitude']); ?>"<?php endif; ?> target="_blank" rel="noopener">Open receiver location on map</a>
                    </div>
                  </div>
                </div>

                <div class="card">
                  <div class="card-header"><i class="fas fa-microchip me-2"></i>Receiver &amp; timing path</div>
                  <div class="card-body">
                    <dl class="row small mb-0">
                      <dt class="col-5">Model / firmware</dt><dd class="col-7 text-break" id="switchberry-gnss-model"><?php echo $escape($gnssDevice['model_firmware'] ?? $gnssDevice['subtype'] ?? 'Unknown'); ?></dd>
                      <dt class="col-5">Driver</dt><dd class="col-7" id="switchberry-gnss-driver"><?php echo $escape($gnssDevice['driver'] ?? 'Unknown'); ?></dd>
                      <dt class="col-5">Serial port</dt><dd class="col-7"><code id="switchberry-gnss-device"><?php echo $escape($gnssDevice['path'] ?? '/dev/ttyAMA5'); ?></code></dd>
                      <dt class="col-5">Baud</dt><dd class="col-7" id="switchberry-gnss-baud"><?php echo $escape($gnssDevice['baud'] ?? 'Unknown'); ?></dd>
                      <dt class="col-5">Live profile</dt><dd class="col-7" id="switchberry-gnss-live-profile"><?php echo $escape($gnssReceiverLive['dynamic_model'] ?? 'Unknown'); ?></dd>
                      <dt class="col-5">Rate / mask</dt><dd class="col-7" id="switchberry-gnss-live-rate"><?php echo isset($gnssReceiverLive['measurement_rate_ms']) ? $escape($gnssReceiverLive['measurement_rate_ms'] . ' ms · ' . ($gnssReceiverLive['minimum_elevation_deg'] ?? '—') . '° mask') : '—'; ?></dd>
                      <dt class="col-5">Constellations</dt><dd class="col-7" id="switchberry-gnss-live-constellations"><?php echo $escape($gnssLiveConstellations ? implode(', ', array_map('strtoupper', $gnssLiveConstellations)) : 'Unknown'); ?></dd>
                      <dt class="col-5">Antenna</dt><dd class="col-7"><span id="switchberry-gnss-antenna" class="badge <?php echo ($gnssRf['antenna_status'] ?? '') === 'OK' ? 'text-bg-success' : 'text-bg-warning'; ?>"><?php echo $escape(($gnssRf['antenna_status'] ?? 'Unknown') . (isset($gnssRf['antenna_power']) ? ' · power ' . $gnssRf['antenna_power'] : '')); ?></span></dd>
                      <dt class="col-5">RF monitor</dt><dd class="col-7" id="switchberry-gnss-rf"><?php echo isset($gnssRf['jamming_indicator']) ? $escape(($gnssRf['interference_level'] ?? 'Unknown') . ' interference · ' . $gnssRf['jamming_indicator'] . '/255') : 'Unknown'; ?></dd>
                      <dt class="col-5">PPS device</dt><dd class="col-7"><span id="switchberry-gnss-pps" class="badge <?php echo !empty($gnss['pps_present']) ? 'text-bg-success' : 'text-bg-danger'; ?>"><?php echo !empty($gnss['pps_present']) ? '/dev/pps0 ready' : 'Unavailable'; ?></span></dd>
                      <dt class="col-5">gpsd</dt><dd class="col-7"><span id="switchberry-gnss-gpsd" class="badge <?php echo !empty($gnss['gpsd_active']) ? 'text-bg-success' : 'text-bg-danger'; ?>"><?php echo !empty($gnss['gpsd_active']) ? 'Active' : 'Inactive'; ?></span></dd>
                      <dt class="col-5">PPS bridge</dt><dd class="col-7"><span id="switchberry-gnss-bridge" class="badge <?php echo !empty($gnss['bridge_active']) ? 'text-bg-success' : 'text-bg-danger'; ?>"><?php echo !empty($gnss['bridge_active']) ? 'Active' : 'Inactive'; ?></span></dd>
                      <dt class="col-5">Timepulse</dt><dd class="col-7" id="switchberry-gnss-timepulse"><?php echo !empty($gnssReceiverLive['timepulse']['enabled']) ? 'Enabled' : 'Unknown'; ?></dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>

            <div class="card mt-3">
              <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-signal me-2"></i>Satellite signals</span>
                <span class="small text-white-50">Used satellites are highlighted</span>
              </div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead><tr><th>System</th><th>Satellite</th><th>Elevation</th><th>Azimuth</th><th style="min-width:180px">Signal (dB-Hz)</th><th>Solution</th></tr></thead>
                  <tbody id="switchberry-gnss-satellite-table">
                    <?php if (empty($gnssSatellites)): ?>
                      <tr class="switchberry-gnss-no-satellites"><td colspan="6" class="text-center text-muted py-4">No satellites are currently reported by the receiver.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <script type="application/json" id="switchberry-gnss-initial-satellites"><?php echo json_encode($gnssSatellites, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

            <?php if (!RASPI_MONITOR_ENABLED): ?>
              <form method="POST" action="switchberry#switchberry-gnss" id="switchberry-gnss-form" class="mt-3">
                <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
                  <div>
                    <h5 class="mb-1">GNSS configuration</h5>
                    <div class="small text-muted">Reference routing and a persistent, boot-applied receiver profile.</div>
                  </div>
                  <div class="text-lg-end">
                    <span id="switchberry-gnss-applied" class="badge <?php echo !empty($gnss['applied']) ? 'text-bg-success' : 'text-bg-warning'; ?>"><?php echo !empty($gnss['applied']) ? 'Applied' : 'Pending apply'; ?></span>
                    <div id="switchberry-gnss-applied-at" class="small text-muted mt-1"><?php echo !empty($gnss['last_applied_at']) ? 'Last applied ' . $escape($formatTimestamp($gnss['last_applied_at'])) : 'No successful apply recorded'; ?></div>
                  </div>
                </div>

                <div class="row g-3">
                  <div class="col-lg-4">
                    <div class="card h-100">
                      <div class="card-header">ClockMatrix reference</div>
                      <div class="card-body">
                        <div class="form-check form-switch mb-3">
                          <input class="form-check-input switchberry-gnss-source-toggle" type="checkbox" id="gnss-use-as-source" name="gnss_use_as_source"<?php echo $checked($config['gps']['present'] ?? false); ?>>
                          <label class="form-check-label fw-semibold" for="gnss-use-as-source">Use GNSS as timing source</label>
                        </div>
                        <label class="form-label" for="gnss-source-role">Signal role</label>
                        <select id="gnss-source-role" name="gnss_source_role" class="form-select mb-3 switchberry-gnss-source-setting">
                          <?php foreach (['TIME_ONLY' => 'Time / 1PPS', 'FREQ_ONLY' => 'Frequency only', 'TIME_AND_FREQ' => 'Time and frequency'] as $value => $label): ?>
                            <option value="<?php echo $value; ?>"<?php echo $selected($config['gps']['role'] ?? 'TIME_ONLY', $value); ?>><?php echo $label; ?></option>
                          <?php endforeach; ?>
                        </select>
                        <label class="form-label" for="gnss-source-priority">DPLL priority</label>
                        <input id="gnss-source-priority" name="gnss_source_priority" type="number" min="0" max="15" class="form-control switchberry-gnss-source-setting" value="<?php echo $escape($config['gps']['priority'] ?? 0); ?>">
                        <div class="form-text">Lower values are preferred. The M.2 PPS path shares DPLL IN3 with rear SMA2.</div>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-8">
                    <div class="card h-100">
                      <div class="card-header">u-blox navigation profile</div>
                      <div class="card-body">
                        <div class="form-check form-switch mb-3">
                          <input class="form-check-input switchberry-gnss-manage-toggle" type="checkbox" id="gnss-manage-receiver" name="gnss_manage_receiver"<?php echo $checked($gnssReceiverConfig['manage_receiver'] ?? false); ?>>
                          <label class="form-check-label fw-semibold" for="gnss-manage-receiver">Apply this receiver profile at every timing-stack start</label>
                        </div>
                        <div class="row g-3">
                          <div class="col-md-4">
                            <label class="form-label" for="gnss-dynamic-model">Dynamic model</label>
                            <select id="gnss-dynamic-model" name="gnss_dynamic_model" class="form-select switchberry-gnss-managed-setting switchberry-gnss-dirty">
                              <?php foreach (['STATIONARY' => 'Stationary timing appliance', 'PORTABLE' => 'Portable', 'PEDESTRIAN' => 'Pedestrian', 'AUTOMOTIVE' => 'Automotive', 'SEA' => 'Marine', 'AIRBORNE_1G' => 'Airborne &lt;1g', 'AIRBORNE_2G' => 'Airborne &lt;2g', 'AIRBORNE_4G' => 'Airborne &lt;4g', 'BIKE' => 'Bicycle', 'WRIST' => 'Wrist'] as $value => $label): ?>
                                <option value="<?php echo $value; ?>"<?php echo $selected($gnssReceiverConfig['dynamic_model'] ?? 'STATIONARY', $value); ?>><?php echo $label; ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label" for="gnss-measurement-rate">Measurement interval</label>
                            <select id="gnss-measurement-rate" name="gnss_measurement_rate_ms" class="form-select switchberry-gnss-managed-setting switchberry-gnss-dirty">
                              <?php foreach ([1000 => '1 Hz · recommended', 500 => '2 Hz', 200 => '5 Hz', 100 => '10 Hz'] as $value => $label): ?>
                                <option value="<?php echo $value; ?>"<?php echo $selected($gnssReceiverConfig['measurement_rate_ms'] ?? 1000, $value); ?>><?php echo $label; ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label" for="gnss-min-elevation">Minimum elevation</label>
                            <div class="input-group">
                              <input id="gnss-min-elevation" name="gnss_minimum_elevation_deg" type="number" min="0" max="90" class="form-control switchberry-gnss-managed-setting switchberry-gnss-dirty" value="<?php echo $escape($gnssReceiverConfig['minimum_elevation_deg'] ?? 5); ?>">
                              <span class="input-group-text">degrees</span>
                            </div>
                          </div>
                        </div>
                        <div class="small text-uppercase fw-semibold text-muted mt-3 mb-2">Enabled constellations</div>
                        <div class="d-flex flex-wrap gap-3">
                          <?php foreach (['gps' => 'GPS', 'galileo' => 'Galileo', 'glonass' => 'GLONASS', 'beidou' => 'BeiDou', 'sbas' => 'SBAS', 'qzss' => 'QZSS'] as $key => $label): ?>
                            <div class="form-check form-switch">
                              <input class="form-check-input switchberry-gnss-managed-setting switchberry-gnss-constellation switchberry-gnss-dirty" type="checkbox" id="gnss-constellation-<?php echo $key; ?>" name="gnss_constellation_<?php echo $key; ?>" data-primary="<?php echo in_array($key, ['gps', 'galileo', 'glonass', 'beidou'], true) ? '1' : '0'; ?>"<?php echo $checked($gnssReceiverConfig['constellations'][$key] ?? true); ?>>
                              <label class="form-check-label" for="gnss-constellation-<?php echo $key; ?>"><?php echo $label; ?></label>
                            </div>
                          <?php endforeach; ?>
                        </div>
                        <div id="switchberry-gnss-constellation-error" class="small text-danger mt-2 d-none">Keep at least one primary constellation enabled.</div>
                        <div id="switchberry-gnss-profile-error" class="small text-danger mt-2 d-none">Enable GNSS as a timing source before applying a managed receiver profile.</div>
                        <div class="form-text mt-3">The profile is applied to receiver RAM after board timing initialization and at every boot, avoiding repeated flash writes. The 1PPS output remains controlled by the proven Switchberry timing path.</div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="card mt-3">
                  <div class="card-header">Receiver recovery</div>
                  <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div class="small text-muted">Restart the data path without resetting acquisition, or request a u-blox acquisition restart. Cold start discards orbital data and can take several minutes outdoors.</div>
                    <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                      <button type="submit" name="GnssAction" value="restart" class="btn btn-sm btn-outline-primary switchberry-gnss-action" data-action="restart"<?php echo $controllerReady ? '' : ' disabled'; ?>><i class="fas fa-redo me-1"></i>Restart gpsd</button>
                      <button type="submit" name="GnssAction" value="hotstart" class="btn btn-sm btn-outline-success switchberry-gnss-action" data-action="hotstart"<?php echo $controllerReady ? '' : ' disabled'; ?>>Hot start</button>
                      <button type="submit" name="GnssAction" value="warmstart" class="btn btn-sm btn-outline-warning switchberry-gnss-action" data-action="warmstart"<?php echo $controllerReady ? '' : ' disabled'; ?>>Warm start</button>
                      <button type="submit" name="GnssAction" value="coldstart" class="btn btn-sm btn-outline-danger switchberry-gnss-action" data-action="coldstart"<?php echo $controllerReady ? '' : ' disabled'; ?>>Cold start</button>
                    </div>
                  </div>
                </div>

                <button type="submit" name="SaveSwitchberryGnss" class="btn btn-primary mt-3 switchberry-gnss-submit" data-controller-ready="<?php echo $controllerReady ? '1' : '0'; ?>"<?php echo $controllerReady ? '' : ' disabled'; ?>><i class="fas fa-save me-2"></i>Save and apply GNSS configuration</button>
              </form>
            <?php endif; ?>
          </div>

          <div class="tab-pane" id="switchberry-ptp">
            <form method="POST" action="switchberry" class="needs-validation" novalidate>
              <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
              <div class="card mb-3">
                <div class="card-header">Clock architecture</div>
                <div class="card-body">
                  <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                      <label class="form-label" for="ptp-role">Operating mode</label>
                      <select id="ptp-role" name="ptp_role" class="form-select">
                        <option value="NONE"<?php echo $selected($config['ptp_role'], 'NONE'); ?>>Disabled</option>
                        <option value="TC"<?php echo $selected($config['ptp_role'], 'TC'); ?>>Transparent clock (hardware)</option>
                        <option value="BC"<?php echo $selected($config['ptp_role'], 'BC'); ?>>Boundary clock (multi-port)</option>
                        <option value="GM"<?php echo $selected($config['ptp_role'], 'GM'); ?>>Grandmaster</option>
                        <option value="CLIENT"<?php echo $selected($config['ptp_role'], 'CLIENT'); ?>>Client / ordinary clock</option>
                      </select>
                    </div>
                    <div class="col-lg-8">
                      <div class="small text-muted switchberry-role-help" data-role="TC">The KSZ9567 corrects residence time in hardware at line rate. No Linux forwarding path is involved.</div>
                      <div class="small text-muted switchberry-role-help" data-role="BC">Each front port is a hardware-timestamped linuxptp port sharing the KSZ9567 PHC. Best-master selection terminates and regenerates PTP.</div>
                      <div class="small text-muted switchberry-role-help" data-role="GM">The CM4 and KSZ9567 advertise time from the selected GNSS, PPS, or timing input.</div>
                      <div class="small text-muted switchberry-role-help" data-role="CLIENT">The CM4 disciplines the KSZ9567 PHC from an upstream PTP master.</div>
                      <div class="small text-muted switchberry-role-help" data-role="NONE">PTP processing is disabled while signal routing and diagnostics remain available.</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="switchberry-ordinary-settings">
                <div class="card mb-3">
                  <div class="card-header">Ordinary clock / grandmaster profile</div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label class="form-label" for="ptp-transport">Message delivery</label>
                        <select id="ptp-transport" name="ptp_transport" class="form-select">
                          <?php foreach (['UNICAST', 'MULTICAST'] as $option): ?>
                            <option value="<?php echo $option; ?>"<?php echo $selected($config['ptp']['transport'], $option); ?>><?php echo $option; ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-5 switchberry-client-settings">
                        <label class="form-label" for="ptp-master-ip">Unicast master IP</label>
                        <input id="ptp-master-ip" name="ptp_master_ip" class="form-control ip_address" value="<?php echo $escape($config['ptp']['master_ip']); ?>">
                      </div>
                      <div class="col-md-3 d-flex align-items-center">
                        <div class="form-check mt-3">
                          <input class="form-check-input" type="checkbox" id="time-traceable" name="ptp_time_traceable"<?php echo $checked($config['ptp']['time_traceable']); ?>>
                          <label class="form-check-label" for="time-traceable">Time traceable</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="switchberry-tc-settings">
                <div class="alert alert-info">Transparent-clock settings are programmed directly into the KSZ9567 and read back for verification.</div>
                <div class="card mb-3">
                  <div class="card-header">Hardware transparent-clock engine</div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-3">
                        <label class="form-label" for="tc-delay">Delay mechanism</label>
                        <select id="tc-delay" name="tc_delay_mechanism" class="form-select">
                          <?php foreach (['E2E', 'P2P'] as $option): ?>
                            <option value="<?php echo $option; ?>"<?php echo $selected($tc['delay_mechanism'], $option); ?>><?php echo $option; ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label" for="tc-domain">PTP domain</label>
                        <input id="tc-domain" name="tc_domain" type="number" min="0" max="255" class="form-control" value="<?php echo $escape($tc['domain']); ?>">
                      </div>
                      <div class="col-md-6">
                        <div class="row g-2">
                          <?php
                          $tcSwitches = [
                              'tc_one_step' => ['One-step operation', 'one_step'],
                              'tc_ieee_802_1as' => ['IEEE 802.1AS / gPTP', 'ieee_802_1as'],
                              'tc_unicast' => ['Unicast detection', 'unicast'],
                              'tc_alternate_master' => ['Alternate-master detection', 'alternate_master'],
                              'tc_priority_queue' => ['PTP priority queue', 'priority_queue'],
                              'tc_domain_check' => ['Enforce domain match', 'domain_check']
                          ];
                          foreach ($tcSwitches as $name => [$label, $key]):
                          ?>
                            <div class="col-md-6">
                              <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="<?php echo $name; ?>" name="<?php echo $name; ?>"<?php echo $checked($tc[$key]); ?>>
                                <label class="form-check-label" for="<?php echo $name; ?>"><?php echo $label; ?></label>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                    <hr>
                    <div class="d-flex flex-wrap gap-4">
                      <?php foreach (['l2' => 'Layer 2', 'ipv4' => 'UDP/IPv4', 'ipv6' => 'UDP/IPv6'] as $key => $label): ?>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="tc-detect-<?php echo $key; ?>" name="tc_detect_<?php echo $key; ?>"<?php echo $checked($tc['detect_' . $key]); ?>>
                          <label class="form-check-label" for="tc-detect-<?php echo $key; ?>">Detect <?php echo $label; ?></label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
                <div class="card mb-3">
                  <div class="card-header">Per-port hardware calibration (nanoseconds)</div>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead><tr><th>Front port</th><th>Ingress latency</th><th>Egress latency</th><th>Path asymmetry</th></tr></thead>
                      <tbody>
                        <?php for ($port = 1; $port <= 5; $port++): $portConfig = $tcPortsByNumber[$port] ?? []; ?>
                          <tr>
                            <td class="fw-semibold">Port <?php echo $port; ?></td>
                            <td><input name="tc_port_<?php echo $port; ?>_rx_latency_ns" type="number" min="0" max="65535" class="form-control form-control-sm" value="<?php echo $escape($portConfig['rx_latency_ns'] ?? 425); ?>"></td>
                            <td><input name="tc_port_<?php echo $port; ?>_tx_latency_ns" type="number" min="0" max="65535" class="form-control form-control-sm" value="<?php echo $escape($portConfig['tx_latency_ns'] ?? 243); ?>"></td>
                            <td><input name="tc_port_<?php echo $port; ?>_asymmetry_ns" type="number" min="-32767" max="32767" class="form-control form-control-sm" value="<?php echo $escape($portConfig['asymmetry_ns'] ?? 0); ?>"></td>
                          </tr>
                        <?php endfor; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <div class="switchberry-bc-settings">
                <div class="alert alert-info">Boundary-clock mode activates the KSZ9567 DSA/PTP driver and five hardware-timestamped interfaces. The upstream KSZ9567 driver provides hardware one-step P2P transmission; E2E/two-step and the E2E-based G.8275 profiles are shown as unavailable instead of silently falling back to software timestamps. Switching to or from this mode requires one reboot.</div>
                <div class="card mb-3">
                  <div class="card-header">Boundary-clock dataset and profile</div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-4 col-xl-2">
                        <label class="form-label" for="bc-profile">Profile</label>
                        <select id="bc-profile" name="bc_profile" class="form-select">
                          <option value="IEEE1588"<?php echo $selected($bc['profile'], 'IEEE1588'); ?>>IEEE 1588 default</option>
                          <option value="G8275_1" disabled>ITU-T G.8275.1 (requires E2E)</option>
                          <option value="G8275_2" disabled>ITU-T G.8275.2 (requires E2E)</option>
                        </select>
                      </div>
                      <div class="col-md-4 col-xl-2">
                        <label class="form-label" for="bc-transport">Network transport</label>
                        <select id="bc-transport" name="bc_network_transport" class="form-select">
                          <?php foreach (['L2', 'UDPV4', 'UDPV6'] as $option): ?>
                            <option value="<?php echo $option; ?>"<?php echo $selected($bc['network_transport'], $option); ?>><?php echo $option; ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-4 col-xl-2">
                        <label class="form-label" for="bc-delay">Delay mechanism</label>
                        <select id="bc-delay" name="bc_delay_mechanism" class="form-select">
                          <option value="P2P" selected>P2P (hardware)</option>
                          <option value="E2E" disabled>E2E (driver unavailable)</option>
                          <option value="AUTO" disabled>Auto (driver unavailable)</option>
                        </select>
                      </div>
                      <?php foreach ([
                          'bc_domain' => ['Domain', 0, 255, 'domain'],
                          'bc_priority1' => ['Priority 1', 0, 255, 'priority1'],
                          'bc_priority2' => ['Priority 2', 0, 255, 'priority2'],
                          'bc_clock_class' => ['Clock class', 0, 255, 'clock_class'],
                          'bc_local_priority' => ['Local priority', 1, 255, 'local_priority'],
                          'bc_announce_receipt_timeout' => ['Announce timeout', 2, 255, 'announce_receipt_timeout'],
                          'bc_tx_timestamp_timeout' => ['TX timeout (ms)', 10, 10000, 'tx_timestamp_timeout'],
                          'bc_summary_interval' => ['Summary log₂(s)', -7, 7, 'summary_interval']
                      ] as $name => [$label, $min, $max, $key]): ?>
                        <div class="col-md-3 col-xl-2">
                          <label class="form-label" for="<?php echo $name; ?>"><?php echo $label; ?></label>
                          <input id="<?php echo $name; ?>" name="<?php echo $name; ?>" type="number" min="<?php echo $min; ?>" max="<?php echo $max; ?>" class="form-control" value="<?php echo $escape($bc[$key]); ?>">
                        </div>
                      <?php endforeach; ?>
                      <div class="col-md-3 col-xl-2">
                        <label class="form-label" for="bc-servo">Clock servo</label>
                        <select id="bc-servo" name="bc_clock_servo" class="form-select">
                          <?php foreach (['PI', 'LINREG'] as $option): ?>
                            <option value="<?php echo $option; ?>"<?php echo $selected($bc['clock_servo'], $option); ?>><?php echo $option; ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-3 col-xl-2 d-flex align-items-center">
                        <div class="form-check form-switch mt-3">
                          <input class="form-check-input" type="checkbox" id="bc-two-step" name="bc_two_step" disabled>
                          <label class="form-check-label" for="bc-two-step">Two-step clock (driver unavailable)</label>
                        </div>
                      </div>
                      <div class="col-md-6 col-xl-4 d-flex align-items-center">
                        <div class="form-check form-switch mt-3">
                          <input class="form-check-input" type="checkbox" id="bc-system-clock" name="bc_sync_system_clock"<?php echo $checked($bc['sync_system_clock']); ?>>
                          <label class="form-check-label" for="bc-system-clock">Discipline the CM4 system clock (slew only)</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="card mb-3">
                  <div class="card-header">Boundary-clock ports</div>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead>
                        <tr><th>Port</th><th>Role policy</th><th>Local priority</th><th>Announce log₂</th><th>Sync log₂</th><th>Delay req log₂</th><th>Asymmetry ns</th><th>Ingress ns</th><th>Egress ns</th><th>Port IP</th><th>CIDR</th><th>Gateway</th><th>Unicast master</th></tr>
                      </thead>
                      <tbody>
                        <?php for ($port = 1; $port <= 5; $port++): $portConfig = $bcPortsByNumber[$port] ?? []; $prefix = 'bc_port_' . $port . '_'; ?>
                          <tr>
                            <td class="fw-semibold text-nowrap">Port <?php echo $port; ?><div class="small text-muted">lan<?php echo $port; ?></div></td>
                            <td style="min-width: 135px"><select name="<?php echo $prefix; ?>mode" class="form-select form-select-sm switchberry-bc-port-mode">
                              <?php foreach (['AUTO', 'UPSTREAM', 'DOWNSTREAM', 'DISABLED'] as $option): ?>
                                <option value="<?php echo $option; ?>"<?php echo $selected($portConfig['mode'] ?? 'AUTO', $option); ?>><?php echo $option; ?></option>
                              <?php endforeach; ?>
                            </select></td>
                            <td><input name="<?php echo $prefix; ?>local_priority" type="number" min="1" max="255" class="form-control form-control-sm" value="<?php echo $escape($portConfig['local_priority'] ?? 128); ?>"></td>
                            <td><input name="<?php echo $prefix; ?>log_announce_interval" type="number" min="-7" max="7" class="form-control form-control-sm" value="<?php echo $escape($portConfig['log_announce_interval'] ?? 1); ?>"></td>
                            <td><input name="<?php echo $prefix; ?>log_sync_interval" type="number" min="-7" max="7" class="form-control form-control-sm" value="<?php echo $escape($portConfig['log_sync_interval'] ?? 0); ?>"></td>
                            <td><input name="<?php echo $prefix; ?>log_min_delay_req_interval" type="number" min="-7" max="7" class="form-control form-control-sm" value="<?php echo $escape($portConfig['log_min_delay_req_interval'] ?? 0); ?>"></td>
                            <td><input name="<?php echo $prefix; ?>delay_asymmetry_ns" type="number" min="-1000000000" max="1000000000" class="form-control form-control-sm" value="<?php echo $escape($portConfig['delay_asymmetry_ns'] ?? 0); ?>"></td>
                            <td><input name="<?php echo $prefix; ?>ingress_latency_ns" type="number" min="-1000000000" max="1000000000" class="form-control form-control-sm" value="<?php echo $escape($portConfig['ingress_latency_ns'] ?? 0); ?>"></td>
                            <td><input name="<?php echo $prefix; ?>egress_latency_ns" type="number" min="-1000000000" max="1000000000" class="form-control form-control-sm" value="<?php echo $escape($portConfig['egress_latency_ns'] ?? 0); ?>"></td>
                            <td style="min-width: 150px"><input name="<?php echo $prefix; ?>ip_address" class="form-control form-control-sm ip_address" value="<?php echo $escape($portConfig['ip_address'] ?? ''); ?>" placeholder="For UDP"></td>
                            <td><input name="<?php echo $prefix; ?>cidr" type="number" min="0" max="128" class="form-control form-control-sm" value="<?php echo $escape($portConfig['cidr'] ?? ''); ?>"></td>
                            <td style="min-width: 150px"><input name="<?php echo $prefix; ?>gateway" class="form-control form-control-sm ip_address" value="<?php echo $escape($portConfig['gateway'] ?? ''); ?>" placeholder="Optional next hop"></td>
                            <td style="min-width: 150px"><input name="<?php echo $prefix; ?>unicast_master" class="form-control form-control-sm ip_address" value="<?php echo $escape($portConfig['unicast_master'] ?? ''); ?>" placeholder="Optional IPv4/IPv6"></td>
                          </tr>
                        <?php endfor; ?>
                      </tbody>
                    </table>
                  </div>
                  <div class="card-footer small text-muted">AUTO uses BMCA. UPSTREAM forces client-only, DOWNSTREAM forces server-only, and DISABLED keeps the interface down. UDP transports require an IP address and CIDR on every enabled port.</div>
                </div>
              </div>

              <?php if (!RASPI_MONITOR_ENABLED): ?>
                <button type="submit" name="SaveSwitchberryPtp" class="btn btn-primary"<?php echo $controllerReady ? '' : ' disabled'; ?>>
                  <i class="fas fa-save me-2"></i>Save PTP clock settings
                </button>
              <?php endif; ?>
            </form>
          </div>

          <div class="tab-pane" id="switchberry-timing">
            <form method="POST" action="switchberry" class="needs-validation" novalidate>
              <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
              <div class="row g-3">
                <div class="col-lg-12">
                  <div class="card h-100">
                    <div class="card-header">Ethernet management on eth0</div>
                    <div class="card-body">
                      <div class="row g-3">
                        <div class="col-md-4">
                          <label class="form-label" for="network-mode">Mode</label>
                          <select id="network-mode" name="network_mode" class="form-select">
                            <option value="DHCP"<?php echo $selected($config['network']['mode'], 'DHCP'); ?>>DHCP</option>
                            <option value="STATIC"<?php echo $selected($config['network']['mode'], 'STATIC'); ?>>Static</option>
                          </select>
                        </div>
                        <div class="col-md-5">
                          <label class="form-label" for="network-ip">IP address</label>
                          <input id="network-ip" name="network_ip_address" class="form-control ip_address switchberry-static" value="<?php echo $escape($config['network']['ip_address']); ?>">
                        </div>
                        <div class="col-md-3">
                          <label class="form-label" for="network-cidr">CIDR</label>
                          <input id="network-cidr" name="network_cidr" type="number" min="1" max="32" class="form-control switchberry-static" value="<?php echo $escape($config['network']['cidr']); ?>">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="network-gateway">Gateway</label>
                          <input id="network-gateway" name="network_gateway" class="form-control ip_address switchberry-static" value="<?php echo $escape($config['network']['gateway']); ?>">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="network-dns">DNS servers</label>
                          <input id="network-dns" name="network_dns" class="form-control switchberry-static" value="<?php echo $escape($config['network']['dns']); ?>" placeholder="1.1.1.1, 8.8.8.8">
                        </div>
                      </div>
                      <div class="form-text mt-2">This controls the KSZ9567/CM4 Ethernet path. Wi-Fi remains managed by RaspAP separately.</div>
                    </div>
                  </div>
                </div>

                <?php
                $sources = [
                    ['key' => 'gps', 'label' => 'M.2 GNSS', 'enabled' => 'present'],
                    ['key' => 'cm4', 'label' => 'CM4 PPS', 'enabled' => 'used_as_source']
                ];
                foreach ($sources as $source):
                    $sourceConfig = $config[$source['key']];
                    $prefix = $source['key'];
                ?>
                  <div class="col-lg-4">
                    <div class="card h-100">
                      <div class="card-header"><?php echo $escape($source['label']); ?> source</div>
                      <div class="card-body">
                        <div class="form-check form-switch mb-3">
                          <input class="form-check-input switchberry-source-toggle" type="checkbox" id="<?php echo $prefix; ?>-enabled" name="<?php echo $prefix === 'gps' ? 'gps_present' : 'cm4_used_as_source'; ?>" data-source="<?php echo $prefix; ?>"<?php echo $checked($sourceConfig[$source['enabled']]); ?>>
                          <label class="form-check-label" for="<?php echo $prefix; ?>-enabled">Use as timing reference</label>
                        </div>
                        <label class="form-label" for="<?php echo $prefix; ?>-role">Role</label>
                        <select id="<?php echo $prefix; ?>-role" name="<?php echo $prefix; ?>_role" class="form-select mb-3 switchberry-source-<?php echo $prefix; ?>">
                          <option value="">None</option>
                          <?php foreach (['TIME_ONLY', 'FREQ_ONLY', 'TIME_AND_FREQ'] as $option): ?>
                            <option value="<?php echo $option; ?>"<?php echo $selected($sourceConfig['role'], $option); ?>><?php echo $option; ?></option>
                          <?php endforeach; ?>
                        </select>
                        <label class="form-label" for="<?php echo $prefix; ?>-priority">Priority</label>
                        <input id="<?php echo $prefix; ?>-priority" name="<?php echo $prefix; ?>_priority" type="number" min="0" max="15" class="form-control switchberry-source-<?php echo $prefix; ?>" value="<?php echo $escape($sourceConfig['priority']); ?>">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>

                <div class="col-lg-4">
                  <div class="card h-100">
                    <div class="card-header">SyncE source</div>
                    <div class="card-body">
                      <div class="form-check form-switch mb-3">
                        <input class="form-check-input switchberry-source-toggle" type="checkbox" id="synce-enabled" name="synce_used_as_source" data-source="synce"<?php echo $checked($config['synce']['used_as_source']); ?>>
                        <label class="form-check-label" for="synce-enabled">Recover frequency from Ethernet</label>
                      </div>
                      <div class="row g-3">
                        <div class="col-6">
                          <label class="form-label" for="synce-port">Front port</label>
                          <select id="synce-port" name="synce_recover_port" class="form-select switchberry-source-synce">
                            <option value="">Select</option>
                            <?php for ($port = 1; $port <= 5; $port++): ?>
                              <option value="<?php echo $port; ?>"<?php echo $selected($config['synce']['recover_port'], $port); ?>>Port <?php echo $port; ?></option>
                            <?php endfor; ?>
                          </select>
                        </div>
                        <div class="col-6">
                          <label class="form-label" for="synce-priority">Priority</label>
                          <input id="synce-priority" name="synce_priority" type="number" min="0" max="15" class="form-control switchberry-source-synce" value="<?php echo $escape($config['synce']['priority']); ?>">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <?php if (!RASPI_MONITOR_ENABLED): ?>
                <div class="mt-3">
                  <button type="submit" name="SaveSwitchberryTiming" class="btn btn-outline-primary"<?php echo $controllerReady ? '' : ' disabled'; ?>>
                    <i class="fas fa-save me-2"></i>Save and apply timing configuration
                  </button>
                </div>
              <?php endif; ?>
            </form>
          </div>

          <div class="tab-pane" id="switchberry-sma">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
              <div>
                <h5 class="mb-1">Rear-panel timing connectors</h5>
                <div class="text-muted">Choose a familiar signal preset, then adjust reference priority only when needed.</div>
              </div>
              <div class="text-lg-end">
                <span id="switchberry-sma-overall" class="badge <?php echo !empty($smaIo['applied']) ? 'text-bg-success' : 'text-bg-warning'; ?>">
                  <?php echo $escape($smaIo['status'] ?? 'Status unavailable'); ?>
                </span>
                <div id="switchberry-sma-applied-at" class="small text-muted mt-1">
                  <?php echo !empty($smaIo['last_applied_at']) ? 'Last applied ' . $escape($formatTimestamp($smaIo['last_applied_at'])) : 'No successful apply recorded'; ?>
                </div>
              </div>
            </div>

            <div class="border border-info-subtle rounded bg-body-tertiary p-3 d-flex gap-3 align-items-start mb-3">
              <i class="fas fa-project-diagram mt-1"></i>
              <div>
                Configure the connectors in their physical rear-panel order below. The smaller <strong>ECAD</strong> label is shown only for board-level troubleshooting.
                Input and output choices are constrained to the signal paths actually wired on Switchberry V6.
              </div>
            </div>
            <div id="switchberry-sma-conflicts" class="border border-danger rounded bg-danger-subtle text-danger-emphasis p-3 mb-3 d-none" role="alert">
              <strong>Resolve the highlighted routing conflict before applying.</strong>
              Two sources cannot drive the same hardware mux path.
            </div>

            <form method="POST" action="switchberry#switchberry-sma" class="needs-validation" id="switchberry-sma-form" novalidate>
              <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
              <div class="row g-3">
                <?php foreach ($smaRows as $row):
                    $number = (int) substr($row['hardware'], 3);
                    $sma = $smaByName[$row['hardware']] ?? [];
                    $status = $smaStatusByHardware[$row['hardware']] ?? [];
                    $prefix = 'sma_' . $number . '_';
                    $profile = $smaProfile($sma);
                    $direction = strtoupper((string) ($sma['direction'] ?? 'UNUSED'));
                    $isApplied = !empty($status['applied']);
                ?>
                  <div class="col-md-6 col-xxl-3">
                    <div class="card h-100 switchberry-sma-card" data-sma-row data-sma-hardware="<?php echo $escape($row['hardware']); ?>" data-sma-rear="<?php echo $escape($row['rear']); ?>" data-input-conflict="<?php echo !empty($row['input_conflict']) ? '1' : '0'; ?>" data-output-conflict="<?php echo !empty($row['output_conflict']) ? '1' : '0'; ?>">
                      <div class="card-header d-flex justify-content-between align-items-start gap-2">
                        <div class="d-flex align-items-center gap-2">
                          <span class="rounded-circle border border-2 d-inline-flex align-items-center justify-content-center" style="width: 2.35rem; height: 2.35rem">
                            <i class="fas fa-bullseye"></i>
                          </span>
                          <div>
                            <div class="fw-semibold fs-5"><?php echo $escape($row['rear']); ?></div>
                            <div class="small text-white-50">ECAD <?php echo $escape($row['hardware']); ?></div>
                          </div>
                        </div>
                        <span class="badge switchberry-sma-applied <?php echo $isApplied ? 'text-bg-success' : 'text-bg-warning'; ?>"><?php echo $isApplied ? 'Applied' : 'Pending'; ?></span>
                      </div>
                      <div class="card-body d-flex flex-column">
                        <div class="small fw-semibold mb-1"><?php echo $escape($row['capability']); ?></div>
                        <div class="small text-muted mb-3"><?php echo $escape($row['route']); ?></div>

                        <label class="form-label" for="sma-profile-<?php echo $number; ?>">Signal preset</label>
                        <select id="sma-profile-<?php echo $number; ?>" class="form-select switchberry-sma-profile mb-3">
                          <option value="OFF"<?php echo $selected($profile, 'OFF'); ?>>Off / isolated</option>
                          <optgroup label="Use as input">
                            <option value="PPS_INPUT"<?php echo $selected($profile, 'PPS_INPUT'); ?>>1 PPS time reference</option>
                            <option value="10MHZ_INPUT"<?php echo $selected($profile, '10MHZ_INPUT'); ?>>10 MHz frequency reference</option>
                            <option value="25MHZ_INPUT"<?php echo $selected($profile, '25MHZ_INPUT'); ?>>25 MHz frequency reference</option>
                            <option value="CUSTOM_INPUT"<?php echo $selected($profile, 'CUSTOM_INPUT'); ?>>Custom input</option>
                          </optgroup>
                          <?php if (!empty($row['output'])): ?>
                            <optgroup label="Use as output">
                              <?php if (!empty($row['phase_output'])): ?><option value="PPS_OUTPUT"<?php echo $selected($profile, 'PPS_OUTPUT'); ?>>1 PPS output</option><?php endif; ?>
                              <option value="10MHZ_OUTPUT"<?php echo $selected($profile, '10MHZ_OUTPUT'); ?>>10 MHz output</option>
                              <option value="25MHZ_OUTPUT"<?php echo $selected($profile, '25MHZ_OUTPUT'); ?>>25 MHz output</option>
                              <option value="CUSTOM_OUTPUT"<?php echo $selected($profile, 'CUSTOM_OUTPUT'); ?>>Custom output</option>
                            </optgroup>
                          <?php endif; ?>
                        </select>

                        <input type="hidden" name="<?php echo $prefix; ?>direction" class="switchberry-sma-direction" value="<?php echo $escape($direction); ?>">

                        <div class="switchberry-sma-signal-fields">
                          <label class="form-label" for="sma-frequency-<?php echo $number; ?>">Nominal frequency</label>
                          <div class="input-group mb-3">
                            <input id="sma-frequency-<?php echo $number; ?>" name="<?php echo $prefix; ?>frequency_hz" type="number" min="1" max="250000000" required class="form-control switchberry-sma-frequency" value="<?php echo $escape($sma['frequency_hz'] ?? null); ?>">
                            <span class="input-group-text">Hz</span>
                          </div>
                        </div>

                        <div class="switchberry-sma-input-fields">
                          <label class="form-label" for="sma-role-<?php echo $number; ?>">Reference type</label>
                          <select id="sma-role-<?php echo $number; ?>" name="<?php echo $prefix; ?>role" class="form-select switchberry-sma-role mb-3">
                            <option value="TIME_ONLY"<?php echo $selected($sma['role'] ?? null, 'TIME_ONLY'); ?>>Time / phase</option>
                            <option value="FREQ_ONLY"<?php echo $selected($sma['role'] ?? null, 'FREQ_ONLY'); ?>>Frequency</option>
                            <option value="TIME_AND_FREQ"<?php echo $selected($sma['role'] ?? null, 'TIME_AND_FREQ'); ?>>Time and frequency</option>
                          </select>
                          <label class="form-label" for="sma-priority-<?php echo $number; ?>">Reference priority</label>
                          <select id="sma-priority-<?php echo $number; ?>" name="<?php echo $prefix; ?>priority" class="form-select switchberry-sma-priority mb-3">
                            <?php for ($priority = 0; $priority <= 15; $priority++): ?>
                              <option value="<?php echo $priority; ?>"<?php echo $selected($sma['priority'] ?? 0, $priority); ?>><?php echo $priority; ?><?php echo $priority === 0 ? ' — highest' : ''; ?></option>
                            <?php endfor; ?>
                          </select>
                        </div>

                        <div class="border border-danger rounded bg-danger-subtle text-danger-emphasis p-2 small mb-3 switchberry-sma-card-conflict d-none"></div>
                        <div class="rounded bg-body-tertiary p-2 small mt-auto">
                          <div class="d-flex justify-content-between gap-2"><span class="text-muted">Configured</span><strong class="switchberry-sma-summary"><?php echo $escape(($status['direction'] ?? $direction) . (($status['signal'] ?? '') ? ' · ' . $status['signal'] : '')); ?></strong></div>
                          <div class="d-flex justify-content-between gap-2 mt-1"><span class="text-muted">Route</span><span class="text-end switchberry-sma-route"><?php echo $escape($status['route'] ?? $row['route']); ?></span></div>
                          <div class="d-flex justify-content-between gap-2 mt-1 switchberry-sma-lock-row<?php echo empty($status['lock_state']) ? ' d-none' : ''; ?>"><span class="text-muted">DPLL</span><span class="switchberry-sma-lock"><?php echo $escape(isset($status['channel']) ? 'CH' . $status['channel'] . ' · ' . ($status['lock_state'] ?? 'Unknown') : ''); ?></span></div>
                          <div class="d-flex justify-content-between gap-2 mt-1 switchberry-sma-actual-row<?php echo !isset($status['actual_frequency_hz']) || $status['actual_frequency_hz'] === null ? ' d-none' : ''; ?>"><span class="text-muted">Realized</span><span class="text-end switchberry-sma-actual"><?php echo isset($status['actual_frequency_hz']) && $status['actual_frequency_hz'] !== null ? $escape($formatSmaFrequency($status['actual_frequency_hz']) . ' · ' . round((float) ($status['accuracy_ppm'] ?? 0), 3) . ' ppm') : ''; ?></span></div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="form-text mt-3 mb-3">Q9 on rear SMA2 uses an integer divider; the controller reports its realized frequency and error. Q10 and Q11 use fractional dividers.</div>
              <?php if (!RASPI_MONITOR_ENABLED): ?>
                <button type="submit" name="SaveSwitchberrySma" class="btn btn-primary switchberry-sma-submit" data-controller-ready="<?php echo $controllerReady ? '1' : '0'; ?>"<?php echo $controllerReady ? '' : ' disabled'; ?>>
                  <i class="fas fa-bolt me-2"></i>Save and apply SMA configuration
                </button>
              <?php endif; ?>
            </form>
          </div>

          <div class="tab-pane" id="switchberry-ports">
            <div class="row g-3">
              <?php foreach ($ports as $port):
                  $number = (int) ($port['port'] ?? 0);
                  $state = $port['state'] ?? 'Unknown';
              ?>
                <div class="col-sm-6 col-xl">
                  <div class="card h-100">
                    <div class="card-body text-center">
                      <i class="fas fa-ethernet fa-2x text-muted mb-2"></i>
                      <h5>Port <?php echo $number; ?></h5>
                      <span data-port="<?php echo $number; ?>" class="badge <?php echo $badgeClass($state); ?> mb-3"><?php echo $escape($state); ?></span>
                      <div class="small text-muted mb-3"><?php echo $escape($port['interface'] ?? ('KSZ port ' . $number)); ?> · <?php echo $escape($port['speed'] ?? 'Unknown speed'); ?></div>
                      <?php if (!RASPI_MONITOR_ENABLED): ?>
                        <form method="POST" action="switchberry" class="d-flex justify-content-center gap-2">
                          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
                          <button type="submit" name="SwitchberryPortAction" value="enable:<?php echo $number; ?>" class="btn btn-sm btn-outline-success"<?php echo $controllerReady ? '' : ' disabled'; ?>>Enable</button>
                          <button type="submit" name="SwitchberryPortAction" value="disable:<?php echo $number; ?>" class="btn btn-sm btn-outline-danger switchberry-port-disable" data-port="<?php echo $number; ?>"<?php echo $controllerReady ? '' : ' disabled'; ?>>Disable</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="form-text mt-3">Physical ports 1–5 map directly to the five front-panel RJ45 connectors on the KSZ9567.</div>
          </div>

          <div class="tab-pane" id="switchberry-services">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <h5 class="mb-1">Switchberry service chain</h5>
                <div class="small text-muted">Hardware checks, network, DPLL routing, switch initialization, PTP, PHC, and NTP.</div>
              </div>
              <?php if (!RASPI_MONITOR_ENABLED): ?>
                <form method="POST" action="switchberry">
                  <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
                  <button type="submit" name="RestartSwitchberryStack" class="btn btn-outline-warning"<?php echo $controllerReady && empty($clockPlane['reboot_required']) ? '' : ' disabled'; ?>>
                    <i class="fas fa-redo me-2"></i>Restart timing stack
                  </button>
                </form>
              <?php endif; ?>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead><tr><th>Service</th><th>Purpose</th><th>State</th><th>Boot</th><th>Recent log</th></tr></thead>
                <tbody>
                  <?php foreach ($services as $service): ?>
                    <tr>
                      <td><code><?php echo $escape($service['name'] ?? ''); ?></code></td>
                      <td><?php echo $escape($service['description'] ?? ''); ?></td>
                      <td><span data-service="<?php echo $escape($service['name'] ?? ''); ?>" class="badge <?php echo $badgeClass($service['display_state'] ?? $service['active'] ?? ''); ?>"><?php echo $escape($service['display_state'] ?? $service['active'] ?? 'unknown'); ?></span></td>
                      <td><?php echo $escape($service['enabled'] ?? 'unknown'); ?></td>
                      <td style="max-width: 420px">
                        <?php if (!empty($service['logs'])): ?>
                          <details><summary class="small">Show last 5 lines</summary><pre class="small mt-2 text-wrap"><?php echo $escape($service['logs']); ?></pre></details>
                        <?php else: ?>
                          <span class="text-muted">No log entries</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <h5 class="mt-4">Hardware prerequisites</h5>
            <div class="row g-2">
              <?php foreach (($hardware['checks'] ?? []) as $check): ?>
                <div class="col-md-6 col-xl-4">
                  <div class="border rounded p-2 h-100">
                    <i class="fas <?php echo !empty($check['present']) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?> me-2"></i>
                    <span><?php echo $escape($check['description'] ?? ''); ?></span>
                    <div class="small text-muted"><code><?php echo $escape($check['path'] ?? ''); ?></code></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-lg-6">
                <h6>PCI / M.2 devices</h6>
                <pre class="small border rounded p-2 text-wrap bg-body-tertiary"><?php echo $escape($hardware['pci'] ?? 'lspci unavailable'); ?></pre>
              </div>
              <div class="col-lg-6">
                <h6>USB devices</h6>
                <pre class="small border rounded p-2 text-wrap bg-body-tertiary"><?php echo $escape($hardware['usb'] ?? 'lsusb unavailable'); ?></pre>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card-footer">
        Hardware-aware management for the KSZ9567 switch, Renesas 8A34004 DPLL, GNSS, SyncE, PTP, PHC, and four routed SMA ports.
      </div>
    </div>
  </div>
</div>
