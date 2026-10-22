import { loadAsync } from "../helpers.js";

// The uplink watchdog switch on the WiFi client page. The switch stays
// hidden when the watchdog is not installed.
export function initUplinkWatchdog_ajax() {
    const row = $('.js-uplink-watchdog');
    if (!row.length) {
        return;
    }
    const toggle = $('#chxuplinkwatchdog');
    const status = $('.js-uplink-watchdog-status');

    function show(data) {
        if (!data.available) {
            row.addClass('d-none');
            return;
        }
        row.removeClass('d-none');
        toggle.prop('checked', data.enabled);
        let text = '';
        if (data.enabled && data.lastAction > 0) {
            const time = new Date(data.lastAction * 1000).toLocaleString();
            text = data.lastResult === 'reconnected'
                ? `Last reconnect: ${time}`
                : `Last reconnect failed: ${time}`;
        }
        status.text(text);
    }

    function load(action) {
        toggle.prop('disabled', true);
        return loadAsync('ajax/networking/uplink_watchdog.php', {'action': action})
            .done(show)
            .fail(function() {
                // show the real state again
                if (action !== 'status') {
                    load('status');
                }
            })
            .always(function() {
                toggle.prop('disabled', false);
            });
    }

    toggle.on('change', function() {
        load(this.checked ? 'enable' : 'disable');
    });

    load('status');
}
