function setStatusBadge(element, state) {
    if (!element) {
        return;
    }
    const normalized = String(state || 'Unknown').toUpperCase();
    const good = ['ACTIVE', 'UP', 'OK', 'LOCKED', 'LOCK_ACQ', 'MASTER', 'SLAVE', 'COMPLETED'];
    const bad = ['INACTIVE', 'DOWN', 'FAILED', 'NOT_OK', 'FREERUN'];
    element.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning');
    element.classList.add(good.includes(normalized)
        ? 'text-bg-success'
        : bad.includes(normalized) ? 'text-bg-danger' : 'text-bg-warning');
    element.textContent = state || 'Unknown';
}

const smaPreset = {
    OFF: { direction: 'UNUSED', frequency: '', role: '' },
    PPS_INPUT: { direction: 'INPUT', frequency: '1', role: 'TIME_ONLY' },
    '10MHZ_INPUT': { direction: 'INPUT', frequency: '10000000', role: 'FREQ_ONLY' },
    '25MHZ_INPUT': { direction: 'INPUT', frequency: '25000000', role: 'FREQ_ONLY' },
    CUSTOM_INPUT: { direction: 'INPUT' },
    PPS_OUTPUT: { direction: 'OUTPUT', frequency: '1', role: '' },
    '10MHZ_OUTPUT': { direction: 'OUTPUT', frequency: '10000000', role: '' },
    '25MHZ_OUTPUT': { direction: 'OUTPUT', frequency: '25000000', role: '' },
    CUSTOM_OUTPUT: { direction: 'OUTPUT', role: '' }
};

const smaRoutes = {
    SMA1: { input: 'SMA4 to DPLL IN1 (CLK0P)', output: 'DPLL Q11 / channel 6 to SMA4' },
    SMA2: { input: 'SMA3 to DPLL IN2 (CLK0N)', output: 'DPLL Q10 to SMA3' },
    SMA3: { input: 'SMA2 to DPLL IN3 (CLK1P)', output: 'DPLL Q9 / channel 5 to SMA2' },
    SMA4: { input: 'SMA1 to DPLL IN4 (CLK1N)', output: '' }
};

const gnssColors = {
    GPS: '#0d6efd',
    Galileo: '#198754',
    GLONASS: '#dc3545',
    BeiDou: '#fd7e14',
    SBAS: '#6f42c1',
    QZSS: '#20c997',
    NavIC: '#d63384',
    GNSS: '#6c757d'
};

function setText(selector, value, fallback = '—') {
    const element = document.querySelector(selector);
    if (element) {
        element.textContent = value == null || value === '' ? fallback : value;
    }
}

function setBinaryBadge(element, active, activeLabel, inactiveLabel) {
    if (!element) {
        return;
    }
    element.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning', 'text-bg-primary', 'text-bg-secondary');
    element.classList.add(active ? 'text-bg-success' : 'text-bg-danger');
    element.textContent = active ? activeLabel : inactiveLabel;
}

function humanFrequency(value) {
    const frequency = Number(value);
    if (!Number.isFinite(frequency) || frequency <= 0) {
        return 'frequency required';
    }
    if (frequency === 1) {
        return '1 PPS';
    }
    if (frequency % 1000000 === 0) {
        return `${frequency / 1000000} MHz`;
    }
    if (frequency % 1000 === 0) {
        return `${frequency / 1000} kHz`;
    }
    return `${frequency} Hz`;
}

