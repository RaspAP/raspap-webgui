
function msgShow(retcode,msg) {
    if(retcode == 0) { var alertType = 'success';
    } else if(retcode == 2 || retcode == 1) {
        var alertType = 'danger';
    }
    var htmlMsg = '<div class="alert alert-'+alertType+' alert-dismissible" role="alert"><button type="button" class="btn-close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close"></button>'+msg+'</div>';
    return htmlMsg;
}

function createNetmaskAddr(bitCount) {
  var mask=[];
  for(i=0;i<4;i++) {
    var n = Math.min(bitCount, 8);
    mask.push(256 - Math.pow(2, 8-n));
    bitCount -= n;
  }
  return mask.join('.');
}

function setupTabs() {
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab',function(e){
        var target = $(e.target).attr('href');
        if(!target.match('summary')) {
            var int = target.replace("#","");
        }
    });
}

$(document).on("click", ".js-add-dhcp-static-lease", function(e) {
    e.preventDefault();
    var container = $(".js-new-dhcp-static-lease");
    var mac = $("input[name=mac]", container).val().trim();
    var ip  = $("input[name=ip]", container).val().trim();
    var comment = $("input[name=comment]", container).val().trim();
    if (mac == "" || ip == "") {
        return;
    }
    var row = $("#js-dhcp-static-lease-row").html()
        .replace("{{ mac }}", mac)
        .replace("{{ ip }}", ip)
        .replace("{{ comment }}", comment);
    $(".js-dhcp-static-lease-container").append(row);

    $("input[name=mac]", container).val("");
    $("input[name=ip]", container).val("");
    $("input[name=comment]", container).val("");
});

$(document).on("click", ".js-remove-dhcp-static-lease", function(e) {
    e.preventDefault();
    $(this).parents(".js-dhcp-static-lease-row").remove();
});

$(document).on("submit", ".js-dhcp-settings-form", function(e) {
    $(".js-add-dhcp-static-lease").trigger("click");
});

$(document).on("click", ".js-add-dhcp-upstream-server", function(e) {
    e.preventDefault();

    var field = $("#add-dhcp-upstream-server-field")
    var row = $("#dhcp-upstream-server").html().replace("{{ server }}", field.val())

    if (field.val().trim() == "") { return }

    $(".js-dhcp-upstream-servers").append(row)

    field.val("")
});

$(document).on("click", ".js-remove-dhcp-upstream-server", function(e) {
    e.preventDefault();
    $(this).parents(".js-dhcp-upstream-server").remove();
});

$(document).on("submit", ".js-dhcp-settings-form", function(e) {
    $(".js-add-dhcp-upstream-server").trigger("click");
});

/**
 * mark a form field, e.g. a select box, with the class `.js-field-preset`
 * and give it an attribute `data-field-preset-target` with a text field's
 * css selector.
 *
 * now, if the element marked `.js-field-preset` receives a `change` event,
 * its value will be copied to all elements matching the selector in
 * data-field-preset-target.
 */
$(document).on("change", ".js-field-preset", function(e) {
    var selector = this.getAttribute("data-field-preset-target")
    var value = "" + this.value
    var syncValue = function(el) { el.value = value }

    if (value.trim() === "") { return }

    document.querySelectorAll(selector).forEach(syncValue)
});

$(document).on("click", "#gen_wpa_passphrase", function(e) {
    $('#txtwpapassphrase').val(genPassword(63));
});

$(document).on("click", "#gen_apikey", function(e) {
    $('#txtapikey').val(genPassword(32).toLowerCase());
});

// Enable Bootstrap tooltips
$(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
})

function genPassword(pwdLen) {
    var pwdChars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    var rndPass = Array(pwdLen).fill(pwdChars).map(function(x) { return x[Math.floor(Math.random() * x.length)] }).join('');
    return rndPass;
}

function setupBtns() {
    $('#btnSummaryRefresh').click(function(){getAllInterfaces();});
    $('.intsave').click(function(){
        var int = $(this).data('int');
        saveNetworkSettings(int);
    });
    $('.intapply').click(function(){
        applyNetworkSettings();
    });
}

