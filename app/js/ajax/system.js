import { getCSRFToken } from "../helpers.js";

export function initSystem_ajax() {
    console.info("RaspAP System ajax module initialized");
    
    $('#js-sys-reboot, #js-sys-shutdown').on('click', function (e) {
        e.preventDefault();
        var csrfToken = getCSRFToken();
        var action = $(this).data('action');
        $.post('ajax/system/sys_actions.php?', {
                'a': action,
                'csrf_token': csrfToken
            }, function(data) {
                var response = JSON.parse(data);

                if (action === 'reboot') {
                    const reconnectModalEl = $('#system-reconnect');
                    const modal = new bootstrap.Modal(reconnectModalEl);
                    modal.show();
                    handleReconnect(reconnectModalEl);
                }
            });
    });

    function handleReconnect(modal) {
        const secondsEl = $(modal).find('#system-reconnect-seconds');
        
        let countdownInterval;
        const attemptSeconds = 20;

        const attemptReconnect = async () => {
            console.log('attempting reconnect');
            $(secondsEl).text('...');

            const startCountdown = (e) => {
                console.log('still rebooting - start countdown', e);
                clearInterval(countdownInterval);
                let countdownInt = attemptSeconds;
                countdownInterval = setInterval(() => {
                    console.log('decrement countdownInt');
                    if (countdownInt === 0) attemptReconnect();
                    $(secondsEl).text(countdownInt);
                    if (countdownInt > 0) countdownInt--;
                }, 1000);
            }

            try {
                // fetch to url and get status
                const checkUrl = window.location.origin;
                const response = await fetch(checkUrl);

                if (response.status === 200) {
                    console.log('reconnected - reload the page');
                    window.location.reload();
                } else {
                    startCountdown();
                }
            } catch (e) {
                startCountdown(e);
            }
        };

        attemptReconnect();
    }

    $('#js-system-reset-confirm').on('click', function (e) {
        var progressText = $('#js-system-reset-confirm').attr('data-message');
        var successHtml = $('#system-reset-message').attr('data-message');
        var closeHtml = $('#js-system-reset-cancel').attr('data-message');
        var csrfToken = getCSRFToken();
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

    $('#debugModal').on('shown.bs.modal', function (e) {
        var csrfToken = getCSRFToken();
        $.post('ajax/system/sys_debug.php',{'csrf_token': csrfToken},function(data){
            window.location.replace('/ajax/system/sys_get_logfile.php');
            $('#debugModal').modal('hide');
        });
    });

    function inspectInterface() {
        var iface = $('#cbxadpateriface').val();
        var csrfToken = getCSRFToken();

        const udevAllowlist = [
        'ID_OUI_FROM_DATABASE',
        'ID_VENDOR_FROM_DATABASE',
        'ID_VENDOR',
        'ID_MODEL',
        'ID_NET_NAME_MAC',
        'ID_PATH',
        'ID_BUS',
        'ID_USB_SERIAL_SHORT',
        ];

        const driverAllowlist = [
        'driver',
        'version',
        'firmware-version',
        'supports-statistics',
        ];

        const keyLabels = {
        // udev
        'ID_VENDOR_FROM_DATABASE': 'Vendor (Database)',
        'ID_VENDOR': 'Vendor',
        'ID_VENDOR_ID': 'Vendor ID',
        'ID_MODEL': 'Model',
        'ID_MODEL_ID': 'Model ID',
        'ID_USB_SERIAL_SHORT': 'USB Serial',
        'ID_NET_NAME_MAC': 'MAC Device Name',
        'ID_PATH': 'Device Path',
        'ID_BUS': 'Bus Type',
        'ID_OUI_FROM_DATABASE': 'Manufacturer (OUI)',

        // driver
        'driver': 'Kernel Driver',
        'version': 'Driver Version',
        'firmware-version': 'Firmware Version',
        'supports-statistics': 'Supports Statistics',
        };

        $.post('ajax/networking/get_adapter.php', {
            'interface': iface,
            'csrf_token': csrfToken
        }, function (data) {
            let jsonData;
            try {
                jsonData = typeof data === 'object' ? data : JSON.parse(data);
            } catch (e) {
                console.warn('Invalid JSON:', e);
                return;
            }

            if (jsonData.error) {
                console.warn('Error occurred:', jsonData.error);
                return;
            }

            const tableBody = $('#adaptersTableBody');
            tableBody.empty();

            function addRow(label, value) {
                tableBody.append(`<tr><td><strong>${label}</strong></td><td>${value}</td></tr>`);
            }

            addRow('Interface', jsonData.interface);
            if (jsonData.udev) {
                for (const [key, value] of Object.entries(jsonData.udev)) {
                    if (udevAllowlist.includes(key)) {
                        addRow(keyLabels[key] || key, value);
                    }
                }
            }
            if (jsonData.driver) {
                for (const [key, value] of Object.entries(jsonData.driver)) {
                    if (driverAllowlist.includes(key)) {
                        addRow(keyLabels[key] || key, value);
                    }
                }
            }
            if (jsonData.capabilities?.supported_modes) {
                const modes = jsonData.capabilities.supported_modes.split(',').map(m => m.trim());
                const badges = modes.map(mode => `<span class="badge bg-secondary me-1">${mode}</span>`).join('');
                addRow('Supported Modes', badges);
            }

            const buttonsContainer = $('#adaptersButtons');
            buttonsContainer.empty();

            if (jsonData.interface_up) {
                buttonsContainer.append(`<button class="btn btn-danger" onclick="setAdapterState('down')">Adapter Down</button>`);
                buttonsContainer.append(`<button class="btn btn-warning" onclick="setAdapterState('cycle')">Adapter Cycle</button>`);
            } else {
                buttonsContainer.append(`<button class="btn btn-success" onclick="setAdapterState('up')">Adapter Up</button>`);
            }
        });
    }
    globalThis.inspectInterface = inspectInterface;

    function setAdapterState(state) {
        var iface = $('#cbxadpateriface').val();
        var csrfToken = $('meta[name=csrf_token]').attr('content');

        const buttonsContainer = $('#adaptersButtons');
        buttonsContainer.empty();

        buttonsContainer.append(`<button class="btn" disabled><i class="fas fa-cog fa-spin"></i> ${state === 'cycle' ? 'Cycling Adapter' : `Bringing Adapter ${state}`}</button>`);

        $.post('ajax/networking/set_adapter_state.php', {
            'interface': iface,
            'state': state,
            'csrf_token': csrfToken
        }, function (data) {
            let jsonData;
            try {
                jsonData = typeof data === 'object' ? data : JSON.parse(data);
            } catch (e) {
                console.warn('Invalid JSON:', e);
                return;
            }

            if (jsonData.error) {
                console.warn('Error occurred:', jsonData.error);
                return;
            }

            buttonsContainer.empty();

            if (jsonData.interface_up) {
                buttonsContainer.append(`<button class="btn btn-danger" onclick="setAdapterState('down')">Adapter Down</button>`);
                buttonsContainer.append(`<button class="btn btn-warning" onclick="setAdapterState('cycle')">Adapter Cycle</button>`);
            } else {
                buttonsContainer.append(`<button class="btn btn-success" onclick="setAdapterState('up')">Adapter Up</button>`);
            }
        });
    }
    globalThis.setAdapterState = setAdapterState;

    function testAdapter(iface) {
        var iface = $('#cbxadpateriface').val();
        const csrfToken = getCSRFToken();
        const statusIcon = $('#healthcheck-status i');

        statusIcon.addClass('fa-cog fa-spin');
        statusIcon
            .removeClass('fa-check fa-times')
            .addClass('fa-cog fa-spin text-secondary')
            .closest('#healthcheck-status')
            .removeClass('check-hidden');

        $.post('ajax/networking/test_adapter.php', {
            interface: iface,
            csrf_token: csrfToken
        }, function (data) {
            let json;
            try {
            json = typeof data === 'object' ? data : JSON.parse(data);
            } catch (e) {
            console.error('Invalid response', e);
            return;
            }

            if (json.error) {
            console.warn('Test error:', json.error);
            statusIcon.removeClass('fa-cog fa-spin text-secondary').addClass('fa-times text-danger');
            return;
            }

            statusIcon.removeClass('fa-cog fa-spin text-secondary').addClass('fa-check text-success');
            renderHealthCheck(json);
        });
    }
    globalThis.testAdapter = testAdapter;

    function renderHealthCheck(data) {
        const checks = [
            { key: 'interface_up', label: 'Interface is up' },
            { key: 'driver_bound', label: 'Driver bound' },
            { key: 'firmware_loaded', label: 'Firmware loaded' },
            { key: 'can_scan', label: 'Can scan for networks' },
            { key: 'has_ip', label: 'IP address assigned' },
            { key: 'can_ping', label: 'Can reach Internet' },
            { key: 'rfkill_soft_blocked', label: 'Not software blocked (rfkill)' },
            { key: 'rfkill_hard_blocked', label: 'Not hardware blocked (rfkill)' }

        ];

        let rows = checks.map(check => {
            const success = !!data[check.key];
            const badge = success
            ? '<span class="badge bg-success"><i class="fa fa-check-circle"></i> OK</span>'
            : '<span class="badge bg-danger"><i class="fa fa-times-circle"></i> Failed</span>';
            return `<tr><td>${check.label}</td><td>${badge}</td></tr>`;
        }).join('');

        $('#adapterTestTitle').html(`<i class="fas fa-stethoscope me-2"></i>Adapter health check: ${data.interface}`);
        $('#adapterTestResults tbody').html(rows);
        const modal = new bootstrap.Modal(document.getElementById('adapterTestModal'));
        modal.show();
    }

    $('#adapterTestModal').on('hidden.bs.modal', function () {
        const statusIcon = $('#healthcheck-status i');
        statusIcon
            .removeClass('fa-check fa-times fa-cog fa-spin text-success text-danger text-secondary')
            .closest('#healthcheck-status')
            .addClass('check-hidden');
    });
}