function updateSmaFields() {
    let hasConflict = false;
    document.querySelectorAll('[data-sma-row]').forEach((row) => {
        const profile = row.querySelector('.switchberry-sma-profile');
        const selectedPreset = smaPreset[profile?.value] || smaPreset.OFF;
        const direction = row.querySelector('.switchberry-sma-direction');
        const role = row.querySelector('.switchberry-sma-role');
        const priority = row.querySelector('.switchberry-sma-priority');
        const frequency = row.querySelector('.switchberry-sma-frequency');
        const custom = profile?.value.startsWith('CUSTOM_');

        if (direction) {
            direction.value = selectedPreset.direction;
        }
        if (frequency && Object.prototype.hasOwnProperty.call(selectedPreset, 'frequency')) {
            frequency.value = selectedPreset.frequency;
        }
        if (role && Object.prototype.hasOwnProperty.call(selectedPreset, 'role') && selectedPreset.role) {
            role.value = selectedPreset.role;
        }
        if (custom && frequency && !frequency.value) {
            frequency.value = selectedPreset.direction === 'INPUT' ? '1' : '10000000';
        }
        if (custom && selectedPreset.direction === 'INPUT' && role && !role.value) {
            role.value = 'TIME_ONLY';
        }

        const active = selectedPreset.direction !== 'UNUSED';
        const input = selectedPreset.direction === 'INPUT';
        const output = selectedPreset.direction === 'OUTPUT';
        row.querySelector('.switchberry-sma-signal-fields')?.classList.toggle('d-none', !active);
        row.querySelector('.switchberry-sma-input-fields')?.classList.toggle('d-none', !input);
        if (frequency) {
            frequency.disabled = !active;
            frequency.readOnly = active && !custom;
        }
        if (role) {
            role.disabled = !input;
        }
        if (priority) {
            priority.disabled = !input;
        }

        const conflict = row.querySelector('.switchberry-sma-card-conflict');
        let conflictMessage = '';
        if (input && row.dataset.inputConflict === '1') {
            const source = { SMA1: 'SyncE', SMA2: 'CM4 PPS', SMA3: 'M.2 GNSS PPS' }[row.dataset.smaHardware];
            conflictMessage = `${source} already uses this DPLL input path. Disable that source first.`;
        } else if (output && row.dataset.outputConflict === '1') {
            conflictMessage = 'Grandmaster mode routes Q11 to the CM4. Change the PTP role before using this output.';
        }
        conflict?.classList.toggle('d-none', !conflictMessage);
        if (conflict) {
            conflict.textContent = conflictMessage;
        }
        hasConflict = hasConflict || Boolean(conflictMessage);

        row.classList.remove('border-primary', 'border-success', 'border-secondary');
        row.classList.add(input ? 'border-primary' : output ? 'border-success' : 'border-secondary');
        const summary = row.querySelector('.switchberry-sma-summary');
        if (summary) {
            summary.textContent = active
                ? `${input ? 'Input' : 'Output'} · ${humanFrequency(frequency?.value)}`
                : 'Off';
        }
        const route = row.querySelector('.switchberry-sma-route');
        if (route) {
            route.textContent = active
                ? smaRoutes[row.dataset.smaHardware]?.[input ? 'input' : 'output'] || 'Configured route'
                : 'DPLL input disabled';
        }
    });

    document.querySelector('#switchberry-sma-conflicts')?.classList.toggle('d-none', !hasConflict);
    const submit = document.querySelector('.switchberry-sma-submit');
    if (submit) {
        submit.disabled = submit.dataset.controllerReady !== '1' || hasConflict;
    }
}

function markSmaDirty(row) {
    const form = document.querySelector('#switchberry-sma-form');
    if (form) {
        form.dataset.dirty = '1';
    }
    const overall = document.querySelector('#switchberry-sma-overall');
    if (overall) {
        overall.classList.remove('text-bg-success');
        overall.classList.add('text-bg-warning');
        overall.textContent = 'Unsaved changes';
    }
    const appliedAt = document.querySelector('#switchberry-sma-applied-at');
    if (appliedAt) {
        appliedAt.textContent = 'Save to apply these connector changes';
    }
    const badge = row?.querySelector('.switchberry-sma-applied');
    if (badge) {
        badge.classList.remove('text-bg-success');
        badge.classList.add('text-bg-warning');
        badge.textContent = 'Unsaved';
    }
    if (row) {
        row.dataset.dirty = '1';
    }
    row?.querySelector('.switchberry-sma-actual-row')?.classList.add('d-none');
}

function markClockmatrixDirty() {
    const form = document.querySelector('#switchberry-clockmatrix-form');
    if (form) {
        form.dataset.dirty = '1';
    }
    const overall = document.querySelector('#switchberry-clockmatrix-overall');
    if (overall) {
        overall.classList.remove('text-bg-success');
        overall.classList.add('text-bg-warning');
        overall.textContent = 'Unsaved changes';
    }
    const appliedAt = document.querySelector('#switchberry-clockmatrix-applied-at');
    if (appliedAt) {
        appliedAt.textContent = 'Save to apply this ClockMatrix profile';
    }
}

function updateClockmatrixFields() {
    document.querySelectorAll('.switchberry-clockmatrix-override').forEach((toggle) => {
        const channel = toggle.dataset.channel;
        document.querySelectorAll(`.switchberry-clockmatrix-tuning[data-channel="${channel}"]`).forEach((field) => {
            field.disabled = !toggle.checked;
            field.closest('.col-md-6')?.classList.toggle('opacity-50', !toggle.checked);
        });
    });

    const comboModes = Array.from(document.querySelectorAll('.switchberry-clockmatrix-combo-mode'))
        .map((field) => field.value);
    const comboConflict = comboModes.length === 2 && comboModes.every((mode) => mode === 'FOLLOW_OTHER');
    document.querySelector('#switchberry-clockmatrix-conflict')?.classList.toggle('d-none', !comboConflict);
    const submit = document.querySelector('.switchberry-clockmatrix-submit');
    if (submit) {
        submit.disabled = submit.dataset.controllerReady !== '1' || comboConflict;
    }
}

