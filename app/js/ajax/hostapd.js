import { getCSRFToken } from "../helpers.js";
import { load80211wSelect } from "../ui/hostapd.js";

export function initHostapd_ajax() {
    console.info("RaspAP Hostapd ajax module initialized");

    $(document).on("click", "#js-clearhostapd-log", function(e) {
        var csrfToken = getCSRFToken();
        $.post('ajax/logging/clearlog.php?', {
                'logfile':'/tmp/hostapd.log',
                'csrf_token': csrfToken
            }, function(data) {
                let jsonData = JSON.parse(data);
                $("#hostapd-log").val("");
            });
    });

    $('#cbxinterface').on('change', function () {
        const iface = $(this).val();
        const csrfToken = getCSRFToken();
        $.post('ajax/networking/get_hostapd_config.php', {
            interface: iface,
            csrf_token: csrfToken
        }, function (data) {
            if (data.error) {
                return;
            }
            if (data.ssid) $('#txtssid').val(data.ssid);
            if (data.hw_mode) $('#cbxhwmode').val(data.hw_mode);
            if (data.channel) $('#cbxchannel').val(data.channel);
            if (data.wpa) $('#cbxwpa').val(data.wpa);
            if (data.wpa_pairwise) $('#cbxwpapairwise').val(data.wpa_pairwise);
            if (data.country_code) $('#cbxcountries').val(data.country_code);
            if (data.wpa_passphrase) $('#txtwpapassphrase').val(data.wpa_passphrase);

            load80211wSelect();
        });
    });

    /* Sets hardware mode tooltip text for selected interface
    */
    function setHardwareModeTooltip() {
        var iface = $('#cbxinterface').val();
        var hwmodeText = '';
        var csrfToken = getCSRFToken();
        // Explanatory text if 802.11ac is disabled
        if ($('#cbxhwmode').find('option[value="ac"]').prop('disabled') == true ) {
            var hwmodeText = $('#hwmode').attr('data-tooltip');
        }
        $.post('ajax/networking/get_nl80211_band.php?', {
                'interface': iface,
                'csrf_token': csrfToken
            }, function(data) {
                var responseText = JSON.parse(data);
                $('#tiphwmode').attr('data-original-title', responseText + '\n' + hwmodeText );
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
        var csrfToken = getCSRFToken();

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
            } else if (hw_mode === 'ac') {
                selectableChannels = data.filter(item => item.MHz.toString().startsWith('5'));
            } else if (hw_mode === 'ax') {
                selectableChannels = data.filter(item => item.MHz.toString().startsWith('5'));
            } else if (hw_mode === 'be') {
                selectableChannels = data.filter(item => item.MHz.toString().startsWith('5'));
            } else {
                // hw_mode 'b', 'g', or default to 2.4GHz
                selectableChannels = data.filter(item => item.MHz.toString().startsWith('24'));
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

    // Retrieves the 'channel' value specified in hostapd.conf
    function getChannel() {
        var iface = $('#cbxinterface').val();
        var csrfToken = getCSRFToken();

        $.post('ajax/networking/get_channel.php', {
            'interface': iface,
            'csrf_token': csrfToken
        }, function (data) {
            let jsonData;
            try {
                jsonData = typeof data === 'object' ? data : JSON.parse(data);
            } catch (e) {
                return;
            }
            if (jsonData.error) {
                console.warn('Channel error:', jsonData.error);
                loadChannelSelect(null); // fallback
                return;
            }
            loadChannelSelect(jsonData);
        });
    }
    globalThis.getChannel = getChannel;
    
    getChannel();
    setHardwareModeTooltip();
}