function msgShow(retcode,msg) {
    if(retcode == 0) { var alertType = 'success';
    } else if(retcode == 2 || retcode == 1) {
        var alertType = 'danger';
    }
    var htmlMsg = '<div class="alert alert-'+alertType+' alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+msg+'</div>';
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

function loadSummary(strInterface) {
    $.post('ajax/networking/get_ip_summary.php',{interface:strInterface},function(data){
        jsonData = JSON.parse(data);
        if(jsonData['return'] == 0) {
            $('#'+strInterface+'-summary').html(jsonData['output'].join('<br />'));
        } else if(jsonData['return'] == 2) {
            $('#'+strInterface+'-summary').append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+jsonData['output'].join('<br />')+'</div>');
        }
    });
}

function getAllInterfaces() {
    $.get('ajax/networking/get_all_interfaces.php',function(data){
        jsonData = JSON.parse(data);
        $.each(jsonData,function(ind,value){
            loadSummary(value)
        });
    });
}

function setupTabs() {
    $('a[data-toggle="tab"]').on('shown.bs.tab',function(e){
        var target = $(e.target).attr('href');
        if(!target.match('summary')) {
            var int = target.replace("#","");
            loadCurrentSettings(int);
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

$(document).on("click", "#js-clearhostapd-log", function(e) {
    $.post('ajax/logging/clearlog.php?',{'logfile':'/tmp/hostapd.log'},function(data){
        jsonData = JSON.parse(data);
        $("#hostapd-log").val("");
    });
});

$(document).on("click", "#js-cleardnsmasq-log", function(e) {
    $.post('ajax/logging/clearlog.php?',{'logfile':'/var/log/dnsmasq.log'},function(data){
        jsonData = JSON.parse(data);
        $("#dnsmasq-log").val("");
    });
});

$(document).on("click", "#js-clearopenvpn-log", function(e) {
    $.post('ajax/logging/clearlog.php?',{'logfile':'/tmp/openvpn.log'},function(data){
        jsonData = JSON.parse(data);
        $("#openvpn-log").val("");
    });
});


// Enable Bootstrap tooltips
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
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
            loadChannel();
            setHardwareModeTooltip();
            break;
        case "dhcpd_conf":
            loadInterfaceDHCPSelect();
        break;
    }
}

function loadWifiStations(refresh) {
    return function() {
        var complete = function() { $(this).removeClass('loading-spinner'); }
        var qs = refresh === true ? '?refresh' : '';
        $('.js-wifi-stations')
            .addClass('loading-spinner')
            .empty()
            .load('ajax/networking/wifi_stations.php'+qs, complete);
    };
}
$(".js-reload-wifi-stations").on("click", loadWifiStations(true));

/*
Populates the DHCP server form fields
Option toggles are set dynamically depending on the loaded configuration
*/
function loadInterfaceDHCPSelect() {
    var strInterface = $('#cbxdhcpiface').val();
    $.get('ajax/networking/get_netcfg.php?iface='+strInterface,function(data){
        jsonData = JSON.parse(data);
        $('#dhcp-iface')[0].checked = jsonData.DHCPEnabled;
        $('#txtipaddress').val(jsonData.StaticIP);
        $('#txtsubnetmask').val(jsonData.SubnetMask);
        $('#txtgateway').val(jsonData.StaticRouters);
        $('#chkfallback')[0].checked = jsonData.FallbackEnabled;
        $('#default-route').prop('checked', jsonData.DefaultRoute);
        if (strInterface.startsWith("wl")) {
            $('#nohook-wpa-supplicant').parent().parent().parent().show()
            $('#nohook-wpa-supplicant').prop('checked', jsonData.NoHookWPASupplicant);
        } else {
            $('#nohook-wpa-supplicant').parent().parent().parent().hide()
        }
        $('#txtrangestart').val(jsonData.RangeStart);
        $('#txtrangeend').val(jsonData.RangeEnd);
        $('#txtrangeleasetime').val(jsonData.leaseTime);
        $('#txtdns1').val(jsonData.DNS1);
        $('#txtdns2').val(jsonData.DNS2);
        $('#cbxrangeleasetimeunits').val(jsonData.leaseTimeInterval);
        $('#no-resolv')[0].checked = jsonData.upstreamServersEnabled;
        $('#cbxdhcpupstreamserver').val(jsonData.upstreamServers[0]);
        $('#txtmetric').val(jsonData.Metric);

        if (jsonData.StaticIP !== null && jsonData.StaticIP !== '' && !jsonData.FallbackEnabled) {
            $('#chkstatic').closest('.btn').button('toggle');
            $('#chkstatic').closest('.btn').button('toggle').blur();
            $('#chkstatic').blur();
            $('#chkfallback').prop('disabled', true);
        } else {
            $('#chkdhcp').closest('.btn').button('toggle');
            $('#chkdhcp').closest('.btn').button('toggle').blur();
            $('#chkdhcp').blur();
            $('#chkfallback').prop('disabled', false);
        }
        if (jsonData.FallbackEnabled || $('#chkdhcp').is(':checked')) {
            $('#dhcp-iface').prop('disabled', true);
        }
    });
}

function setDHCPToggles(state) {
    if ($('#chkfallback').is(':checked') && state) {
        $('#chkfallback').prop('checked', state);
    }
    if ($('#dhcp-iface').is(':checked') && !state) {
        $('#dhcp-iface').prop('checked', state);
    }

    $('#chkfallback').prop('disabled', state);
    $('#dhcp-iface').prop('disabled', !state);
    //$('#dhcp-iface').prop('checked', state);
}

function loadChannel() {
    $.get('ajax/networking/get_channel.php',function(data){
        jsonData = JSON.parse(data);
        loadChannelSelect(jsonData);
    });
}

$('#hostapdModal').on('shown.bs.modal', function (e) {
    var seconds = 9;
    var countDown = setInterval(function(){
      if(seconds <= 0){
        clearInterval(countDown);
      }
      var pct = Math.floor(100-(seconds*100/9));
      document.getElementsByClassName('progress-bar').item(0).setAttribute('style','width:'+Number(pct)+'%');
      seconds --;
    }, 1000);
});

$('#configureClientModal').on('shown.bs.modal', function (e) {
});

$('#ovpn-confirm-delete').on('click', '.btn-delete', function (e) {
    var cfg_id = $(this).data('recordId');
    $.post('ajax/openvpn/del_ovpncfg.php',{'cfg_id':cfg_id},function(data){
        jsonData = JSON.parse(data);
        $("#ovpn-confirm-delete").modal('hide');
        var row = $(document.getElementById("openvpn-client-row-" + cfg_id));
        row.fadeOut( "slow", function() {
            row.remove();
        });
    });
});

$('#ovpn-confirm-delete').on('show.bs.modal', function (e) {
    var data = $(e.relatedTarget).data();
    $('.btn-delete', this).data('recordId', data.recordId);
});

$('#ovpn-confirm-activate').on('click', '.btn-activate', function (e) {
    var cfg_id = $(this).data('record-id');
    $.post('ajax/openvpn/activate_ovpncfg.php',{'cfg_id':cfg_id},function(data){
        jsonData = JSON.parse(data);
        $("#ovpn-confirm-activate").modal('hide');
        setTimeout(function(){
            window.location.reload();
        },300);
    });
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

$('#js-system-reset-confirm').on('click', function (e) {
    var progressText = $('#js-system-reset-confirm').attr('data-message');
    var successHtml = $('#system-reset-message').attr('data-message');
    var closeHtml = $('#js-system-reset-cancel').attr('data-message');
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    var progressHtml = $('<div>').text(progressText).html() + '<i class="fas fa-cog fa-spin ml-2"></i>';
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

$(document).ready(function(){
  $("#PanelManual").hide();
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

// Add the following code if you want the name of the file appear on select
$(".custom-file-input").on("change", function() {
  var fileName = $(this).val().split("\\").pop();
  $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});

/*
Sets the wirelss channel select options based on hw_mode and country_code.

Methodology: In North America up to channel 11 is the maximum allowed WiFi 2.4Ghz channel,
except for the US that allows channel 12 & 13 in low power mode with additional restrictions.
Canada allows channel 12 in low power mode. Because it's unsure if low powered mode can be
supported the channels are not selectable for those countries. Also Uzbekistan and Colombia
allow up to channel 11 as maximum channel on the 2.4Ghz WiFi band.
Source: https://en.wikipedia.org/wiki/List_of_WLAN_channels
Additional: https://git.kernel.org/pub/scm/linux/kernel/git/sforshee/wireless-regdb.git
*/
function loadChannelSelect(selected) {
    // Fetch wireless regulatory data
    $.getJSON("config/wireless.json", function(json) {
        var hw_mode = $('#cbxhwmode').val();
        var country_code = $('#cbxcountries').val();
        var channel_select = $('#cbxchannel');
        var data = json["wireless_regdb"];
        var selectablechannels = Array.range(1,14);

        // Assign array of countries to valid frequencies (channels)
        var countries_2_4Ghz_max11ch = data["2_4GHz_max11ch"].countries;
        var countries_2_4Ghz_max14ch = data["2_4GHz_max14ch"].countries;
        var countries_5Ghz_max48ch = data["5Ghz_max48ch"].countries;

        // Map selected hw_mode and country to determine channel list
        if (hw_mode === 'a') {
            selectablechannels = data["5Ghz_max48ch"].channels;
        } else if (($.inArray(country_code, countries_2_4Ghz_max11ch) !== -1) && (hw_mode !== 'ac') ) {
            selectablechannels = data["2_4GHz_max11ch"].channels;
        } else if (($.inArray(country_code, countries_2_4Ghz_max14ch) !== -1) && (hw_mode === 'b')) {
            selectablechannels = data["2_4GHz_max14ch"].channels;
        } else if (($.inArray(country_code, countries_5Ghz_max48ch) !== -1) && (hw_mode === 'ac')) {
            selectablechannels = data["5Ghz_max48ch"].channels;
        }

        // Set channel select with available values
        selected = (typeof selected === 'undefined') ? selectablechannels[0] : selected;
        channel_select.empty();
        $.each(selectablechannels, function(key,value) {
            channel_select.append($("<option></option>").attr("value", value).text(value));
        });
        channel_select.val(selected);
    });
}

/* Sets hardware mode tooltip text for selected interface.
 */
function setHardwareModeTooltip() {
    var iface = $('#cbxinterface').val();
    var hwmodeText = '';
    // Explanatory text if 802.11ac is disabled
    if ($('#cbxhwmode').find('option[value="ac"]').prop('disabled') == true ) {
        var hwmodeText = $('#hwmode').attr('data-tooltip');
    }
    $.post('ajax/networking/get_frequencies.php?',{'interface': iface},function(data){
        var responseText = JSON.parse(data);
        $('#tiphwmode').attr('data-original-title', responseText + '\n' + hwmodeText );
    });
}

/* Updates the selected blocklist
 * Request is passed to an ajax handler to download the associated list.
 * Interface elements are updated to indicate current progress, status.
 */
function updateBlocklist() {
    var blocklist_id = $('#cbxblocklist').val();
    if (blocklist_id == '') { return; }
    $('#cbxblocklist-status').find('i').removeClass('fas fa-check').addClass('fas fa-cog fa-spin');
    $('#cbxblocklist-status').removeClass('check-hidden').addClass('check-progress');
    $.post('ajax/adblock/update_blocklist.php',{ 'blocklist_id':blocklist_id },function(data){
        var jsonData = JSON.parse(data);
        if (jsonData['return'] == '0') {
            $('#cbxblocklist-status').find('i').removeClass('fas fa-cog fa-spin').addClass('fas fa-check');
            $('#cbxblocklist-status').removeClass('check-progress').addClass('check-updated').delay(500).animate({ opacity: 1 }, 700);
            $('#'+blocklist_id).text("Just now");
        }
    })
}

function clearBlocklistStatus() {
    $('#cbxblocklist-status').removeClass('check-updated').addClass('check-hidden');
}

// Handler for the wireguard generate key button
$('.wg-keygen').click(function(){
    var entity_pub = $(this).parent('div').prev('input[type="text"]');
    var entity_priv = $(this).parent('div').next('input[type="hidden"]');
    var updated = entity_pub.attr('name')+"-pubkey-status";
    $.post('ajax/networking/get_wgkey.php',{'entity':entity_pub.attr('name') },function(data){
        var jsonData = JSON.parse(data);
        entity_pub.val(jsonData.pubkey);
        $('#' + updated).removeClass('check-hidden').addClass('check-updated').delay(500).animate({ opacity: 1 }, 700);
    })
})

// Handler for wireguard client.conf download
$('.wg-client-dl').click(function(){
    var req = new XMLHttpRequest();
    var url = 'ajax/networking/get_wgcfg.php';
    req.open('get', url, true);
    req.responseType = 'blob';
    req.setRequestHeader('Content-type', 'text/plain; charset=UTF-8');
    req.onreadystatechange = function (event) {
        if(req.readyState == 4 && req.status == 200) {
            var blob = req.response;
            var link=document.createElement('a');
            link.href=window.URL.createObjectURL(blob);
            link.download = 'client.conf';
            link.click();
        }
    }
    req.send();
})

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

// Static Array method
Array.range = (start, end) => Array.from({length: (end - start)}, (v, k) => k + start);

$(document).on("click", ".js-toggle-password", function(e) {
    var button = $(e.target)
    var field  = $(button.data("target"));

    if (field.is(":input")) {
        e.preventDefault();

        if (!button.data("__toggle-with-initial")) {
            $("i", this).removeClass("fas fa-eye").addClass(button.attr("data-toggle-with"));
        }

        if (field.attr("type") === "password") {
            field.attr("type", "text");
        } else {
            $("i", this).removeClass("fas fa-eye-slash").addClass("fas fa-eye");
            field.attr("type", "password");
        }
    }
});

$(function() {
    $('#theme-select').change(function() {
        var theme = themes[$( "#theme-select" ).val() ]; 
        set_theme(theme);
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
    var isDarkTheme = currentTheme === 'lightsout.css' || currentTheme === 'material-dark.php';

    $('#night-mode').prop('checked', isDarkTheme);
    $('#night-mode').change(function() {
        var state = $(this).is(':checked');
        var currentTheme = getCookie('theme');
        
        if (state == true) {
            if (currentTheme == 'custom.php') {
                set_theme('lightsout.css');
            } else if (currentTheme == 'material-light.php') {
                set_theme('material-dark.php');
            }
        } else {
            if (currentTheme == 'lightsout.css') {
                set_theme('custom.php');
            } else if (currentTheme == 'material-dark.php') {
                set_theme('material-light.php');
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
    "hackernews" : "hackernews.css",
    "lightsout" : "lightsout.css",
    "material-light" : "material-light.php",
    "material-dark" : "material-dark.php",
}

// Toggles the sidebar navigation.
// Overrides the default SB Admin 2 behavior
$("#sidebarToggleTopbar").on('click', function(e) {
    $("body").toggleClass("sidebar-toggled");
    $(".sidebar").toggleClass("toggled d-none");
});

// Overrides SB Admin 2
$("#sidebarToggle, #sidebarToggleTop").on('click', function(e) {
    var toggled = $(".sidebar").hasClass("toggled");
    // Persist state in cookie
    setCookie('sidebarToggled',toggled, 90);
});

$(function() {
    if ($(window).width() < 768) {
        $('.sidebar').addClass('toggled');
        setCookie('sidebarToggled',false, 90);
    }
});

$(window).on("load resize",function(e) {
    if ($(window).width() > 768) {
        $('.sidebar').removeClass('d-none d-md-block');
        if (getCookie('sidebarToggled') == 'false') {
            $('.sidebar').removeClass('toggled');
        }
    }
});

// Adds active class to current nav-item
$(window).bind("load", function() {
    var url = window.location;
    $('ul.navbar-nav a').filter(function() {
      return this.href == url;
    }).parent().addClass('active');
});

$(document)
    .ajaxSend(setCSRFTokenHeader)
    .ready(contentLoaded)
    .ready(loadWifiStations());
