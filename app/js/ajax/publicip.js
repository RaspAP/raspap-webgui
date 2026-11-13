import { loadAsync } from "../helpers.js";

// Fills in the public IP address after the page has loaded, so a slow
// lookup does not delay the page itself
export function initPublicIP_ajax() {
    const ipFields = $('.js-public-ip');
    if (!ipFields.length) {
        return;
    }
    console.info("RaspAP public IP ajax module initialized");

    loadAsync('ajax/networking/get_public_ip.php')
        .done(function(data) {
            const ip = data.ip || '';
            if (ip === '') {
                ipFields.text('-');
                return;
            }
            ipFields.text(ip);
            $('.js-public-ip-link')
                .attr('href', 'https://ipapi.co/' + encodeURIComponent(ip))
                .removeClass('d-none');
        })
        .fail(function() {
            ipFields.text('-');
        });
}
