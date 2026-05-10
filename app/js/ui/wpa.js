export function initWPA() {
    console.info("RaspAP WPA module initialized");

    $(document).on('input', '.network-passphrase', function(e) {
        let initialValue = $(this).data('init-value');
        let currentValue = $(this).val();

        let updateButton = $(this).parent().parent().find('.update-passphrase');

        if (updateButton) {
            if (initialValue == currentValue) {
                updateButton.hide().removeClass('show');
            } else {
                updateButton.show().addClass('show');
            }
        }
    });

    $(document).on('click', '.open-advanced', function(e) {
        let cardBody = $(this).closest('.card-body');
        let advancedPane = cardBody.find('.network-advanced');

        if (advancedPane) {
            advancedPane.addClass('show');

            advancedPane.find('.close-advanced').on('click', function() {
                advancedPane.removeClass('show');
            });

            advancedPane.find('.clear-priority').on('click', function() {
                let priorityInput = $(this).parent().find('input[type="number"]');
                priorityInput.val('');
            });
        }
    });
}