function markGnssDirty() {
    const form = document.querySelector('#switchberry-gnss-form');
    if (form) {
        form.dataset.dirty = '1';
    }
    const badge = document.querySelector('#switchberry-gnss-applied');
    if (badge) {
        badge.classList.remove('text-bg-success');
        badge.classList.add('text-bg-warning');
        badge.textContent = 'Unsaved changes';
    }
    setText('#switchberry-gnss-applied-at', 'Save to apply this GNSS profile');
}

function updateGnssFields() {
    const sourceEnabled = document.querySelector('.switchberry-gnss-source-toggle')?.checked ?? false;
    const managed = document.querySelector('.switchberry-gnss-manage-toggle')?.checked ?? false;
    document.querySelectorAll('.switchberry-gnss-source-setting').forEach((field) => {
        field.disabled = !sourceEnabled;
    });
    document.querySelectorAll('.switchberry-gnss-managed-setting').forEach((field) => {
        field.disabled = !managed;
    });
    const primaryConstellations = Array.from(document.querySelectorAll('.switchberry-gnss-constellation[data-primary="1"]'));
    const noPrimary = managed && primaryConstellations.length > 0 && !primaryConstellations.some((field) => field.checked);
    const managedWithoutSource = managed && !sourceEnabled;
    document.querySelector('#switchberry-gnss-constellation-error')?.classList.toggle('d-none', !noPrimary);
    document.querySelector('#switchberry-gnss-profile-error')?.classList.toggle('d-none', !managedWithoutSource);
    const submit = document.querySelector('.switchberry-gnss-submit');
    if (submit) {
        submit.disabled = submit.dataset.controllerReady !== '1' || noPrimary || managedWithoutSource;
    }
}

function renderGnssSky(satellites) {
    const namespace = 'http://www.w3.org/2000/svg';
    const group = document.querySelector('#switchberry-gnss-sky-satellites');
    const empty = document.querySelector('#switchberry-gnss-sky-empty');
    if (!group) {
        return;
    }
    group.replaceChildren();
    let plotted = 0;
    (satellites || []).forEach((satellite) => {
        if (satellite.elevation == null || satellite.azimuth == null) {
            return;
        }
        const elevation = Number(satellite.elevation);
        const azimuth = Number(satellite.azimuth);
        if (!Number.isFinite(elevation) || !Number.isFinite(azimuth) || elevation < 0) {
            return;
        }
        const radius = Math.max(0, Math.min(178, ((90 - elevation) / 90) * 178));
        const radians = (azimuth * Math.PI) / 180;
        const x = 210 + Math.sin(radians) * radius;
        const y = 210 - Math.cos(radians) * radius;
        const marker = document.createElementNS(namespace, 'circle');
        marker.setAttribute('cx', x.toFixed(2));
        marker.setAttribute('cy', y.toFixed(2));
        marker.setAttribute('r', satellite.used ? '16' : '13');
        marker.setAttribute('fill', gnssColors[satellite.constellation] || gnssColors.GNSS);
        marker.setAttribute('fill-opacity', satellite.used ? '1' : '0.55');
        marker.setAttribute('stroke', satellite.used ? 'var(--bs-body-color)' : 'var(--bs-border-color)');
        marker.setAttribute('stroke-width', satellite.used ? '3' : '1');
        const title = document.createElementNS(namespace, 'title');
        title.textContent = `${satellite.constellation} ${satellite.svid ?? satellite.prn ?? '?'} · ${satellite.signal_dbhz ?? '—'} dB-Hz · ${satellite.used ? 'used' : 'visible'}`;
        marker.appendChild(title);
        const label = document.createElementNS(namespace, 'text');
        label.setAttribute('x', x.toFixed(2));
        label.setAttribute('y', (y + 4.5).toFixed(2));
        label.setAttribute('fill', '#fff');
        label.setAttribute('font-size', '11');
        label.setAttribute('font-weight', '700');
        label.setAttribute('text-anchor', 'middle');
        label.textContent = satellite.svid ?? satellite.prn ?? '?';
        group.append(marker, label);
        plotted += 1;
    });
    if (empty) {
        empty.textContent = satellites?.length ? 'Waiting for satellite geometry' : 'Waiting for satellites';
        empty.classList.toggle('d-none', plotted > 0);
    }
}

