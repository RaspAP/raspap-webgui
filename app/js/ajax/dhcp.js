import { getCSRFToken } from "../helpers.js";
import { setDhcpFieldsDisabled } from "../ui/dhcp.js";

export function initDHCP_ajax() {
    console.info("RaspAP DHCP ajax module initialized");

    $(document).on("click", "#js-cleardnsmasq-log", function(e) {
        var csrfToken = getCSRFToken();
        $.post('ajax/logging/clearlog.php?', {
                'logfile':'/var/log/dnsmasq.log',
                'csrf_token': csrfToken
            }, function(data) {
                let jsonData = JSON.parse(data);
                $("#dnsmasq-log").val("");
            });
    });

    /*
    Populates the DHCP server form fields
    Option toggles are set dynamically depending on the loaded configuration
    */
    function loadInterfaceDHCPSelect() {
        var strInterface = $('#cbxdhcpiface').val();
        var csrfToken = getCSRFToken();
        $.post('ajax/networking/get_netcfg.php', {'iface' : strInterface, 'csrf_token': csrfToken}, function(data) {
            jsonData = JSON.parse(data);
            
            // Static IP fields
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

            // DHCP Fields
            $('#dhcp-iface')[0].checked = jsonData.DHCPEnabled;
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
                $('#chkstatic').prop('checked', true).trigger('change');
            } else {
                $('#chkdhcp').prop('checked', true).trigger('change');
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
    globalThis.loadInterfaceDHCPSelect = loadInterfaceDHCPSelect;

    loadInterfaceDHCPSelect();
}
