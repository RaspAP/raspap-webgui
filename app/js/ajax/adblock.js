import { getCSRFToken } from '../helpers.js';

export function initAdblock_ajax() {
    console.info("RaspAP adblock ajax module initialized");
    /* Updates the selected blocklist
    * Request is passed to an ajax handler to download the associated list.
    * Interface elements are updated to indicate current progress, status.
    */
    function updateBlocklist() {
        const opt = $('#cbxblocklist option:selected');
        const blocklist_id = opt.val();
        const csrfToken = getCSRFToken();

        if (blocklist_id === '') return;

        const statusIcon = $('#cbxblocklist-status').find('i');
        const statusWrapper = $('#cbxblocklist-status');

        statusIcon.removeClass('fa-check fa-exclamation-triangle').addClass('fa-cog fa-spin');
        statusWrapper.removeClass('check-hidden check-error check-updated').addClass('check-progress');

        $.post('ajax/adblock/update_blocklist.php', {
            'blocklist_id': blocklist_id,
            'csrf_token': csrfToken
        }, function (data) {
            let jsonData;
            try {
                jsonData = JSON.parse(data);
            } catch (e) {
                showError("Unexpected server response.");
                return;
            }
            const resultCode = jsonData['return'];
            const output = jsonData['output']?.join('\n') || '';

            switch (resultCode) {
                case 0:
                    statusIcon.removeClass('fa-cog fa-spin').addClass('fa-check');
                    statusWrapper.removeClass('check-progress').addClass('check-updated').delay(500).animate({ opacity: 1 }, 700);
                    $('#blocklist-' + jsonData['list']).text("Just now");
                    break;
                case 1:
                    showError("Invalid blocklist.");
                    break;
                case 2:
                    showError("No blocklist provided.");
                    break;
                case 3:
                    showError("Could not parse blocklists.json.");
                    break;
                case 4:
                    showError("blocklists.json file not found.");
                    break;
                case 5:
                    showError("Update script not found.");
                    break;
                default:
                    showError("Unknown error occurred.");
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            showError(`AJAX request failed: ${textStatus}`);
        });

        function showError(message) {
            statusIcon.removeClass('fa-cog fa-spin').addClass('fa-exclamation-triangle');
            statusWrapper.removeClass('check-progress').addClass('check-error');
            alert("Blocklist update failed:\n\n" + message);
        }
    }
    globalThis.updateBlocklist = updateBlocklist;
}