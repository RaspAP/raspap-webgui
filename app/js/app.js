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

    if (!isNaN(alertTimeout) && alertTimeout > 0) {
        window.setTimeout(function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function(){
            $(this).remove();
            });
        }, alertTimeout);
    }
});