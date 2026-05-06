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

                if (action === 'reboot') {
                    const reconnectModalEl = $('#system-reconnect');
                    const modal = new bootstrap.Modal(reconnectModalEl);
                    modal.show();
                    handleReconnect(reconnectModalEl);
                }
            });
    });

    function handleReconnect(modal) {
        const secondsEl = $(modal).find('#system-reconnect-seconds');
        
        let countdownInterval;
        const attemptSeconds = 20;

        const attemptReconnect = async () => {
            console.log('attempting reconnect');
            $(secondsEl).text('...');

            const startCountdown = (e) => {
                console.log('still rebooting - start countdown', e);
                clearInterval(countdownInterval);
                let countdownInt = attemptSeconds;
                countdownInterval = setInterval(() => {
                    console.log('decrement countdownInt');
                    if (countdownInt === 0) attemptReconnect();
                    $(secondsEl).text(countdownInt);
                    if (countdownInt > 0) countdownInt--;
                }, 1000);
            }

            try {
                // fetch to url and get status
                const checkUrl = window.location.origin;
                const response = await fetch(checkUrl);

                if (response.status === 200) {
                    console.log('reconnected - reload the page');
                    window.location.reload();
                } else {
                    startCountdown();
                }
            } catch (e) {
                startCountdown(e);
            }
        };

        attemptReconnect();
    }

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