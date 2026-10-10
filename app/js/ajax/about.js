import { getCSRFToken } from "../helpers.js";

const STEP_COMPLETE = 6;
const STEP_ERROR = 7;
const POLL_INTERVAL = 1000;
// the installer restarts lighttpd and php-fpm, so tolerate a brief outage
const MAX_POLL_FAILURES = 60;
// the installer may briefly disappear while it relaunches itself
const MAX_STOPPED_POLLS = 5;

// sys_read_logfile.php returns one step number per line, plus "stopped"
// if the installer is no longer running
export function parseUpdateResponse(response) {
    const lines = String(response).split(/\r?\n/).map(line => line.trim());
    const steps = lines.filter(line => /^\d+$/.test(line)).map(Number);
    return {
        steps: steps,
        complete: steps.includes(STEP_COMPLETE),
        error: steps.includes(STEP_ERROR),
        stopped: lines.includes('stopped')
    };
}

function showUpdateResult(success) {
    const msg = $(success ? '#successMsg' : '#errorMsg').data('message');
    $('#updateMsg').after('<span class="small">' + msg + '</span>');
    $('#updateMsg').addClass(success ? 'fa-check' : 'fa-times');
    $('#updateMsg').removeClass('invisible');
    $('#updateSync2').removeClass("fa-spin");
    $('#updateOk').removeAttr('disabled');
}

export function fetchUpdateResponse(since, failures = 0, stoppedPolls = 0) {
    $.ajax({
        url: 'ajax/system/sys_read_logfile.php',
        type: 'GET',
        data: { since: since },
        dataType: 'text',
        cache: false,
        success: function(response) {
            const status = parseUpdateResponse(response);
            status.steps.forEach(step => $('#updateStep' + step).removeClass('invisible'));

            if (status.complete) {
                showUpdateResult(true);
            } else if (status.error) {
                showUpdateResult(false);
            } else if (status.stopped && stoppedPolls + 1 >= MAX_STOPPED_POLLS) {
                console.error("Update stopped before completing");
                showUpdateResult(false);
            } else {
                setTimeout(function() {
                    fetchUpdateResponse(since, 0, status.stopped ? stoppedPolls + 1 : 0);
                }, POLL_INTERVAL);
            }
        },
        error: function(xhr, status, error) {
            if (failures + 1 >= MAX_POLL_FAILURES) {
                console.error("AJAX Error:", error);
                showUpdateResult(false);
            } else {
                setTimeout(function() {
                    fetchUpdateResponse(since, failures + 1, stoppedPolls);
                }, POLL_INTERVAL * 2);
            }
        }
    });
}

export function initAbout_ajax() {
    console.info("RaspAP About ajax module initialized");

    $('#chkupdateModal').on('shown.bs.modal', function (e) {
        var csrfToken = getCSRFToken();
        var faCheck = '<i class="fas fa-check ms-2"></i><br />';
        var msgDismiss = $('#js-check-dismiss').data('message');
        var dismiss = $("#js-check-dismiss");

        $.post('ajax/system/sys_chk_update.php', {'csrf_token': csrfToken})
            .done(function(data) {
                var response;
                try {
                    response = JSON.parse(data);
                } catch (e) {
                    response = {error: true};
                }
                $("#updateSync").removeClass("fa-spin");
                if (response.error) {
                    $("#msg-check-update").html($('#msgCheckFailed').data('message'));
                    $("#js-sys-check-update").addClass("collapse");
                } else if (response.update === true) {
                    $("#msg-check-update").html($('#msgUpdate').data('message') + ' ' + response.tag);
                    $("#msg-check-update").append(faCheck);
                    $("#msg-check-update").append("<p>" + $('#msgInstall').data('message') + "</p>");
                    $("#js-sys-check-update").removeClass("collapse");
                } else {
                    $("#msg-check-update").html($('#msgLatest').data('message'));
                    $("#msg-check-update").append(faCheck);
                    $("#js-sys-check-update").remove();
                    dismiss.text(msgDismiss);
                    dismiss.removeClass("btn-outline-secondary");
                    dismiss.addClass("btn-primary");
                }
            })
            .fail(function() {
                $("#updateSync").removeClass("fa-spin");
                $("#msg-check-update").html($('#msgCheckFailed').data('message'));
            });
    });

    $('#performUpdate').on('submit', function(event) {
        event.preventDefault();
        var csrfToken = getCSRFToken();
        $('#chkupdateModal').modal('hide');
        $('#performupdateModal').modal('show');
        $.post('ajax/system/sys_perform_update.php', {'csrf_token': csrfToken})
            .done(function(data) {
                var started;
                try {
                    started = JSON.parse(data).started;
                } catch (e) {
                    started = 0;
                }
                fetchUpdateResponse(started);
            })
            .fail(function() {
                showUpdateResult(false);
            });
    });
}
