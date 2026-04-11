import { genPassword } from "../helpers.js";

/* Loads 802.11w select option based on user selected security type.
* WPA3-Personal and WPA2/WPA3-Personal (transitional) require
* specific settings, which are selected automatically.
* Security types without 802.11w support force a disabled state.
*/
export function load80211wSelect() {
    var _80211w_select = $('#cbx80211w');
    var wpa = $('#cbxwpa').val();
    if (wpa === '4') { // WPA2 and WPA3-Personal (transitional)
        _80211w_select.val('1'); // enabled
        _80211w_select.attr('disabled','disabled');
    } else if (wpa === '5') { // WPA3-Personal (required)
        _80211w_select.val('2'); // required
        _80211w_select.attr('disabled','disabled');
    } else if (wpa === 'none' || wpa === '1' || wpa === '3' ) { // unsupported modes
        _80211w_select.val('3'); // disabled
        _80211w_select.attr('disabled','disabled');
    } else {
        _80211w_select.removeAttr('disabled');
    }
}

export function initHostapd() {
    console.info("RaspAP hostapd module initialized");
    load80211wSelect();

    const bridgeCheckbox = document.getElementById('chxbridgedenable');
    const bridgeSection = document.getElementById('bridgeStaticIpSection');
    const staticIpInput = document.getElementById('bridgeStaticIp');
    const netmaskInput = document.getElementById('bridgeNetmask');
    const gatewayInput = document.getElementById('bridgeGateway');
    const dnsInput = document.getElementById('bridgeDNS');
    const previewIp = document.getElementById('previewStaticIp');

    const bridgeInputs = [staticIpInput, netmaskInput, gatewayInput, dnsInput];

    // toggle visibility and required fields
    if (bridgeCheckbox) {
        bridgeCheckbox.addEventListener('change', function() {
        if (this.checked) {
            bridgeSection.style.display = 'block';
            bridgeInputs.forEach(input => input.setAttribute('required', 'required'));
        } else {
            bridgeSection.style.display = 'none';
            bridgeInputs.forEach(input => input.removeAttribute('required'));
        }
        });
    }

    // auto-populate DNS when gateway loses focus
    if (gatewayInput) {
        gatewayInput.addEventListener('blur', function() {
        const gatewayVal = this.value.trim();
        if (gatewayVal !== '' && dnsInput.value.trim() === '') {
            dnsInput.value = gatewayVal;
        }
        });
    }

    $(document).on("click", "#gen_wpa_passphrase", function(e) {
        $('#txtwpapassphrase').val(genPassword(63));
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
}