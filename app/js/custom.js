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
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/networking/get_ip_summary.php',{'interface': strInterface, 'csrf_token': csrfToken},function(data){
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

$(document).on("click", "#gen_apikey", function(e) {
    $('#txtapikey').val(genPassword(32).toLowerCase());
});

$(document).on("click", "#js-clearhostapd-log", function(e) {
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/logging/clearlog.php?',{'logfile':'/tmp/hostapd.log', 'csrf_token': csrfToken},function(data){
        jsonData = JSON.parse(data);
        $("#hostapd-log").val("");
    });
});

$(document).on("click", "#js-cleardnsmasq-log", function(e) {
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/logging/clearlog.php?',{'logfile':'/var/log/dnsmasq.log', 'csrf_token': csrfToken},function(data){
        jsonData = JSON.parse(data);
        $("#dnsmasq-log").val("");
    });
});

$(document).on("click", "#js-clearopenvpn-log", function(e) {
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/logging/clearlog.php?',{'logfile':'/tmp/openvpn.log', 'csrf_token': csrfToken},function(data){
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
            getChannel();
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
            $('#dhcp-iface').removeAttr('disabled');
        } else {
            $('#chkdhcp').closest('.btn').button('toggle');
            $('#chkdhcp').closest('.btn').button('toggle').blur();
            $('#chkdhcp').blur();
            $('#chkfallback').prop('disabled', false);
        }
        if (jsonData.FallbackEnabled || $('#chkdhcp').is(':checked')) {
            $('#dhcp-iface').prop('disabled', true);
            setDhcpFieldsDisabled();
        }
    });
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

$('#debugModal').on('shown.bs.modal', function (e) {
  var csrfToken = $('meta[name=csrf_token]').attr('content');
  $.post('ajax/system/sys_debug.php',{'csrf_token': csrfToken},function(data){
        window.location.replace('/ajax/system/sys_get_logfile.php');
        $('#debugModal').modal('hide');
    });
});

