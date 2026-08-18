const toneClasses = ['tone-primary', 'tone-success', 'tone-warning', 'tone-danger', 'tone-secondary'];

function setText(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value ?? '—';
    }
}

function setBadge(id, value, tone = 'secondary') {
    const element = document.getElementById(id);
    if (!element) {
        return;
    }
    element.classList.remove('text-bg-primary', 'text-bg-success', 'text-bg-warning', 'text-bg-danger', 'text-bg-secondary');
    element.classList.add(`text-bg-${tone}`);
    element.textContent = value;
}

function setTileTone(name, tone) {
    const tile = document.querySelector(`[data-switchberry-tile="${name}"]`);
    if (!tile) {
        return;
    }
    tile.classList.remove(...toneClasses);
    tile.classList.add(`tone-${tone}`);
}

function normalizeState(value) {
    return String(value || 'UNKNOWN').trim().toUpperCase();
}

function summarizePtp(data) {
    const ptp = data.ptp || {};
    const plane = data.clock_plane || {};
    const role = normalizeState(ptp.role || data.config?.ptp_role || 'NONE');
    const labels = {
        TC: 'Transparent clock',
        BC: 'Boundary clock',
        GM: 'Grandmaster',
        CLIENT: 'PTP client',
        NONE: 'PTP disabled'
    };
    const tone = role === 'NONE' ? 'secondary' : 'primary';
    const activePlane = plane.active || ptp.plane || 'Unknown';

    setBadge('switchberry-dashboard-ptp-badge', role, tone);
    setText('switchberry-dashboard-ptp-title', labels[role] || role);
    setText('switchberry-dashboard-ptp-detail', `${activePlane} hardware plane${plane.reboot_required ? ' · reboot required' : ''}`);
    setText('switchberry-dashboard-ptp-note', ptp.status || 'PTP role is configured');
    setTileTone('ptp', plane.reboot_required ? 'warning' : tone);
}

function summarizeClockmatrix(data) {
    const clockmatrix = data.clockmatrix || {};
    const channels = Array.isArray(clockmatrix.channels) ? clockmatrix.channels : [];
    const channelState = (channel) => normalizeState(channels.find((item) => Number(item.channel) === channel)?.state);
    const ch5 = channelState(5);
    const ch6 = channelState(6);
    const states = [ch5, ch6];
    let tone = 'success';
    let badge = 'LOCKED';

    if (states.some((state) => state === 'UNKNOWN')) {
        tone = 'danger';
        badge = 'UNAVAILABLE';
    } else if (states.some((state) => state === 'FREERUN')) {
        tone = 'warning';
        badge = 'FREERUN';
    } else if (states.some((state) => state === 'HOLDOVER')) {
        tone = 'warning';
        badge = 'HOLDOVER';
    } else if (states.some((state) => state.includes('ACQ'))) {
        tone = 'warning';
        badge = 'ACQUIRING';
    } else if (!states.every((state) => state.includes('LOCK'))) {
        tone = 'warning';
        badge = 'CHECK';
    }

    const references = Array.isArray(clockmatrix.inputs) ? clockmatrix.inputs.length : 0;
    setBadge('switchberry-dashboard-clockmatrix-badge', badge, tone);
    setText('switchberry-dashboard-clockmatrix-title', `CH5 ${ch5} · CH6 ${ch6}`);
    setText('switchberry-dashboard-clockmatrix-detail', `${references} active reference${references === 1 ? '' : 's'} · ${clockmatrix.status || 'Unknown apply state'}`);
    setText('switchberry-dashboard-clockmatrix-note', clockmatrix.device || 'Renesas ClockMatrix');
    setTileTone('clockmatrix', tone);
}

function summarizeGnss(data) {
    const gnss = data.gnss || {};
    const visible = Number(gnss.satellites_visible || 0);
    const used = Number(gnss.satellites_used || 0);
    const model = gnss.device?.model_firmware || gnss.device?.model || 'GNSS receiver';
    let badge = 'OFFLINE';
    let tone = 'danger';

    if (gnss.fix) {
        badge = gnss.mode_label || 'FIXED';
        tone = 'success';
    } else if (gnss.online) {
        badge = 'ACQUIRING';
        tone = 'warning';
    }

    setBadge('switchberry-dashboard-gnss-badge', badge.toUpperCase(), tone);
    setText('switchberry-dashboard-gnss-title', gnss.status || (gnss.online ? 'Receiver online' : 'Receiver offline'));
    setText('switchberry-dashboard-gnss-detail', `${used}/${visible} satellites used · ${model}`);
    setText('switchberry-dashboard-gnss-note', `${gnss.pps_present ? 'PPS ready' : 'PPS unavailable'} · gpsd ${gnss.gpsd_active ? 'active' : 'inactive'}`);
    setTileTone('gnss', tone);
}

