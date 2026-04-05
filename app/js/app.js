import {
    setCSRFTokenHeader,
    getCookie,
    setCookie,
    set_theme,
    disableValidation
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
        const loginModal = $('#modal-admin-login');
        const redirectUrl = window.location.pathname;
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

    const $htmlElement = $('html');
    const $modeswitch = $('#night-mode');
    $modeswitch.on('change', function() {
        const isChecked = $(this).is(':checked');
        const newTheme = isChecked ? 'dark' : 'light';
        $htmlElement.attr('data-bs-theme', newTheme);
        localStorage.setItem('bsTheme', newTheme);
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