function renderGnssSatelliteTable(satellites) {
    const body = document.querySelector('#switchberry-gnss-satellite-table');
    if (!body) {
        return;
    }
    body.replaceChildren();
    if (!satellites?.length) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 6;
        cell.className = 'text-center text-muted py-4';
        cell.textContent = 'No satellites are currently reported by the receiver.';
        row.appendChild(cell);
        body.appendChild(row);
        return;
    }
    satellites.forEach((satellite) => {
        const row = document.createElement('tr');
        if (satellite.used) {
            row.classList.add('table-success');
        }
        const systemCell = document.createElement('td');
        const systemBadge = document.createElement('span');
        systemBadge.className = 'badge text-white';
        systemBadge.style.backgroundColor = gnssColors[satellite.constellation] || gnssColors.GNSS;
        systemBadge.textContent = satellite.constellation || 'GNSS';
        systemCell.appendChild(systemBadge);
        const satelliteCell = document.createElement('td');
        satelliteCell.textContent = satellite.svid ?? satellite.prn ?? '—';
        const elevationCell = document.createElement('td');
        elevationCell.textContent = satellite.elevation == null ? '—' : `${satellite.elevation}°`;
        const azimuthCell = document.createElement('td');
        azimuthCell.textContent = satellite.azimuth == null ? '—' : `${satellite.azimuth}°`;
        const signalCell = document.createElement('td');
        const signal = Number(satellite.signal_dbhz);
        const signalLabel = document.createElement('div');
        signalLabel.className = 'small mb-1';
        signalLabel.textContent = Number.isFinite(signal) ? `${signal.toFixed(1)} dB-Hz` : 'Unavailable';
        const progress = document.createElement('div');
        progress.className = 'progress';
        progress.style.height = '6px';
        const progressBar = document.createElement('div');
        const percent = Number.isFinite(signal) ? Math.max(0, Math.min(100, (signal / 60) * 100)) : 0;
        progressBar.className = `progress-bar ${percent >= 58 ? 'bg-success' : percent >= 35 ? 'bg-warning' : 'bg-danger'}`;
        progressBar.style.width = `${percent}%`;
        progress.appendChild(progressBar);
        signalCell.append(signalLabel, progress);
        const usedCell = document.createElement('td');
        const usedBadge = document.createElement('span');
        usedBadge.className = `badge ${satellite.used ? 'text-bg-success' : 'text-bg-secondary'}`;
        usedBadge.textContent = satellite.used ? 'Used' : 'Visible';
        usedCell.appendChild(usedBadge);
        row.append(systemCell, satelliteCell, elevationCell, azimuthCell, signalCell, usedCell);
        body.appendChild(row);
    });
}

function updateDynamicFields() {
    const staticMode = document.querySelector('#network-mode')?.value === 'STATIC';
    document.querySelectorAll('.switchberry-static').forEach((field) => {
        field.disabled = !staticMode;
    });

    document.querySelectorAll('.switchberry-source-toggle').forEach((toggle) => {
        const source = toggle.dataset.source;
        document.querySelectorAll(`.switchberry-source-${source}`).forEach((field) => {
            field.disabled = !toggle.checked;
        });
    });

    updateSmaFields();
    updateClockmatrixFields();
    updateGnssFields();

    const ptpRole = document.querySelector('#ptp-role')?.value || 'NONE';
    const ordinary = ptpRole === 'GM' || ptpRole === 'CLIENT';
    document.querySelectorAll('.switchberry-role-help').forEach((element) => {
        element.classList.toggle('d-none', element.dataset.role !== ptpRole);
    });
    document.querySelectorAll('.switchberry-ordinary-settings').forEach((element) => {
        element.classList.toggle('d-none', !ordinary);
    });
    document.querySelectorAll('.switchberry-client-settings').forEach((element) => {
        const unicast = document.querySelector('#ptp-transport')?.value === 'UNICAST';
        element.classList.toggle('d-none', ptpRole !== 'CLIENT' || !unicast);
    });
    document.querySelectorAll('.switchberry-tc-settings').forEach((element) => {
        element.classList.toggle('d-none', ptpRole !== 'TC');
    });
    document.querySelectorAll('.switchberry-bc-settings').forEach((element) => {
        element.classList.toggle('d-none', ptpRole !== 'BC');
    });

    const bcDelay = document.querySelector('#bc-delay');
    const bcTwoStep = document.querySelector('#bc-two-step');
    if (bcDelay) {
        bcDelay.value = 'P2P';
    }
    if (bcTwoStep) {
        bcTwoStep.checked = false;
    }
}

