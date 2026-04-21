import { getCSRFToken } from "../helpers.js";

export function initPlugins_ajax() {
    console.info("RaspAP Plugins ajax module initialized");

    $('#js-install-plugin-confirm').on('click', function (e) {
        var button = $('#install-user-plugin').data('button');
        var manifestData = button.data('plugin-manifest');
        var installPath = manifestData.install_path;
        var pluginUri = manifestData.plugin_uri;
        var pluginVersion = manifestData.version;
        var pluginConfirm = $('#js-install-plugin-confirm').text();
        var progressText = $('#js-install-plugin-confirm').attr('data-message');
        var successHtml = $('#plugin-install-message').attr('data-message');
        var successText = $('<div>').text(successHtml).text();
        var csrfToken = getCSRFToken();

        if (pluginConfirm  === 'Install now') {
            $("#install-user-plugin").modal('hide');
            $("#install-plugin-progress").modal('show');
            $.post(
                'ajax/plugins/do_plugin_install.php',
                {
                    'plugin_uri': pluginUri,
                    'plugin_version': pluginVersion,
                    'install_path': installPath,
                    'csrf_token': csrfToken
                },
                function (data) {
                    setTimeout(function () {
                        response = JSON.parse(data);
                        if (response === true) {
                            $('#plugin-install-message').contents().first().text(successText);
                            $('#plugin-install-message')
                                .find('i')
                                .removeClass('fas fa-cog fa-spin link-secondary')
                                .addClass('fas fa-check');
                            $('#js-install-plugin-ok').removeAttr("disabled");
                        } else {
                            const errorMessage = jsonData.error || 'An unknown error occurred.';
                            var errorLog = '<textarea class="plugin-log text-secondary" readonly>' + errorMessage + '</textarea>';
                            $('#plugin-install-message')
                                .contents()
                                .first()
                                .replaceWith('An error occurred installing the plugin:');
                            $('#plugin-install-message').append(errorLog);
                            $('#plugin-install-message').find('i').removeClass('fas fa-cog fa-spin link-secondary');
                            $('#js-install-plugin-ok').removeAttr("disabled");
                        }
                    }, 200);
                }
            ).fail(function (xhr) {
                const jsonData = JSON.parse(xhr.responseText);
                const errorMessage = jsonData.error || 'An unknown error occurred.';
                $('#plugin-install-message')
                    .contents()
                    .first()
                    .replaceWith('An error occurred installing the plugin:');
                var errorLog = '<textarea class="plugin-log text-secondary" readonly>' + errorMessage + '</textarea>';
                $('#plugin-install-message').append(errorLog);
                $('#plugin-install-message').find('i').removeClass('fas fa-cog fa-spin link-secondary');
                $('#js-install-plugin-ok').removeAttr("disabled");
            });
        } else if (pluginConfirm  === 'Get Insiders') {
            window.open('https://docs.raspap.com/insiders/', '_blank');
            return;
        } else if (pluginConfirm === 'OK') {
            $("#install-user-plugin").modal('hide');
        }
    });
}