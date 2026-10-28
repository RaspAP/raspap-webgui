export function initWireGuard() {
    console.info("RaspAP WireGuard module initialized");

    $('#wg-confirm-delete').on('show.bs.modal', function (e) {
        var data = $(e.relatedTarget).data();
        $('.btn-delete', this).data('recordId', data.recordId);
    });

    $('#wg-confirm-activate').on('shown.bs.modal', function (e) {
        var data = $(e.relatedTarget).data();
        $('.btn-activate', this).data('recordId', data.recordId);
    });

    $("#PanelManual").hide();

    // show the kill switch options only when the kill switch is on
    const toggleKSwitchOptions = () => $('#wg-kswitch-options').toggle($('#chxwgkswitch').is(':checked'));
    $('#chxwgkswitch').on('change', toggleKSwitchOptions);
    toggleKSwitchOptions();
    // the automatic login is an option of the captive portal login
    const togglePortalAuto = () => $('#wg-kswitch-portal-auto').toggle($('#chxwgkswitchportal').is(':checked'));
    $('#chxwgkswitchportal').on('change', togglePortalAuto);
    togglePortalAuto();

    $('#wg-upload,#wg-manual').on('click', function (e) {
        if (this.id == 'wg-upload') {
            $('#PanelManual').hide();
            $('#PanelUpload').show();
        } else if (this.id == 'wg-manual') {
            $('#PanelUpload').hide();
            $('#PanelManual').show();
        }
    });
}