function setCSRFTokenHeader(event, xhr, settings) {
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    if (/^(POST|PATCH|PUT|DELETE)$/i.test(settings.type)) {
        xhr.setRequestHeader("X-CSRF-Token", csrfToken);
    }
}

function contentLoaded() {
    pageCurrent = window.location.href.split("/").pop();
    switch(pageCurrent) {
        case "network_conf":
            getAllInterfaces();
            setupTabs();
            setupBtns();
            break;
        case "hostapd_conf":
            getChannel();
            setHardwareModeTooltip();
            break;
        case "dhcpd_conf":
            loadInterfaceDHCPSelect();
        break;
    }
}

function setDHCPToggles(state) {
    if ($('#chkfallback').is(':checked') && state) {
        $('#chkfallback').prop('checked', state);
    }
    if ($('#dhcp-iface').is(':checked') && !state) {
        $('#dhcp-iface').prop('checked', state);
        setDhcpFieldsDisabled();
    }
    $('#chkfallback').prop('disabled', state);
    $('#dhcp-iface').prop('disabled', !state);
}

$('#chkfallback').change(function() {
    if ($('#chkfallback').is(':checked')) {
        setStaticFieldsEnabled();
    } else {
        setStaticFieldsDisabled();
    }
});

$('#performupdateModal').on('shown.bs.modal', function (e) {
    fetchUpdateResponse();
});

$('#hostapdModal').on('shown.bs.modal', function (e) {
    var seconds = 3;
    var pct = 0;
    var countDown = setInterval(function(){
      if(seconds <= 0){
        clearInterval(countDown);
      }
      document.getElementsByClassName('progress-bar').item(0).setAttribute('style','width:'+Number(pct)+'%');
      seconds --;
      pct = Math.floor(100-(seconds*100/4));
    }, 500);
});

$('#configureClientModal').on('shown.bs.modal', function (e) {
});

$('#ovpn-confirm-activate').on('shown.bs.modal', function (e) {
    var data = $(e.relatedTarget).data();
    $('.btn-activate', this).data('recordId', data.recordId);
});

$('#ovpn-userpw,#ovpn-certs').on('click', function (e) {
    if (this.id == 'ovpn-userpw') {
        $('#PanelCerts').hide();
        $('#PanelUserPW').show();
    } else if (this.id == 'ovpn-certs') {
        $('#PanelUserPW').hide();
        $('#PanelCerts').show();
    }
});

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

function formatProperty(prop) {
    if (Array.isArray(prop)) {
        if (typeof prop[0] === 'object') {
            return prop.map(item => {
                return Object.entries(item)
                    .map(([key, value]) => `${key}: ${value}`)
                    .join('<br/>');
            }).join('<br/>');
        }
        return prop.map(line => `${line}<br/>`).join('');
    }
    if (typeof prop === 'object') {
        return Object.entries(prop)
            .map(([key, value]) => `${key}: ${value}`)
            .join('<br/>');
    }
    return prop || 'None';
}

$(document).ready(function(){
    $("#PanelManual").hide();
    $('.ip_address').mask('0ZZ.0ZZ.0ZZ.0ZZ', {
        translation: {
            'Z': {
                pattern: /[0-9]/, optional: true
            }
        },
        placeholder: "___.___.___.___"
    });
    $('.mac_address').mask('FF:FF:FF:FF:FF:FF', {
        translation: {
            'F': {
                pattern: /[0-9a-fA-F]/, optional: false
            }
        },
        placeholder: "__:__:__:__:__:__"
    });
});

$(document).ready(function() {
    $('.cidr').mask('099.099.099.099/099', {
        translation: {
            '0': { pattern: /[0-9]/ }
        },
        placeholder: "___.___.___.___/___"
    });
});

