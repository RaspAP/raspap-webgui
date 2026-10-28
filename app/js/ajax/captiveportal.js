import { loadAsync } from "../helpers.js";

// The buttons of the captive portal warning (dashboard and WireGuard page):
// "open" allows the login for 5 minutes, "close" ends it. The page loads
// again to show the new state.
export function initCaptivePortal_ajax() {
    $(document).on('click', '.js-captive-portal', function() {
        const button = $(this);
        button.prop('disabled', true);
        loadAsync('ajax/networking/captive_portal.php', {'action': button.data('action')})
            .done(function() {
                window.location.reload();
            })
            .fail(function() {
                button.prop('disabled', false);
            });
    });
}
