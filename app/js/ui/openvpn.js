export function initOpenVPN() {
    console.info("RaspAP OpenVPN module initialized");

    $('#ovpn-confirm-delete').on('show.bs.modal', function (e) {
        var data = $(e.relatedTarget).data();
        $('.btn-delete', this).data('recordId', data.recordId);
    });

    $('#ovpn-confirm-activate').on('shown.bs.modal', function (e) {
        var data = $(e.relatedTarget).data();
        $('.btn-activate', this).data('recordId', data.recordId);
    });

    $('#ovpn-userpw,#ovpn-certs').on('click', function (e) {
        if (this.id == 'ovpn-userpw') {
            $('#PanelCerts').hide();
            $('#PanelUserPW').show();
        } else if (this.id == 'ovpn-certs') {
            $('#PanelUserPW').hide();
            $('#PanelCerts').show();
        }
    });
}