$('#wg-upload,#wg-manual').on('click', function (e) {
    if (this.id == 'wg-upload') {
        $('#PanelManual').hide();
        $('#PanelUpload').show();
    } else if (this.id == 'wg-manual') {
        $('#PanelUpload').hide();
        $('#PanelManual').show();
    }
});

$(".custom-file-input").on("change", function() {
  var fileName = $(this).val().split("\\").pop();
  $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});

// Event listener for Bootstrap's form validation
window.addEventListener('load', function() {
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.getElementsByClassName('needs-validation');
    // Loop over them and prevent submission
    var validation = Array.prototype.filter.call(forms, function(form) {
        form.addEventListener('submit', function(event) {
          if (form.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
    });
}, false);

function showSessionExpiredModal() {
    $('#sessionTimeoutModal').modal('show');
}

$(document).on("click", "#js-session-expired-login", function(e) {
    const loginModal = $('#modal-admin-login');
    const redirectUrl = window.location.pathname;
    window.location.href = `/login?action=${encodeURIComponent(redirectUrl)}`;
});

// show modal login on page load
$(document).ready(function () {
    const params = new URLSearchParams(window.location.search);
    const redirectUrl = $('#redirect-url').val() || params.get('action') || '/';
    $('#modal-admin-login').modal('show');
    $('#redirect-url').val(redirectUrl);
    $('#username').focus();
    $('#username').addClass("focusedInput");
});

// DHCP or Static IP option group
$('#chkstatic').on('change', function() {
    if (this.checked) {
        setStaticFieldsEnabled();
    }
});

$('#chkdhcp').on('change', function() {
    this.checked ? setStaticFieldsDisabled() : null;
});


$('input[name="dhcp-iface"]').change(function() {
    if ($('input[name="dhcp-iface"]:checked').val() == '1') {
        setDhcpFieldsEnabled();
    } else {
        setDhcpFieldsDisabled();
    }
});


function setStaticFieldsEnabled() {
    $('#txtipaddress').prop('required', true);
    $('#txtsubnetmask').prop('required', true);
    $('#txtgateway').prop('required', true);

    $('#txtipaddress').removeAttr('disabled');
    $('#txtsubnetmask').removeAttr('disabled');
    $('#txtgateway').removeAttr('disabled');
}

function setStaticFieldsDisabled() {
    $('#txtipaddress').prop('disabled', true);
    $('#txtsubnetmask').prop('disabled', true);
    $('#txtgateway').prop('disabled', true);

    $('#txtipaddress').removeAttr('required');
    $('#txtsubnetmask').removeAttr('required');
    $('#txtgateway').removeAttr('required');
}

function setDhcpFieldsEnabled() {
    $('#txtrangestart').prop('required', true);
    $('#txtrangeend').prop('required', true);
    $('#txtrangeleasetime').prop('required', true);
    $('#cbxrangeleasetimeunits').prop('required', true);

    $('#txtrangestart').removeAttr('disabled');
    $('#txtrangeend').removeAttr('disabled');
    $('#txtrangeleasetime').removeAttr('disabled');
    $('#cbxrangeleasetimeunits').removeAttr('disabled');
    $('#txtdns1').removeAttr('disabled');
    $('#txtdns2').removeAttr('disabled');
    $('#txtmetric').removeAttr('disabled');
}

function setDhcpFieldsDisabled() {
    $('#txtrangestart').removeAttr('required');
    $('#txtrangeend').removeAttr('required');
    $('#txtrangeleasetime').removeAttr('required');
    $('#cbxrangeleasetimeunits').removeAttr('required');

    $('#txtrangestart').prop('disabled', true);
    $('#txtrangeend').prop('disabled', true);
    $('#txtrangeleasetime').prop('disabled', true);
    $('#cbxrangeleasetimeunits').prop('disabled', true);
    $('#txtdns1').prop('disabled', true);
    $('#txtdns2').prop('disabled', true);
    $('#txtmetric').prop('disabled', true);
}

// Static Array method
Array.range = (start, end) => Array.from({length: (end - start)}, (v, k) => k + start);

$(document).on("click", ".js-toggle-password", function(e) {
    var button = $(e.currentTarget);
    var field  = $(button.data("bsTarget"));
    if (field.is(":input")) {
        e.preventDefault();

        if (!button.data("__toggle-with-initial")) {
            $("i", button).removeClass("fas fa-eye").addClass(button.attr("data-toggle-with"));
        }

        if (field.attr("type") === "password") {
            field.attr("type", "text");
        } else {
            $("i", button).removeClass("fas fa-eye-slash").addClass("fas fa-eye");
            field.attr("type", "password");
        }
    }
});

$(function() {
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
});

function set_theme(theme) {
    $('link[title="main"]').attr('href', 'app/css/' + theme);
    // persist selected theme in cookie 
    setCookie('theme',theme,90);
}

$(function() {
    var currentTheme = getCookie('theme');
    // Check if the current theme is a dark theme
    var isDarkTheme = currentTheme === 'dark.css';

    $('#night-mode').prop('checked', isDarkTheme);
    $('#night-mode').change(function() {
        var state = $(this).is(':checked');
        var currentTheme = getCookie('theme');
        
        if (state == true) {
            if (currentTheme == 'custom.php') {
                set_theme('dark.css');
            }
        } else {
            if (currentTheme == 'dark.css') {
                set_theme('custom.php');
            }
        }
   });
});

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var regx = new RegExp(cname + "=([^;]+)");
    var value = regx.exec(document.cookie);
    return (value != null) ? unescape(value[1]) : null;
}

// Define themes
var themes = {
    "default": "custom.php",
    "hackernews" : "hackernews.css"
}

// Adds active class to current nav-item
$(window).bind("load", function() {
    var url = window.location;
    $('.sb-nav-link-icon a').filter(function() {
      return this.href == url;
    }).parent().addClass('active');
});

// Sets focus on a specified tab
document.addEventListener("DOMContentLoaded", function () {
    const params = new URLSearchParams(window.location.search);
    const targetTab = params.get("tab");
    if (targetTab) {
        let tabElement = document.querySelector(`[data-bs-toggle="tab"][href="#${targetTab}"]`);
        if (tabElement) {
            let tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    }
});

function disableValidation(form) {
    form.removeAttribute("novalidate");
    form.classList.remove("needs-validation");
    form.querySelectorAll("[required]").forEach(function (field) {
        field.removeAttribute("required");
    });
}

function updateActivityLED() {
  const threshold_bytes = 300;
  fetch('app/net_activity')
    .then(res => res.text())
    .then(data => {
      const activity = parseInt(data.trim());
      const leds = document.querySelectorAll('.hostapd-led');

      if (!isNaN(activity)) {
        leds.forEach(led => {
          if (activity > threshold_bytes) {
            led.classList.add('led-pulse');
            setTimeout(() => {
              led.classList.remove('led-pulse');
            }, 50);
          } else {
            led.classList.remove('led-pulse');
          }
        });
      }
    })
    .catch(() => { /* ignore fetch errors */ });
}
setInterval(updateActivityLED, 100);

$(document).ready(function() {
    const $htmlElement = $('html');
    const $modeswitch = $('#night-mode');
    $modeswitch.on('change', function() {
        const isChecked = $(this).is(':checked');
        const newTheme = isChecked ? 'dark' : 'light';
        $htmlElement.attr('data-bs-theme', newTheme);
        localStorage.setItem('bsTheme', newTheme);
    });
});

$(document)
    .ajaxSend(setCSRFTokenHeader)
    .ready(contentLoaded)
    .ready(loadWifiStations());

// To auto-close Bootstrap alerts; time is in milliseconds
const alertTimeout = parseInt(getCookie('alert_timeout'), 10);

if (!isNaN(alertTimeout) && alertTimeout > 0) {
  window.setTimeout(function() {
    $(".alert").fadeTo(500, 0).slideUp(500, function(){
      $(this).remove();
    });
  }, alertTimeout);
}

