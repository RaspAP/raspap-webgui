import {
    setCSRFTokenHeader,
    getCookie,
    setCookie,
    disableValidation,
    setDarkMode,
    setLightMode
} from "./helpers.js";

import { initHostapd } from "./ui/hostapd.js";
import { initDHCP } from "./ui/dhcp.js";
import { initAdblock } from "./ui/adblock.js";
import { initNetworking } from "./ui/networking.js";
import { initOpenVPN } from "./ui/openvpn.js";
import { initWireGuard } from "./ui/wg.js";
import { initRestApi } from "./ui/restapi.js";
import { initSystem } from "./ui/system.js";
import { initAbout } from "./ui/about.js";
import { initLogin } from "./ui/login.js";

// ajax handlers
import { initHostapd_ajax } from "./ajax/hostapd.js";
import { initDHCP_ajax } from "./ajax/dhcp.js";
import { initAdblock_ajax } from "./ajax/adblock.js";
import { initWPA_ajax } from "./ajax/wpa.js"; 
import { initNetworking_ajax } from "./ajax/networking.js";
import { initOpenVPN_ajax } from "./ajax/openvpn.js";
import { initWireGuard_ajax } from "./ajax/wg.js";
import { initSession_ajax } from "./ajax/session.js";
import { initSystem_ajax} from "./ajax/system.js";
import { initPlugins_ajax } from "./ajax/plugins.js";
import { initAbout_ajax } from "./ajax/about.js";

