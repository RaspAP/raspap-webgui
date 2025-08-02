
function loadSummary(strInterface) {
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/networking/get_ip_summary.php',{'interface': strInterface, 'csrf_token': csrfToken},function(data){
        jsonData = JSON.parse(data);
        if(jsonData['return'] == 0) {
            $('#'+strInterface+'-summary').html(jsonData['output'].join('<br />'));
        } else if(jsonData['return'] == 2) {
            $('#'+strInterface+'-summary').append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'+jsonData['output'].join('<br />')+'</div>');
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
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/networking/get_netcfg.php', {'iface' : strInterface, 'csrf_token': csrfToken}, function(data){
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
            $('#chkstatic').prop('checked', true).closest('.btn').addClass('active');
            $('#chkdhcp').prop('checked', false).closest('.btn').removeClass('active');
            $('#chkfallback').prop('disabled', true);
            $('#dhcp-iface').removeAttr('disabled');
        } else {
            $('#chkdhcp').closest('.btn').addClass('active');
            $('#chkdhcp').closest('.btn').blur();
        }
        if (jsonData.FallbackEnabled || $('#chkdhcp').is(':checked')) {
            $('#dhcp-iface').prop('disabled', true);
            setDhcpFieldsDisabled();
        }

        const leaseContainer = $('.js-dhcp-static-lease-container');
        leaseContainer.empty();

        if (jsonData.dhcpHost && jsonData.dhcpHost.length > 0) {
            const leases = jsonData.dhcpHost || [];
                leases.forEach((entry, index) => {
                const [mainPart, commentPart] = entry.split('#');
                const comment = commentPart ? commentPart.trim() : '';
                const [mac, ip] = mainPart.split(',').map(part => part.trim());
                const row = `
                <div class="row dhcp-static-lease-row js-dhcp-static-lease-row">
                    <div class="col-md-4 col-xs-3">
                        <input type="text" name="static_leases[mac][]" value="${mac}" placeholder="MAC address" class="form-control">
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <input type="text" name="static_leases[ip][]" value="${ip}" placeholder="IP address" class="form-control">
                    </div>
                    <div class="col-md-3 col-xs-3">
                        <input type="text" name="static_leases[comment][]" value="${comment || ''}" placeholder="Optional comment" class="form-control">
                    </div>
                    <div class="col-md-2 col-xs-3">
                        <button type="button" class="btn btn-outline-danger js-remove-dhcp-static-lease"><i class="far fa-trash-alt"></i></button>
                    </div>
                </div>`;
                leaseContainer.append(row);
            });
        }
    });
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
        var faCheck = '<i class="fas fa-check ms-2"></i><br />';
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

function fetchUpdateResponse() {
    const complete = 6;
    const error = 7;
    let phpFile = 'ajax/system/sys_read_logfile.php';

    $.ajax({
        url: phpFile,
        type: 'GET',
        success: function(response) { 
            for (let i = 1; i <= 6; i++) {
                let divId = '#updateStep' + i;
                if (response.includes(i.toString())) {
                    $(divId).removeClass('invisible');
                }
            }
            // check if the update is complete or if there's an error
            if (response.includes(complete)) {
                var successMsg = $('#successMsg').data('message');
                $('#updateMsg').after('<span class="small">' + successMsg + '</span>');
                $('#updateMsg').addClass('fa-check');
                $('#updateMsg').removeClass('invisible');
                $('#updateStep6').removeClass('invisible');
                $('#updateSync2').removeClass("fa-spin");
                $('#updateOk').removeAttr('disabled');
            } else if (response.includes(error)) {
                var errorMsg = $('#errorMsg').data('message');
                $('#updateMsg').after('<span class="small">' + errorMsg + '</span>');
                $('#updateMsg').addClass('fa-times');
                $('#updateMsg').removeClass('invisible');
                $('#updateSync2').removeClass("fa-spin");
                $('#updateOk').removeAttr('disabled');
            } else {
                setTimeout(fetchUpdateResponse, 500);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", error);
        }
    });
}

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

$('#js-system-reset-confirm').on('click', function (e) {
    var progressText = $('#js-system-reset-confirm').attr('data-message');
    var successHtml = $('#system-reset-message').attr('data-message');
    var closeHtml = $('#js-system-reset-cancel').attr('data-message');
    var csrfToken = $('meta[name=csrf_token]').attr('content');
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

$('#js-sys-reboot, #js-sys-shutdown').on('click', function (e) {
    e.preventDefault();
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    var action = $(this).data('action');
    $.post('ajax/system/sys_actions.php?',{'a': action, 'csrf_token': csrfToken},function(data){
        var response = JSON.parse(data);
    });
});

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
    var csrfToken = $('meta[name=csrf_token]').attr('content');

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
    const opt = $('#cbxblocklist option:selected');
    const blocklist_id = opt.val();
    const csrfToken = $('meta[name=csrf_token]').attr('content');

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

function clearBlocklistStatus() {
    $('#cbxblocklist-status').removeClass('check-updated').addClass('check-hidden');
}

// Handler for the WireGuard generate key button
$('.wg-keygen').click(function(){
    var parentGroup = $(this).closest('.input-group');
    var entity_pub = parentGroup.find('input[type="text"]');
    var updated = entity_pub.attr('name')+"-pubkey-status";
    var csrfToken = $('meta[name="csrf_token"]').attr('content');
    $.post('ajax/networking/get_wgkey.php',{'entity':entity_pub.attr('name'), 'csrf_token': csrfToken},function(data){
        var jsonData = JSON.parse(data);
        entity_pub.val(jsonData.pubkey);
        $('#' + updated).removeClass('check-hidden').addClass('check-updated').delay(500).animate({ opacity: 1 }, 700);
    });
});

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

let sessionCheckInterval = setInterval(checkSession, 5000);

function checkSession() {
    // skip session check if on login page
    if (window.location.pathname === '/login') {
        return;
    }
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    $.post('ajax/session/do_check_session.php',{'csrf_token': csrfToken},function (data) {
        if (data.status === 'session_expired') {
            clearInterval(sessionCheckInterval);
            showSessionExpiredModal();
        }
    }).fail(function (jqXHR, status, err) {
        console.error("Error checking session status:", status, err);
    });
}

