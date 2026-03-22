export function initWireGuard() {
    console.info("RaspAP WireGuard module initialized");

    $("#PanelManual").hide();

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