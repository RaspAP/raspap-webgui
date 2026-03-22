export function initAdblock() {
    console.info("RaspAP adblock module initialized");

    function clearBlocklistStatus() {
        $('#cbxblocklist-status').removeClass('check-updated').addClass('check-hidden');
    }
    globalThis.clearBlocklistStatus = clearBlocklistStatus;
}