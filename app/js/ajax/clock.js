import { getCSRFToken } from "../helpers.js";

// difference between the device clock and the browser clock that gives a warning
const MAX_OFFSET_MS = 2 * 60 * 1000;

// Formats a time difference in milliseconds, for example "18 h 24 min"
function formatOffset(ms) {
    const minutes = Math.round(Math.abs(ms) / 60000);
    const days = Math.floor(minutes / 1440);
    const hours = Math.floor((minutes % 1440) / 60);
    const rest = minutes % 60;
    const parts = [];
    if (days) parts.push(days + ' d');
    if (hours) parts.push(hours + ' h');
    if (rest || !parts.length) parts.push(rest + ' min');
    return parts.join(' ');
}

function postTime(data) {
    data.csrf_token = getCSRFToken();
    return $.post('ajax/system/sys_time.php', data, null, 'json');
}

// A persistent alert stays until the user closes it; other alerts close
// automatically, like the other RaspAP status messages
function showAlert(type, text, button, persistent = false) {
    $('#clock-alert').remove();
    const alert = $('<div class="alert alert-dismissible" role="alert" id="clock-alert"></div>')
        .addClass('alert-' + type)
        .toggleClass('alert-persistent', persistent)
        .append($('<i class="fas fa-clock me-2"></i>'))
        .append($('<span></span>').text(text))
        .append($('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'));
    if (button) {
        alert.append($('<div class="mt-2"></div>').append(button));
    }
    $('#layoutSidenav_content main .container-fluid').first().prepend(alert);
}

// Compares the clock of the device with the clock of the browser. When the
// device clock is wrong and NTP did not synchronize it, the user can set the
// device clock to the time of the browser. A WireGuard handshake fails when
// the clock is behind, and NTP can not correct the clock through a tunnel
// that is down.
export function initClock_ajax() {
    const sent = Date.now();
    postTime({ a: 'get' }).done(function (data) {
        const received = Date.now();
        // the device read its clock about halfway between sent and received
        const offset = data.time - (sent + received) / 2;
        if (data.synced || Math.abs(offset) < MAX_OFFSET_MS) {
            return;
        }
        const direction = offset < 0 ? 'behind' : 'ahead of';
        const button = $('<button type="button" class="btn btn-sm btn-outline-secondary"></button>')
            .text('Set the device clock to the time of this browser')
            .on('click', function () {
                $(this).prop('disabled', true);
                postTime({ a: 'set', time: String(Date.now()) }).done(function (result) {
                    showAlert('success', 'The device clock is now ' + new Date(result.time).toLocaleString() + '.');
                }).fail(function () {
                    showAlert('danger', 'Unable to set the device clock.');
                });
            });
        showAlert('warning',
            'The device clock is ' + formatOffset(offset) + ' ' + direction + ' the clock of this browser, ' +
            'and NTP did not synchronize it. A VPN tunnel can fail when the clock is behind.',
            button, true);
    });
}