document.addEventListener('DOMContentLoaded', () => {
    console.info("RaspAP app.js initialized");

    // Initialize the appropriate module based on the current path
    const path = window.location.pathname;
    console.log(`Current path: ${path}`);
    switch (path) {
        case '/dashboard':
        case '/':
            // initDashboard();
            break;
        case '/hostapd_conf':
            initHostapd();
            initHostapd_ajax();
            break;
        case '/dhcpd_conf':
            initDHCP();
            initDHCP_ajax();
            break;
        case '/adblock_conf':
            initAdblock();
            initAdblock_ajax();
            break;
        case '/network_conf':
            initNetworking();
            initNetworking_ajax();
            break;
        case '/wpa_conf':
            initWPA_ajax();
            break;
        case '/openvpn_conf':
            initOpenVPN();
            initOpenVPN_ajax();
            break;
        case '/wg_conf':
            initWireGuard();
            initWireGuard_ajax();
            break;
        case '/restapi_conf':
            initRestApi();
            break;
        case '/system_info':
            initSystem();
            initSystem_ajax();
            initPlugins_ajax();
            break;
        case '/about':
            initAbout();
            initAbout_ajax();
            break;
        case '/login':
            initLogin();
            break;
        default:
            console.warn(`No initialization function defined for path: ${path}`);
    }

    // --------- Global initialization ---------
    initSession_ajax();
    $(document).ajaxSend(setCSRFTokenHeader);
    globalThis.getCookie = getCookie;
    globalThis.setCookie = setCookie;
    globalThis.disableValidation = disableValidation;

    // Enable Bootstrap tooltips
    $('[data-bs-toggle="tooltip"]').tooltip()

    // Adds active class to current nav-item
    $(window).on("load", function() {
        var currentLocation = window.location;
        $('.sb-nav-link-icon a').filter(function() {
            const linkUrl = new URL(this.href);
            return linkUrl.pathname == currentLocation.pathname;
        }).parent().addClass('active');
    });

    // Allows closing of sidebar when content overlay is clicked
    $(document).on('click', '.sb-sidenav-toggled #layoutSidenav_content', function() {
        // Only apply on mobile style nav
        if (window.innerWidth < 992) {
            $('#sidebarToggle').trigger('click');
        }
    });

    // Sets focus on a specified tab
    jQuery(function() {
        // Store hash in URL
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            var hash = $(e.target).attr('href');
            history.pushState(null, null, hash);
        });

        // Activate tab based on URL hash
        var hash = window.location.hash;
        if (hash) {
            $('.nav-link[href="' + hash + '"]').tab('show');
        }
    });

    // Event listener for custom from validation and live form handling
    window.addEventListener('load', function() {
        var forms = document.getElementsByTagName('form');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                // if form has bootstrap `needs-validation` class, perform validation checks
                if (form.classList.contains('needs-validation')) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }

                // if form has `live-form` class, handle live form submission
                if (form.classList.contains('live-form')) {
                    handleLiveFormSubmission(event);
                }
            }, false);
        });
    }, false);

    async function handleLiveFormSubmission(e) {
        e.preventDefault();

        const form = e.target;
        const action = form.action;
        const modalEl = document.getElementById('liveFormModal');
        const modal = new bootstrap.Modal(modalEl, { keyboard: false });
        
        if (!form || !action || !modal) return;

        // Clear previous modal state
        $(modalEl).find('#liveFormModalProgressBar').removeClass('bg-success bg-danger').css('width', '0%');
        $(modalEl).find('#liveFormModalCurrentMessage').text('');
        $(modalEl).find('#liveFormModalMessageHistory').empty();
        $(modalEl).find('.modal-footer').empty().hide();

        const formData = new FormData(form);

        // Get data from submitting button if exists
        const submitter = e.submitter || null;
        if (submitter && submitter.name) {
            formData.append(submitter.name, submitter?.value || '');
        }

        // set initial modal content
        let newTitle = $(submitter)?.data('modal-title') || $(form).data('modal-title');
        if (newTitle) $(modalEl).find('#liveFormModalTitle').text(newTitle);

        modal.show();

        await fetchLiveFormStream(action, formData, {
            onMessage: (json) => {
                if (json.progress) {
                    $(modalEl).find('#liveFormModalProgressBar').css('width', json.progress + '%');
                }
            },
            onUpdateMessage: (json) => {
                if (json.message) {
                    $(modalEl).find('#liveFormModalCurrentMessage').text(json.message);
                    const messageHistory = $(modalEl).find('#liveFormModalMessageHistory');
                    messageHistory.append($('<div>').text(json.message));
                    messageHistory.scrollTop(messageHistory.prop("scrollHeight"));
                }
            },
            onCompleteMessage: (json) => {
                $(modalEl).find('#liveFormModalProgressBar').addClass('bg-success');
            },
            onFailedMessage: (json) => {
                $(modalEl).find('#liveFormModalProgressBar').addClass('bg-danger');

                let closeButton = $('<button>')
                    .addClass('btn btn-outline-primary')
                    .attr('type', 'button')
                    .text('Close')
                    .on('click', () => {
                        modal.hide();
                    });
                let reloadButton = $('<button>')
                    .addClass('btn btn-primary')
                    .attr('type', 'button')
                    .text('Reload Page')
                    .on('click', () => {
                        window.location.reload();
                    });
                $(modalEl)
                    .find('.modal-footer')
                    .append(closeButton)
                    .append(reloadButton)
                    .show();
            }
        });
    };

    async function fetchLiveFormStream(action, formData, options = {}) {
        let defaultOptions = {
            onMessage: null,
            onUpdateMessage: null,
            onCompleteMessage: null,
            onFailedMessage: null,
            reloadOnComplete: true,
            reloadOnFailed: false
        };
        options = { ...defaultOptions, ...options };

        const response = await fetch(action, {
            method: 'POST',
            body: formData
        });

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const { done, value } = await reader.read();

            if (done) break;

            // Accumulate chunks into a buffer
            buffer += decoder.decode(value, { stream: true });

            // Split on SSE message boundaries
            const parts = buffer.split('\n\n');

            // Last element may be an incomplete message — keep it in the buffer
            buffer = parts.pop();

            for (const part of parts) {
                if (part.startsWith('data: ')) {
                    const data = part.replace(/^data: /, '').trim();
                    let json = JSON.parse(data);

                    if (options?.onMessage) {
                        options.onMessage(json);
                    }

                    if (json.status === 'RUNNING' && options?.onUpdateMessage) {
                        options.onUpdateMessage(json);
                    }

                    if (json.status === 'COMPLETE' || json.status === 'FAILED') {
                        reader.cancel();
                        if (json.status === 'COMPLETE' && options?.onCompleteMessage) {
                            options.onCompleteMessage(json);
                        }

                        if (json.status === 'FAILED' && options?.onFailedMessage) {
                            options.onFailedMessage(json);
                        }

                        if (options?.reloadOnComplete || options?.reloadOnFailed) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        }
                    }
                }
            }
        }
    };

    // Input masks
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
    $('.cidr').mask('099.099.099.099/099', {
        translation: {
            '0': { pattern: /[0-9]/ }
        },
        placeholder: "___.___.___.___/___"
    });

    // Show hide password functionality
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

    // Session expired login button
    $(document).on("click", "#js-session-expired-login", function(e) {
        const redirectUrl = window.location.pathname + window.location.hash;
        window.location.href = `/login?action=${encodeURIComponent(redirectUrl)}`;
    });

    // Static Array method
    Array.range = (start, end) => Array.from({length: (end - start)}, (v, k) => k + start);

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

    const systemModeToggle = $('.system-mode-toggle');
    const darkModeToggle = $('.dark-mode-toggle');
    darkModeToggle.on('change', function() {
        const isChecked = $(this).is(':checked');
        if (isChecked) {
            setDarkMode();
        } else {
            setLightMode();
        }
        systemModeToggle.removeClass('active').prop('checked', false);
        setCookie('use_system_color_scheme', 'false', 365);
    });

    // Update color mode on system preference change if set to use system preference
    const preferredColorScheme = window.matchMedia('(prefers-color-scheme: dark)');
    const systemColorScheme = preferredColorScheme.matches ? 'dark' : 'light';
    setCookie('system_color_scheme', systemColorScheme, 365);
    preferredColorScheme.addEventListener('change', function(event) {
        const useSystem = getCookie('use_system_color_scheme') === 'true';
        if (event.matches) {
            if (useSystem) setDarkMode(true);
            setCookie('system_color_scheme', 'dark', 365);
        } else {
            if (useSystem) setLightMode(true);
            setCookie('system_color_scheme', 'light', 365);
            
        }
    });
    
    systemModeToggle.on('click', function() {
        const systemColorScheme = preferredColorScheme.matches ? 'dark' : 'light';
        // update cookie for PHP context
        setCookie('system_color_scheme', systemColorScheme, 365);

        const isButton = $(this).hasClass('btn');
        const useSystem = getCookie('use_system_color_scheme') === 'true' || false;

        if (useSystem) {
            setCookie('use_system_color_scheme', 'false', 365);
            const userTheme = getCookie('theme_mode') || 'light';
            if (userTheme === 'dark') {
                setDarkMode();
            } else {
                setLightMode();
            }
            // Update state and sync System->Theme toggle
            if (isButton) {
                $(this).removeClass('active');
                $('#settings-system-mode').prop('checked', false);
            } else {
                $(this).prop('checked', false);
                $('#navbar-system-mode').removeClass('active');
            }
        } else {
            setCookie('use_system_color_scheme', 'true', 365);
            if (systemColorScheme === 'dark') {
                setDarkMode(true);
            } else {
                setLightMode(true);
            }
            // Update state and sync System->Theme toggle
            if (isButton) {
                $(this).addClass('active');
                $('#settings-system-mode').prop('checked', true);
            } else {
                $(this).prop('checked', true);
                $('#navbar-system-mode').addClass('active');
            }
        }
    });

    // Handle stacking of multiple Bootstrap modals
    $(document).on('show.bs.modal', '.modal', function () {
        // Calculate increasing z-index based on how many modals are currently visible
        // 1050 is Bootstrap's base modal z-index
        const zIndex = 1050 + 10 * $('.modal:visible').length;

        $(this).css('z-index', zIndex);

        // Give the backdrop a slightly lower z-index and mark it as stacked
        // Small delay ensures Bootstrap has created the backdrop
        setTimeout(() => {
            $('.modal-backdrop').not('.modal-stack')
            .css('z-index', zIndex - 1)
            .addClass('modal-stack');
        }, 10);
    });

    // To auto-close Bootstrap alerts; time is in milliseconds
    const alertTimeout = parseInt(getCookie('alert_timeout'), 10);
    window.setTimeout(
        function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function(){
                $(this).remove();
            });
        },
        !isNaN(alertTimeout) && alertTimeout > 0 ? alertTimeout : 5000
    );
});