function updateStatus(payload) {
    document.querySelector('#switchberry-last-refresh').textContent = payload.generated_at || 'Unavailable';

    (payload.ports || []).forEach((port) => {
        setStatusBadge(document.querySelector(`[data-port="${port.port}"]`), port.state);
    });
    (payload.dpll || []).forEach((channel) => {
        setStatusBadge(document.querySelector(`[data-dpll="${channel.channel}"]`), channel.state);
    });
    (payload.timing || []).forEach((item) => {
        setStatusBadge(document.querySelector(`[data-timing="${item.name}"]`), item.state);
    });
    (payload.services || []).forEach((service) => {
        setStatusBadge(
            document.querySelector(`[data-service="${service.name}"]`),
            service.display_state || service.active
        );
    });

    const ptp = payload.ptp || {};
    document.querySelector('#switchberry-ptp-state').textContent = ptp.portState || ptp.status || 'Not running';
    const clockPlane = document.querySelector('#switchberry-clock-plane');
    if (clockPlane) {
        clockPlane.textContent = payload.clock_plane?.active || 'Unknown';
    }
    const gnss = payload.gnss || {};
    const gnssBadge = document.querySelector('#switchberry-gnss-overview');
    if (gnssBadge) {
        gnssBadge.classList.remove('text-bg-success', 'text-bg-warning');
        gnssBadge.classList.add(gnss.fix ? 'text-bg-success' : 'text-bg-warning');
        gnssBadge.textContent = gnss.fix ? 'Fix' : (gnss.status || 'No fix');
    }
    setBinaryBadge(document.querySelector('#switchberry-gnss-online'), Boolean(gnss.online), 'Receiver online', 'Receiver offline');
    const gnssFixBadge = document.querySelector('#switchberry-gnss-fix');
    if (gnssFixBadge) {
        gnssFixBadge.classList.remove('text-bg-success', 'text-bg-warning', 'text-bg-danger');
        gnssFixBadge.classList.add(!gnss.online ? 'text-bg-danger' : gnss.fix ? 'text-bg-success' : 'text-bg-warning');
        gnssFixBadge.textContent = gnss.mode_label || gnss.status || 'Unknown';
    }
    const gnssSourceBadge = document.querySelector('#switchberry-gnss-source');
    if (gnssSourceBadge) {
        gnssSourceBadge.classList.remove('text-bg-primary', 'text-bg-secondary');
        gnssSourceBadge.classList.add(gnss.configured ? 'text-bg-primary' : 'text-bg-secondary');
        gnssSourceBadge.textContent = gnss.configured ? 'Timing source enabled' : 'Monitor only';
    }
    const guidance = document.querySelector('#switchberry-gnss-guidance');
    if (guidance) {
        guidance.classList.remove('border-success-subtle', 'border-warning-subtle', 'border-danger-subtle');
        guidance.classList.add(!gnss.online ? 'border-danger-subtle' : gnss.fix ? 'border-success-subtle' : 'border-warning-subtle');
        const copy = guidance.querySelector('div');
        if (copy) {
            copy.textContent = gnss.guidance || gnss.status || 'Waiting for receiver status.';
        }
    }
    setText('#switchberry-gnss-mode', gnss.mode_label);
    setText('#switchberry-gnss-count', `${gnss.satellites_used ?? 0} / ${gnss.satellites_visible ?? 0}`);
    setText('#switchberry-gnss-used-header', gnss.satellites_used ?? 0);
    setText('#switchberry-gnss-visible-header', gnss.satellites_visible ?? 0);
    setText('#switchberry-gnss-hdop', gnss.dop?.hdop);
    setText('#switchberry-gnss-pdop', gnss.dop?.pdop);
    const latitude = Number(gnss.latitude);
    const longitude = Number(gnss.longitude);
    setText('#switchberry-gnss-latitude', gnss.latitude != null && Number.isFinite(latitude) ? latitude.toFixed(7) : null);
    setText('#switchberry-gnss-longitude', gnss.longitude != null && Number.isFinite(longitude) ? longitude.toFixed(7) : null);
    const altitude = Number(gnss.altitude);
    setText('#switchberry-gnss-altitude', gnss.altitude != null && Number.isFinite(altitude) ? `${altitude.toFixed(2)} m` : null);
    const positionError = Number(gnss.position_error_m);
    setText('#switchberry-gnss-position-error', gnss.position_error_m != null && Number.isFinite(positionError) ? `${positionError.toFixed(2)} m` : null);
    setText('#switchberry-gnss-time', gnss.time ? `${gnss.time}${gnss.time_stale ? ' · not valid yet' : ''}` : null, 'Unavailable');
    const locationLink = document.querySelector('#switchberry-gnss-location-link');
    if (locationLink) {
        const hasLocation = Boolean(gnss.fix && Number.isFinite(latitude) && Number.isFinite(longitude));
        locationLink.classList.toggle('d-none', !hasLocation);
        if (hasLocation) {
            locationLink.href = `https://www.openstreetmap.org/?mlat=${encodeURIComponent(latitude)}&mlon=${encodeURIComponent(longitude)}#map=16/${encodeURIComponent(latitude)}/${encodeURIComponent(longitude)}`;
        } else {
            locationLink.removeAttribute('href');
        }
    }
    setText('#switchberry-gnss-model', gnss.device?.model_firmware || gnss.device?.subtype, 'Unknown');
    setText('#switchberry-gnss-driver', gnss.device?.driver, 'Unknown');
    setText('#switchberry-gnss-device', gnss.device?.path, '/dev/ttyAMA5');
    setText('#switchberry-gnss-baud', gnss.device?.baud, 'Unknown');
    setBinaryBadge(document.querySelector('#switchberry-gnss-pps'), Boolean(gnss.pps_present), '/dev/pps0 ready', 'Unavailable');
    setBinaryBadge(document.querySelector('#switchberry-gnss-gpsd'), Boolean(gnss.gpsd_active), 'Active', 'Inactive');
    setBinaryBadge(document.querySelector('#switchberry-gnss-bridge'), Boolean(gnss.bridge_active), 'Active', 'Inactive');
    const receiverConfiguration = gnss.receiver_configuration || {};
    setText('#switchberry-gnss-live-profile', receiverConfiguration.available
        ? String(receiverConfiguration.dynamic_model || 'Unknown').replace(/_/g, ' ')
        : null);
    setText('#switchberry-gnss-live-rate', receiverConfiguration.measurement_rate_ms != null
        ? `${receiverConfiguration.measurement_rate_ms} ms · ${receiverConfiguration.minimum_elevation_deg ?? '—'}° mask`
        : null);
    const liveConstellations = Object.entries(receiverConfiguration.constellations || {})
        .filter(([, enabled]) => enabled === true)
        .map(([name]) => name.toUpperCase());
    setText('#switchberry-gnss-live-constellations', liveConstellations.length ? liveConstellations.join(', ') : null, 'Unknown');
    const rf = receiverConfiguration.rf || {};
    const antenna = document.querySelector('#switchberry-gnss-antenna');
    if (antenna) {
        antenna.classList.remove('text-bg-success', 'text-bg-warning', 'text-bg-danger');
        antenna.classList.add(rf.antenna_status === 'OK' ? 'text-bg-success' : rf.antenna_status && rf.antenna_status !== 'Unknown' ? 'text-bg-danger' : 'text-bg-warning');
        antenna.textContent = `${rf.antenna_status || 'Unknown'}${rf.antenna_power ? ` · power ${rf.antenna_power}` : ''}`;
    }
    setText('#switchberry-gnss-rf', rf.jamming_indicator != null
        ? `${rf.interference_level || 'Unknown'} interference · ${rf.jamming_indicator}/255`
        : null, 'Unknown');
    const timepulse = receiverConfiguration.timepulse || {};
    const pulsePeriod = timepulse.locked_period_us || timepulse.period_us;
    const pulseFrequency = Number(pulsePeriod) > 0 ? 1000000 / Number(pulsePeriod) : null;
    setText('#switchberry-gnss-timepulse', timepulse.enabled
        ? `${pulseFrequency && Number.isFinite(pulseFrequency) ? `${pulseFrequency.toFixed(3)} Hz · ` : ''}${timepulse.time_grid || 'time grid'}${timepulse.align_to_tow ? ' · aligned to TOW' : ''}`
        : 'Disabled');
    const gnssDirty = document.querySelector('#switchberry-gnss-form')?.dataset.dirty === '1';
    const gnssApplied = document.querySelector('#switchberry-gnss-applied');
    if (gnssApplied && !gnssDirty) {
        gnssApplied.classList.remove('text-bg-success', 'text-bg-warning');
        gnssApplied.classList.add(gnss.applied ? 'text-bg-success' : 'text-bg-warning');
        gnssApplied.textContent = gnss.applied ? 'Applied' : 'Pending apply';
    }
    const gnssAppliedAt = document.querySelector('#switchberry-gnss-applied-at');
    if (gnssAppliedAt && !gnssDirty) {
        const timestamp = gnss.last_applied_at ? new Date(gnss.last_applied_at) : null;
        gnssAppliedAt.textContent = timestamp && !Number.isNaN(timestamp.getTime())
            ? `Last applied ${timestamp.toLocaleString()}`
            : 'No successful apply recorded';
    }
    renderGnssSky(gnss.satellites || []);
    renderGnssSatelliteTable(gnss.satellites || []);

    const sma = payload.sma_io || {};
    const smaDirty = document.querySelector('#switchberry-sma-form')?.dataset.dirty === '1';
    const smaOverall = document.querySelector('#switchberry-sma-overall');
    if (smaOverall && !smaDirty) {
        smaOverall.classList.remove('text-bg-success', 'text-bg-warning');
        smaOverall.classList.add(sma.applied ? 'text-bg-success' : 'text-bg-warning');
        smaOverall.textContent = sma.status || 'Status unavailable';
    }
    const appliedAt = document.querySelector('#switchberry-sma-applied-at');
    if (appliedAt && !smaDirty) {
        const timestamp = sma.last_applied_at ? new Date(sma.last_applied_at) : null;
        appliedAt.textContent = timestamp && !Number.isNaN(timestamp.getTime())
            ? `Last applied ${timestamp.toLocaleString()}`
            : 'No successful apply recorded';
    }
    (sma.connectors || []).forEach((connector) => {
        const row = document.querySelector(`[data-sma-hardware="${connector.hardware}"]`);
        const badge = row?.querySelector('.switchberry-sma-applied');
        if (badge && !smaDirty) {
            badge.classList.remove('text-bg-success', 'text-bg-warning');
            badge.classList.add(connector.applied ? 'text-bg-success' : 'text-bg-warning');
            badge.textContent = connector.applied ? 'Applied' : 'Pending';
        }
        const lockRow = row?.querySelector('.switchberry-sma-lock-row');
        const lock = row?.querySelector('.switchberry-sma-lock');
        lockRow?.classList.toggle('d-none', !connector.channel);
        if (lock) {
            lock.textContent = connector.channel ? `CH${connector.channel} · ${connector.lock_state || 'Unknown'}` : '';
        }
        const actual = row?.querySelector('.switchberry-sma-actual');
        const actualRow = row?.querySelector('.switchberry-sma-actual-row');
        const rowDirty = row?.dataset.dirty === '1';
        if (!rowDirty) {
            actualRow?.classList.toggle('d-none', connector.actual_frequency_hz == null);
        }
        if (actual && connector.actual_frequency_hz != null && !rowDirty) {
            const ppm = connector.accuracy_ppm == null ? '' : ` · ${Number(connector.accuracy_ppm).toFixed(3)} ppm`;
            actual.textContent = `${humanFrequency(connector.actual_frequency_hz)}${ppm}`;
        }
    });

    const clockmatrix = payload.clockmatrix || {};
    const clockmatrixDirty = document.querySelector('#switchberry-clockmatrix-form')?.dataset.dirty === '1';
    const clockmatrixOverall = document.querySelector('#switchberry-clockmatrix-overall');
    if (clockmatrixOverall && !clockmatrixDirty) {
        clockmatrixOverall.classList.remove('text-bg-success', 'text-bg-warning');
        clockmatrixOverall.classList.add(clockmatrix.applied ? 'text-bg-success' : 'text-bg-warning');
        clockmatrixOverall.textContent = clockmatrix.status || 'Status unavailable';
    }
    const clockmatrixAppliedAt = document.querySelector('#switchberry-clockmatrix-applied-at');
    if (clockmatrixAppliedAt && !clockmatrixDirty) {
        const timestamp = clockmatrix.last_applied_at ? new Date(clockmatrix.last_applied_at) : null;
        clockmatrixAppliedAt.textContent = timestamp && !Number.isNaN(timestamp.getTime())
            ? `Last applied ${timestamp.toLocaleString()}`
            : 'Automatic board tuning is active';
    }
    (clockmatrix.channels || []).forEach((channel) => {
        setStatusBadge(document.querySelector(`[data-clockmatrix-state="${channel.channel}"]`), channel.state);
        const phase = document.querySelector(`[data-clockmatrix-phase="${channel.channel}"]`);
        const phaseNanoseconds = Number(channel.phase_nanoseconds);
        if (phase) {
            phase.textContent = channel.phase_nanoseconds != null && Number.isFinite(phaseNanoseconds)
                ? `${phaseNanoseconds.toFixed(3)} ns`
                : 'Unavailable';
        }
        const bandwidth = document.querySelector(`[data-clockmatrix-bandwidth="${channel.channel}"]`);
        if (bandwidth) {
            bandwidth.textContent = channel.loop_bandwidth_value != null
                ? `${channel.loop_bandwidth_value} ${channel.loop_bandwidth_unit || ''}`.trim()
                : 'Unavailable';
        }
        const phaseSlope = document.querySelector(`[data-clockmatrix-psl="${channel.channel}"]`);
        if (phaseSlope) {
            phaseSlope.textContent = channel.phase_slope_limit_ns_per_s != null
                ? `${channel.phase_slope_limit_ns_per_s} ns/s`
                : 'Unavailable';
        }
        const damping = document.querySelector(`[data-clockmatrix-damping="${channel.channel}"]`);
        if (damping) {
            damping.textContent = channel.damping_factor != null ? channel.damping_factor : 'Unavailable';
        }
        const combo = document.querySelector(`[data-clockmatrix-combo="${channel.channel}"]`);
        if (combo) {
            combo.textContent = channel.combo || 'Unavailable';
        }
    });
}