$('#chkupdateModal').on('shown.bs.modal', function (e) {
  var csrfToken = $('meta[name=csrf_token]').attr('content');
  $.post('ajax/system/sys_chk_update.php',{'csrf_token': csrfToken},function(data){
        var response = JSON.parse(data);
        var tag = response.tag;
        var update = response.update;
        var msg;
        var msgUpdate = $('#msgUpdate').data('message');
        var msgLatest = $('#msgLatest').data('message');
        var msgInstall = $('#msgInstall').data('message');
        var msgDismiss = $('#js-check-dismiss').data('message');
        var faCheck = '<i class="fas fa-check ml-2"></i><br />';
        $("#updateSync").removeClass("fa-spin");
        if (update === true) {
            msg = msgUpdate +' '+tag;
            $("#msg-check-update").html(msg);
            $("#msg-check-update").append(faCheck);
            $("#msg-check-update").append("<p>"+msgInstall+"</p>");
            $("#js-sys-check-update").removeClass("collapse");
        } else {
            msg = msgLatest;
            dismiss = $("#js-check-dismiss");
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
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/system/sys_perform_update.php',{
        'csrf_token': csrfToken
    })
    $('#chkupdateModal').modal('hide');
    $('#performupdateModal').modal('show');
});

$('#performupdateModal').on('shown.bs.modal', function (e) {
    fetchUpdateResponse();
});

function fetchUpdateResponse() {
    const xhr = new XMLHttpRequest();
    const complete = 6;
    const error = 7;
    let phpFile = 'ajax/system/sys_read_logfile.php';
    $.ajax({
        url: phpFile,
        type: 'GET',
        success: function(response) {
            let endPolling = false;
            for (let i = 1; i <= 6; i++) {
                let divId = '#updateStep' + i;
                if (response.includes(i.toString())) {
                    $(divId).removeClass('invisible');
                }
                if (response.includes(complete)) {
                    var successMsg = $('#successMsg').data('message');
                    $('#updateMsg').after('<span class="small">' + successMsg + '</span>');
                    $('#updateMsg').addClass('fa-check');
                    $('#updateMsg').removeClass('invisible');
                    $('#updateStep6').removeClass('invisible');
                    $('#updateSync2').removeClass("fa-spin");
                    $('#updateOk').removeAttr('disabled');
                    endPolling = true;
                    break;
                } else if (response.includes(error)) {
                    var errorMsg = $('#errorMsg').data('message');
                    $('#updateMsg').after('<span class="small">' + errorMsg + '</span>');
                    $('#updateMsg').addClass('fa-times');
                    $('#updateMsg').removeClass('invisible');
                    $('#updateSync2').removeClass("fa-spin");
                    $('#updateOk').removeAttr('disabled');
                    endPolling = true;
                    break;
                }
            }
            if (!endPolling) {
                setTimeout(fetchUpdateResponse, 500);
            }
        },
        error: function(xhr, status, error) {
            console.error(error);
        }
    });
}

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

$('#ovpn-confirm-delete').on('click', '.btn-delete', function (e) {
    var cfg_id = $(this).data('recordId');
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/openvpn/del_ovpncfg.php',{'cfg_id':cfg_id, 'csrf_token': csrfToken},function(data){
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
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/openvpn/activate_ovpncfg.php',{'cfg_id':cfg_id, 'csrf_token': csrfToken},function(data){
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

$('#js-sys-reboot, #js-sys-shutdown').on('click', function (e) {
    e.preventDefault();
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    var action = $(this).data('action');
    $.post('ajax/system/sys_actions.php?',{'a': action, 'csrf_token': csrfToken},function(data){
        var response = JSON.parse(data);
    });
});

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
    $('.date').mask('FF:FF:FF:FF:FF:FF', {
        translation: {
            "F": {
                pattern: /[0-9a-z]/, optional: true
            }
        },
        placeholder: "__:__:__:__:__:__"
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

// Add the following code if you want the name of the file appear on select
$(".custom-file-input").on("change", function() {
  var fileName = $(this).val().split("\\").pop();
  $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});

 // Retrieves the 'channel' value specified in hostapd.conf
function getChannel() {
    $.get('ajax/networking/get_channel.php',function(data){
        jsonData = JSON.parse(data);
        loadChannelSelect(jsonData);
    });
}

/*
 Sets the wirelss channel select options based on frequencies reported by iw.

 See: https://git.kernel.org/pub/scm/linux/kernel/git/sforshee/wireless-regdb.git
 Also: https://en.wikipedia.org/wiki/List_of_WLAN_channels
*/
function loadChannelSelect(selected) {
    var iface = $('#cbxinterface').val();
    var hwmodeText = '';
    var csrfToken = $('meta[name=csrf_token]').attr('content');

    // update hardware mode tooltip
    setHardwareModeTooltip();

    $.post('ajax/networking/get_frequencies.php',{'interface': iface, 'csrf_token': csrfToken, 'selected': selected},function(response){
        var hw_mode = $('#cbxhwmode').val();
        var country_code = $('#cbxcountries').val();
        var channel_select = $('#cbxchannel');
        var btn_save = $('#btnSaveHostapd');
        var data = JSON.parse(response);
        var selectableChannels = [];

        // Map selected hw_mode to available channels
        if (hw_mode === 'a') {
            selectableChannels = data.filter(item => item.MHz.toString().startsWith('5'));
        } else if (hw_mode !== 'ac') {
            selectableChannels = data.filter(item => item.MHz.toString().startsWith('24'));
        } else if (hw_mode === 'b') {
            selectableChannels = data.filter(item => item.MHz.toString().startsWith('24'));
        } else if (hw_mode === 'ac') {
            selectableChannels = data.filter(item => item.MHz.toString().startsWith('5'));
        }

        // If selected channel doeesn't exist in allowed channels, set default or null (unsupported)
        if (!selectableChannels.find(item => item.Channel === selected)) {
            if (selectableChannels.length === 0) {
                selectableChannels[0] = { Channel: null };
            } else {
                defaultChannel = selectableChannels[0].Channel;
                selected = defaultChannel
            }
        }

        // Set channel select with available values
        channel_select.empty();
        if (selectableChannels[0].Channel === null) {
            channel_select.append($("<option></option>").attr("value", "").text("---"));
            channel_select.prop("disabled", true);
            btn_save.prop("disabled", true);
        } else {
            channel_select.prop("disabled", false);
            btn_save.prop("disabled", false);
            $.each(selectableChannels, function(key,value) {
                channel_select.append($("<option></option>").attr("value", value.Channel).text(value.Channel));
            });
            channel_select.val(selected);
        }
    });
}

/* Sets hardware mode tooltip text for selected interface
 * and calls loadChannelSelect()
 */
function setHardwareModeTooltip() {
    var iface = $('#cbxinterface').val();
    var hwmodeText = '';
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    // Explanatory text if 802.11ac is disabled
    if ($('#cbxhwmode').find('option[value="ac"]').prop('disabled') == true ) {
        var hwmodeText = $('#hwmode').attr('data-tooltip');
    }
    $.post('ajax/networking/get_nl80211_band.php?',{'interface': iface, 'csrf_token': csrfToken},function(data){
        var responseText = JSON.parse(data);
        $('#tiphwmode').attr('data-original-title', responseText + '\n' + hwmodeText );
    });
}

/* Updates the selected blocklist
 * Request is passed to an ajax handler to download the associated list.
 * Interface elements are updated to indicate current progress, status.
 */
function updateBlocklist() {
    var opt = $('#cbxblocklist option:selected');
    var blocklist_id = opt.val();
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    if (blocklist_id == '') { return; }
    $('#cbxblocklist-status').find('i').removeClass('fas fa-check').addClass('fas fa-cog fa-spin');
    $('#cbxblocklist-status').removeClass('check-hidden').addClass('check-progress');
    $.post('ajax/adblock/update_blocklist.php',{ 'blocklist_id':blocklist_id, 'csrf_token': csrfToken},function(data){
        var jsonData = JSON.parse(data);
        if (jsonData['return'] == '0') {
            $('#cbxblocklist-status').find('i').removeClass('fas fa-cog fa-spin').addClass('fas fa-check');
            $('#cbxblocklist-status').removeClass('check-progress').addClass('check-updated').delay(500).animate({ opacity: 1 }, 700);
            $('#blocklist-'+jsonData['list']).text("Just now");
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
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/networking/get_wgkey.php',{'entity':entity_pub.attr('name'), 'csrf_token': csrfToken},function(data){
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
    var isDarkTheme = currentTheme === 'lightsout.php' || currentTheme === 'material-dark.php';

    $('#night-mode').prop('checked', isDarkTheme);
    $('#night-mode').change(function() {
        var state = $(this).is(':checked');
        var currentTheme = getCookie('theme');
        
        if (state == true) {
            if (currentTheme == 'custom.php') {
                set_theme('lightsout.php');
            } else if (currentTheme == 'material-light.php') {
                set_theme('material-dark.php');
            }
        } else {
            if (currentTheme == 'lightsout.php') {
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
    "lightsout" : "lightsout.php",
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
