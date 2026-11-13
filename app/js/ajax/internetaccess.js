import { loadAsync } from "../helpers.js";

function setCheck(row, check, ok) {
    row.find('.js-access-' + check)
        .removeClass('fa-circle-notch fa-spin')
        .addClass(ok ? 'fa-check' : 'fa-times');
}

// Runs the internet access checks of each interface after the page has
// loaded, in parallel, so the slow checks do not delay the page itself
export function initInternetAccess_ajax() {
    const rows = $('.js-internet-access');
    if (!rows.length) {
        return;
    }
    console.info("RaspAP internet access ajax module initialized");

    rows.each(function() {
        const row = $(this);
        loadAsync('ajax/networking/get_internet_access.php', {'iface': row.data('iface')})
            .done(function(data) {
                if (data.isAP) {
                    row.find('.js-access-checks').addClass('d-none');
                    row.find('.js-access-ap').removeClass('d-none');
                    return;
                }
                setCheck(row, 'ip', data.ip);
                setCheck(row, 'dns', data.dns);
                setCheck(row, 'http', data.http);
                // a failed check on an interface that internet traffic does
                // not use is often expected, for example with a kill switch
                if (!row.data('in-use') && !(data.ip || data.dns || data.http)) {
                    row.find('.js-access-hint').removeClass('d-none');
                }
            })
            .fail(function() {
                ['ip', 'dns', 'http'].forEach(check => setCheck(row, check, false));
            });
    });
}
