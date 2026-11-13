import { getCSRFToken, loadAsync } from "../helpers.js";

function showPluginInstallError(errorMessage) {
    var errorLog = '<textarea class="plugin-log text-secondary" readonly>' + errorMessage + '</textarea>';
    $('#plugin-install-message')
        .contents()
        .first()
        .replaceWith('An error occurred installing the plugin:');
    $('#plugin-install-message').append(errorLog);
    $('#plugin-install-message').find('i').removeClass('fas fa-cog fa-spin link-secondary');
    $('#js-install-plugin-ok').removeAttr("disabled");
}

function showPluginUninstallError(errorMessage) {
    var errorLog = $('<textarea class="plugin-log text-secondary" readonly></textarea>').text(errorMessage);
    $('#plugin-uninstall-message')
        .contents()
        .first()
        .replaceWith('An error occurred uninstalling the plugin:');
    $('#plugin-uninstall-message').find('i').removeClass('fas fa-cog fa-spin link-secondary');
    $('#plugin-uninstall-message').append(errorLog);
    $('#js-uninstall-plugin-ok').removeAttr("disabled");
}

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
                        var response;
                        try {
                            response = JSON.parse(data);
                        } catch (e) {
                            showPluginInstallError('An unexpected response was received from the server.');
                            return;
                        }
                        if (response === true) {
                            $('#plugin-install-message').contents().first().text(successText);
                            $('#plugin-install-message')
                                .find('i')
                                .removeClass('fas fa-cog fa-spin link-secondary')
                                .addClass('fas fa-check');
                            $('#js-install-plugin-ok').removeAttr("disabled");
                        } else {
                            showPluginInstallError((response && response.error) || 'An unknown error occurred.');
                        }
                    }, 200);
                }
            ).fail(function (xhr) {
                var errorMessage;
                try {
                    errorMessage = JSON.parse(xhr.responseText).error;
                } catch (e) {
                    errorMessage = xhr.responseText;
                }
                showPluginInstallError(errorMessage || 'An unknown error occurred.');
            });
        } else if (pluginConfirm === 'OK') {
            $("#install-user-plugin").modal('hide');
        }
    });

    $('#js-uninstall-plugin').on('click', function (e) {
        var button = $('#install-user-plugin').data('button');
        var manifestData = button.data('plugin-manifest');

        $('#uninstall-user-plugin').data('plugin-namespace', manifestData.namespace);
        $('#plugin-uninstall-name').text(manifestData.name);
        $("#install-user-plugin").modal('hide');
        $("#uninstall-user-plugin").modal('show');
    });

    $('#js-uninstall-plugin-confirm').on('click', function (e) {
        var pluginNamespace = $('#uninstall-user-plugin').data('plugin-namespace');
        var successHtml = $('#plugin-uninstall-message').attr('data-message');
        var successText = $('<div>').text(successHtml).text();

        $('#plugin-uninstall-confirm').addClass('d-none');
        $('#plugin-uninstall-message').removeClass('d-none');
        $('#js-uninstall-plugin-cancel, #js-uninstall-plugin-confirm').addClass('d-none');
        $('#js-uninstall-plugin-ok').removeClass('d-none');

        loadAsync('ajax/plugins/do_plugin_uninstall.php', {'plugin_namespace': pluginNamespace})
            .done(function (response) {
                if (response === true) {
                    $('#plugin-uninstall-message').contents().first().replaceWith(successText);
                    $('#plugin-uninstall-message')
                        .find('i')
                        .removeClass('fas fa-cog fa-spin link-secondary')
                        .addClass('fas fa-check');
                    $('#js-uninstall-plugin-ok').removeAttr("disabled");
                } else {
                    showPluginUninstallError((response && response.error) || 'An unknown error occurred.');
                }
            })
            .fail(function (xhr) {
                var errorMessage;
                try {
                    errorMessage = JSON.parse(xhr.responseText).error;
                } catch (e) {
                    errorMessage = xhr.responseText;
                }
                showPluginUninstallError(errorMessage || 'An unknown error occurred.');
            });
    });

    $('#js-uninstall-plugin-ok').on('click', function (e) {
        $("#uninstall-user-plugin").modal('hide');
        window.location.reload();
    });
}