function summarizeTiming(data) {
    const timingItems = Array.isArray(data.timing) ? data.timing : [];
    const stateFor = (name) => normalizeState(timingItems.find((item) => item.name === name)?.state);
    const clockmatrixState = stateFor('clockmatrix');
    const ts2phcState = stateFor('ts2phc');
    const phc2sysState = stateFor('phc2sys');
    const gnss = data.gnss || {};
    const healthy = gnss.pps_present && [clockmatrixState, ts2phcState, phc2sysState].every((state) => state === 'OK');
    const tone = healthy ? 'success' : (gnss.pps_present ? 'warning' : 'danger');
    const badge = healthy ? 'LOCKED' : (gnss.pps_present ? 'PPS READY' : 'CHECK');

    setBadge('switchberry-dashboard-timing-badge', badge, tone);
    setText('switchberry-dashboard-timing-title', `${gnss.pps_present ? 'PPS ready' : 'PPS unavailable'} · PHC ${phc2sysState === 'OK' ? 'synced' : 'waiting'}`);
    setText('switchberry-dashboard-timing-detail', `ClockMatrix ${clockmatrixState} · ts2phc ${ts2phcState}`);
    setText('switchberry-dashboard-timing-note', `gpsd ${gnss.gpsd_active ? 'active' : 'inactive'} · PPS bridge ${gnss.bridge_active ? 'active' : 'inactive'}`);
    setTileTone('timing', tone);
}

function renderReferences(data) {
    const container = document.getElementById('switchberry-dashboard-references');
    if (!container) {
        return;
    }
    container.replaceChildren();
    const inputs = Array.isArray(data.clockmatrix?.inputs) ? data.clockmatrix.inputs : [];
    if (inputs.length === 0) {
        const empty = document.createElement('span');
        empty.className = 'badge rounded-pill text-bg-secondary';
        empty.textContent = 'No timing references enabled';
        container.append(empty);
        return;
    }

    inputs.forEach((input) => {
        const badge = document.createElement('span');
        badge.className = 'badge rounded-pill text-bg-light border text-dark';
        const priority = input.priority === null || input.priority === undefined ? '' : ` · P${input.priority}`;
        badge.textContent = `CH${input.channel} · ${input.label || input.source} · ${input.signal || 'signal'}${priority}`;
        container.append(badge);
    });
}

function updateSummary(data) {
    summarizePtp(data);
    summarizeClockmatrix(data);
    summarizeGnss(data);
    summarizeTiming(data);
    renderReferences(data);
    setText('switchberry-dashboard-updated', `Live · updated ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`);
}

export function initDashboard() {
    const root = document.querySelector('[data-switchberry-dashboard]');
    if (!root) {
        return;
    }

    const refreshButton = document.getElementById('switchberry-dashboard-refresh');
    const errorBox = document.getElementById('switchberry-dashboard-error');
    let refreshing = false;

    const refresh = async () => {
        if (refreshing || document.hidden) {
            return;
        }
        refreshing = true;
        const icon = refreshButton?.querySelector('i');
        icon?.classList.add('fa-spin');
        errorBox?.classList.add('d-none');

        try {
            const response = await fetch('/ajax/switchberry/get_status.php', {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const data = await response.json();
            if (!data.ok) {
                throw new Error(data.error || 'Switchberry controller is unavailable');
            }
            updateSummary(data);
        } catch (error) {
            if (errorBox) {
                errorBox.textContent = `Unable to refresh Switchberry timing state: ${error.message}`;
                errorBox.classList.remove('d-none');
            }
            setText('switchberry-dashboard-updated', 'Live timing state unavailable');
            console.warn('Unable to refresh Switchberry Dashboard summary', error);
        } finally {
            icon?.classList.remove('fa-spin');
            refreshing = false;
        }
    };

    refreshButton?.addEventListener('click', refresh);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refresh();
        }
    });
    refresh();
    window.setInterval(refresh, 30000);
}
