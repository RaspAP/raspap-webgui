import { getCSRFToken } from "../helpers.js";

export function initWireGuard_ajax() {
    console.info("RaspAP WireGuard ajax module initialized");

    $('#wg-confirm-delete').on('click', '.btn-delete', function (e) {
        var cfg_id = $(this).data('recordId');
        var csrfToken = getCSRFToken();
        $.post('ajax/wg/delete_wgcfg.php',{'cfg_id':cfg_id, 'csrf_token': csrfToken},function(data){
            let jsonData = JSON.parse(data);
            $("#wg-confirm-delete").modal('hide');
            var row = $(document.getElementById("wg-config-row-" + cfg_id));
            row.fadeOut( "slow", function() {
                row.remove();
            });
        });
    });

    $('#wg-confirm-activate').on('click', '.btn-activate', function (e) {
        var cfg_id = $(this).data('record-id');
        var csrfToken = getCSRFToken();
        $.post('ajax/wg/activate_wgcfg.php',{'cfg_id':cfg_id, 'csrf_token': csrfToken},function(data){
            let jsonData = JSON.parse(data);
            $("#wg-confirm-activate").modal('hide');
            setTimeout(function(){
                window.location.reload();
            },300);
        });
    });

    // Handler for the WireGuard generate key button
    $('.wg-keygen').click(function(){
        var parentGroup = $(this).closest('.input-group');
        var entity_pub = parentGroup.find('input[type="text"]');
        var updated = entity_pub.attr('name')+"-pubkey-status";
        var csrfToken = $('meta[name="csrf_token"]').attr('content');
        $.post('ajax/networking/get_wgkey.php',{'entity':entity_pub.attr('name'), 'csrf_token': csrfToken},function(data){
            var jsonData = JSON.parse(data);
            entity_pub.val(jsonData.pubkey);
            $('#' + updated).removeClass('check-hidden').addClass('check-updated').delay(500).animate({ opacity: 1 }, 700);
        });
    });

    // Handler for wireguard client.conf download
    $('.wg-client-dl').click(function(){
        var req = new XMLHttpRequest();
        var url = 'ajax/networking/get_wgcfg.php';
        req.open('get', url, true);
        req.responseType = 'blob';
        req.setRequestHeader('Content-type', 'text/plain; charset=UTF-8');
        req.onreadystatechange = function (event) {
            if(req.readyState == 4 && req.status == 200) {
                var blob = req.response;
                var link=document.createElement('a');
                link.href=window.URL.createObjectURL(blob);
                link.download = 'client.conf';
                link.click();
            }
        }
        req.send();
    });
}