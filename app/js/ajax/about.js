import { getCSRFToken } from "../helpers.js";

export function fetchUpdateResponse() {
    const complete = 6;
    const error = 7;
    let phpFile = 'ajax/system/sys_read_logfile.php';

    $.ajax({
        url: phpFile,
        type: 'GET',
        success: function(response) { 
            for (let i = 1; i <= 6; i++) {
                let divId = '#updateStep' + i;
                if (response.includes(i.toString())) {
                    $(divId).removeClass('invisible');
                }
            }
            // check if the update is complete or if there's an error
            if (response.includes(complete)) {
                var successMsg = $('#successMsg').data('message');
                $('#updateMsg').after('<span class="small">' + successMsg + '</span>');
                $('#updateMsg').addClass('fa-check');
                $('#updateMsg').removeClass('invisible');
                $('#updateStep6').removeClass('invisible');
                $('#updateSync2').removeClass("fa-spin");
                $('#updateOk').removeAttr('disabled');
            } else if (response.includes(error)) {
                var errorMsg = $('#errorMsg').data('message');
                $('#updateMsg').after('<span class="small">' + errorMsg + '</span>');
                $('#updateMsg').addClass('fa-times');
                $('#updateMsg').removeClass('invisible');
                $('#updateSync2').removeClass("fa-spin");
                $('#updateOk').removeAttr('disabled');
            } else {
                setTimeout(fetchUpdateResponse, 500);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", error);
        }
    });
}

export function initAbout_ajax() {
    console.info("RaspAP About ajax module initialized");
    
    $('#chkupdateModal').on('shown.bs.modal', function (e) {
    var csrfToken = getCSRFToken();
    $.post('ajax/system/sys_chk_update.php',{'csrf_token': csrfToken},function(data){
            var response = JSON.parse(data);
            var tag = response.tag;
            var update = response.update;
            var msg;
            var msgUpdate = $('#msgUpdate').data('message');
            var msgLatest = $('#msgLatest').data('message');
            var msgInstall = $('#msgInstall').data('message');
            var msgDismiss = $('#js-check-dismiss').data('message');
            var faCheck = '<i class="fas fa-check ms-2"></i><br />';
            $("#updateSync").removeClass("fa-spin");
            if (update === true) {
                msg = msgUpdate +' '+tag;
                $("#msg-check-update").html(msg);
                $("#msg-check-update").append(faCheck);
                $("#msg-check-update").append("<p>"+msgInstall+"</p>");
                $("#js-sys-check-update").removeClass("collapse");
            } else {
                msg = msgLatest;
                let dismiss = $("#js-check-dismiss");
                $("#msg-check-update").html(msg);
                $("#msg-check-update").append(faCheck);
                $("#js-sys-check-update").remove();
                dismiss.text(msgDismiss);
                dismiss.removeClass("btn-outline-secondary");
                dismiss.addClass("btn-primary");
            }
        });
    });

    $('#performUpdate').on('submit', function(event) {
        event.preventDefault();
        var csrfToken = getCSRFToken();
        $.post('ajax/system/sys_perform_update.php',{
            'csrf_token': csrfToken
        })
        $('#chkupdateModal').modal('hide');
        $('#performupdateModal').modal('show');
    });
}
