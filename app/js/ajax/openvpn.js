import { getCSRFToken } from "../helpers.js";

export function initOpenVPN_ajax() {
    console.info("RaspAP OpenVPN ajax module initialized");

    $(document).on("click", "#js-clearopenvpn-log", function(e) {
        var csrfToken = getCSRFToken();
        $.post('ajax/logging/clearlog.php?', {
                'logfile':'/tmp/openvpn.log',
                'csrf_token': csrfToken
            }, function(data) {
                let jsonData = JSON.parse(data);
                $("#openvpn-log").val("");
            });
    });

    $('#ovpn-confirm-delete').on('click', '.btn-delete', function (e) {
        var cfg_id = $(this).data('recordId');
        var csrfToken = getCSRFToken();
        $.post('ajax/openvpn/del_ovpncfg.php',{'cfg_id':cfg_id, 'csrf_token': csrfToken},function(data){
            let jsonData = JSON.parse(data);
            $("#ovpn-confirm-delete").modal('hide');
            var row = $(document.getElementById("openvpn-client-row-" + cfg_id));
            row.fadeOut( "slow", function() {
                row.remove();
            });
        });
    });

    $('#ovpn-confirm-activate').on('click', '.btn-activate', function (e) {
        var cfg_id = $(this).data('record-id');
        var csrfToken = getCSRFToken();
        $.post('ajax/openvpn/activate_ovpncfg.php',{'cfg_id':cfg_id, 'csrf_token': csrfToken},function(data){
            let jsonData = JSON.parse(data);
            $("#ovpn-confirm-activate").modal('hide');
            setTimeout(function(){
                window.location.reload();
            },300);
        });
    });
}