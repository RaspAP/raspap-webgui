import { formatProperty, set_theme } from "../helpers.js";

export function initSystem() {
    console.info("RaspAP System module initialized");

    $('#install-user-plugin').on('shown.bs.modal', function (e) {
        var button = $(e.relatedTarget);
        $(this).data('button', button);
        var manifestData = button.data('plugin-manifest');
        var installed = button.data('plugin-installed') || false;
        var repoPublic = button.data('repo-public') || false;
        var installPath = manifestData.install_path;

        if (!installed && repoPublic && installPath === 'plugins-available') {
            insidersHTML = 'Available with <i class="fas fa-heart heart me-1"></i><a href="https://docs.raspap.com/insiders" target="_blank" rel="noopener">Insiders</a>';
            $('#plugin-additional').html(insidersHTML);
        } else {
            $('#plugin-additional').empty();
        }
        if (manifestData) {
            $('#plugin-docs').html(manifestData.plugin_docs
                ? `<a href="${manifestData.plugin_docs}" target="_blank">${manifestData.plugin_docs}</a>`
                : 'Unknown');
            $('#plugin-icon').attr('class', `${manifestData.icon || 'fas fa-plug'} link-secondary h5 me-2`);
            $('#plugin-name').text(manifestData.name || 'Unknown');
            $('#plugin-version').text(manifestData.version || 'Unknown');
            $('#plugin-description').text(manifestData.description || 'No description provided');
            $('#plugin-author').html(manifestData.author
                ? manifestData.author + (manifestData.author_uri
                ? ` (<a href="${manifestData.author_uri}" target="_blank">profile</a>)` : '') : 'Unknown');
            $('#plugin-license').text(manifestData.license || 'Unknown');
            $('#plugin-locale').text(manifestData.default_locale || 'Unknown');
            $('#plugin-configuration').html(formatProperty(manifestData.configuration || 'None'));
            $('#plugin-packages').html(formatProperty(manifestData.keys || 'None'));
            $('#plugin-dependencies').html(formatProperty(manifestData.dependencies || 'None'));
            $('#plugin-javascript').html(formatProperty(manifestData.javascript || 'None'));
            $('#plugin-sudoers').html(formatProperty(manifestData.sudoers || 'None'));
            $('#plugin-user-name').html((manifestData.user_nonprivileged && manifestData.user_nonprivileged.name) || 'None');
        }
        if (installed) {
            $('#js-install-plugin-confirm').html('OK');
        } else if (!installed && repoPublic && installPath == 'plugins-available') {
            $('#js-install-plugin-confirm').html('Get Insiders');
        } else {
            $('#js-install-plugin-confirm').html('Install now');
        }
    });

    $('#js-install-plugin-ok').on('click', function (e) {
        $("#install-plugin-progress").modal('hide');
        window.location.reload();
    });

    // Define themes
    var themes = {
        "default": "custom.php",
        "hackernews" : "hackernews.css"
    };

    $('#theme-select').change(function() {
        var theme = themes[$( "#theme-select" ).val() ];

        var hasDarkTheme = theme === 'custom.php';
        var nightModeChecked = $("#night-mode").prop("checked");

        if (nightModeChecked && hasDarkTheme) {
            if (theme === "custom.php") {
                set_theme("dark.css");
            }
        } else {
            set_theme(theme);
        }
   });
}