export function initSwitchberry() {
    const refreshButton = document.querySelector('#switchberry-refresh');
    if (!refreshButton) {
        return;
    }

    console.info('RaspAP Switchberry module initialized');
    let refreshing = false;
    const refresh = async () => {
        if (refreshing || document.hidden) {
            return;
        }
        refreshing = true;
        const icon = refreshButton.querySelector('i');
        icon?.classList.add('fa-spin');
        try {
            const response = await fetch('/ajax/switchberry/get_status.php', {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            updateStatus(await response.json());
        } catch (error) {
            console.warn('Unable to refresh Switchberry status', error);
        } finally {
            icon?.classList.remove('fa-spin');
            refreshing = false;
        }
    };

    refreshButton.addEventListener('click', refresh);
    document.querySelector('#network-mode')?.addEventListener('change', updateDynamicFields);
    document.querySelectorAll('#ptp-role, #ptp-transport, #bc-profile').forEach((field) => {
        field.addEventListener('change', updateDynamicFields);
    });
    document.querySelectorAll('.switchberry-source-toggle').forEach((field) => {
        field.addEventListener('change', updateDynamicFields);
    });
    document.querySelectorAll('.switchberry-sma-profile').forEach((field) => {
        field.addEventListener('change', () => {
            updateSmaFields();
            markSmaDirty(field.closest('[data-sma-row]'));
        });
    });
    document.querySelectorAll('.switchberry-sma-role, .switchberry-sma-frequency').forEach((field) => {
        field.addEventListener('input', () => {
            updateSmaFields();
            markSmaDirty(field.closest('[data-sma-row]'));
        });
    });
    document.querySelectorAll('.switchberry-clockmatrix-override').forEach((field) => {
        field.addEventListener('change', () => {
            updateClockmatrixFields();
            markClockmatrixDirty();
        });
    });
    document.querySelectorAll('.switchberry-clockmatrix-dirty').forEach((field) => {
        field.addEventListener('change', () => {
            updateClockmatrixFields();
            markClockmatrixDirty();
        });
    });
    document.querySelector('#switchberry-clockmatrix-form')?.addEventListener('submit', () => {
        document.querySelectorAll('.switchberry-clockmatrix-tuning').forEach((field) => {
            field.disabled = false;
        });
    });
    const clockmatrixActionMessages = {
        reacquire: 'Briefly pulse this channel through FREERUN, then return it to NORMAL and reacquire its best reference?',
        normal: 'Return this channel to NORMAL automatic reference tracking?',
        holdover: 'Force this channel into HOLDOVER? It will stop tracking new input changes.',
        freerun: 'Force this channel into FREERUN? Its output will no longer be disciplined to a reference.'
    };
    document.querySelectorAll('.switchberry-clockmatrix-action').forEach((button) => {
        button.addEventListener('click', (event) => {
            const message = clockmatrixActionMessages[button.dataset.action] || 'Apply this ClockMatrix action?';
            if (!window.confirm(`Channel ${button.dataset.channel}: ${message}`)) {
                event.preventDefault();
            }
        });
    });
    document.querySelectorAll('.switchberry-gnss-source-toggle, .switchberry-gnss-manage-toggle').forEach((field) => {
        field.addEventListener('change', () => {
            updateGnssFields();
            markGnssDirty();
        });
    });
    document.querySelectorAll('.switchberry-gnss-source-setting, .switchberry-gnss-dirty').forEach((field) => {
        field.addEventListener('change', () => {
            updateGnssFields();
            markGnssDirty();
        });
    });
    document.querySelector('#switchberry-gnss-form')?.addEventListener('submit', () => {
        document.querySelectorAll('.switchberry-gnss-source-setting, .switchberry-gnss-managed-setting').forEach((field) => {
            field.disabled = false;
        });
    });
    const gnssActionMessages = {
        restart: 'Restart gpsd and the PPS data bridge? The receiver keeps its current acquisition state.',
        hotstart: 'Request a hot start? Current position, time and orbital data are retained.',
        warmstart: 'Request a warm start? Ephemeris is discarded and reacquisition may take longer.',
        coldstart: 'Request a cold start? All orbital data is discarded and reacquisition can take several minutes with a clear sky view.'
    };
    document.querySelectorAll('.switchberry-gnss-action').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (!window.confirm(gnssActionMessages[button.dataset.action] || 'Apply this GNSS action?')) {
                event.preventDefault();
            }
        });
    });
    document.querySelectorAll('.switchberry-port-disable').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (!window.confirm(`Disable front-panel port ${button.dataset.port}?`)) {
                event.preventDefault();
            }
        });
    });
    document.querySelectorAll('.switchberry-reboot').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (!window.confirm('Reboot Switchberry now to activate the selected clock plane? Wi-Fi management will be briefly unavailable.')) {
                event.preventDefault();
            }
        });
    });

    if (window.location.hash.startsWith('#switchberry-')) {
        const tab = document.querySelector(`a[data-bs-toggle="tab"][href="${window.location.hash}"]`);
        if (tab && window.bootstrap?.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(tab).show();
        }
    }
    updateDynamicFields();
    const initialGnssSatellites = document.querySelector('#switchberry-gnss-initial-satellites');
    if (initialGnssSatellites) {
        try {
            const satellites = JSON.parse(initialGnssSatellites.textContent || '[]');
            renderGnssSky(satellites);
            renderGnssSatelliteTable(satellites);
        } catch (error) {
            console.warn('Unable to render initial GNSS satellite data', error);
        }
    }
    window.setInterval(refresh, 10000);
}
