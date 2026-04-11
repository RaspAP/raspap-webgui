import { getCSRFToken } from "../helpers.js";

export function initSystem_ajax() {
    console.info("RaspAP System ajax module initialized");
    
    $('#js-sys-reboot, #js-sys-shutdown').on('click', function (e) {
        e.preventDefault();
        var csrfToken = getCSRFToken();
        var action = $(this).data('action');
        $.post('ajax/system/sys_actions.php?', {
                'a': action,
                'csrf_token': csrfToken
            }, function(data) {
                var response = JSON.parse(data);
            });
    });

    $('#js-system-reset-confirm').on('click', function (e) {
        var progressText = $('#js-system-reset-confirm').attr('data-message');
        var successHtml = $('#system-reset-message').attr('data-message');
        var closeHtml = $('#js-system-reset-cancel').attr('data-message');
        var csrfToken = getCSRFToken();
        var progressHtml = $('<div>').text(progressText).html() + '<i class="fas fa-cog fa-spin ms-2"></i>';
        $('#system-reset-message').html(progressHtml);
        $.post('ajax/networking/do_sys_reset.php?',{'csrf_token':csrfToken},function(data){
            setTimeout(function(){
                jsonData = JSON.parse(data);
                if(jsonData['return'] == 0) {
                    $('#system-reset-message').text(successHtml);
                } else {
                    $('#system-reset-message').text('Error occured: '+ jsonData['return']);
                }
                $("#js-system-reset-confirm").hide();
                $("#js-system-reset-cancel").text(closeHtml);
            },750);
        });
    });

    $('#debugModal').on('shown.bs.modal', function (e) {
        var csrfToken = getCSRFToken();
        $.post('ajax/system/sys_debug.php',{'csrf_token': csrfToken},function(data){
            window.location.replace('/ajax/system/sys_get_logfile.php');
            $('#debugModal').modal('hide');
        });
    });
}