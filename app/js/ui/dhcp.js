import { escapeHtml } from "../helpers.js";

export function setStaticFieldsEnabled() {
    $('#txtipaddress').prop('required', true);
    $('#txtsubnetmask').prop('required', true);
    $('#txtgateway').prop('required', true);

    $('#txtipaddress').removeAttr('disabled');
    $('#txtsubnetmask').removeAttr('disabled');
    $('#txtgateway').removeAttr('disabled');
}

export function setStaticFieldsDisabled() {
    $('#txtipaddress').prop('disabled', true);
    $('#txtsubnetmask').prop('disabled', true);
    $('#txtgateway').prop('disabled', true);

    $('#txtipaddress').removeAttr('required');
    $('#txtsubnetmask').removeAttr('required');
    $('#txtgateway').removeAttr('required');
}

export function setDhcpFieldsEnabled() {
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

export function setDhcpFieldsDisabled() {
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

export function initDHCP() {
    console.info("RaspAP DHCP module initialized");

    // DHCP or Static IP option group
    $('#chkstatic, #chkdhcp').on('change', function() {
        if (this.id === 'chkstatic' && this.checked) {
            $('#chkdhcp').closest('.btn').removeClass('active');
            $('#chkstatic').closest('.btn').addClass('active');

            // set form for Static IP
            $('#dhcp-iface').removeAttr('disabled');
            $('#chkfallback').prop('disabled', true).prop('checked', false);
            setDhcpFieldsEnabled();
            setStaticFieldsEnabled();
        } else {
            $('#chkstatic').closest('.btn').removeClass('active');
            $('#chkdhcp').closest('.btn').addClass('active');

            // set form for DHCP
            $('#chkfallback').removeAttr('disabled');
            $('#dhcp-iface').prop('disabled', true).prop('checked', false);
            setDhcpFieldsDisabled();
            if ($('#chkfallback').is(':checked')) {
                setStaticFieldsEnabled();
            } else {
                setStaticFieldsDisabled();
            }
        }
    });

    $('input[name="dhcp-iface"]').on('change', function() {
        if ($('input[name="dhcp-iface"]:checked').val() == '1') {
            setDhcpFieldsEnabled();
        } else {
            setDhcpFieldsDisabled();
        }
    });

    $('#chkfallback').on('change', function() {
        if ($('#chkfallback').is(':checked')) {
            setStaticFieldsEnabled();
        } else {
            setStaticFieldsDisabled();
        }
    });

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
            .replace("{{ mac }}", escapeHtml(mac))
            .replace("{{ ip }}", escapeHtml(ip))
            .replace("{{ comment }}", escapeHtml(comment));
        $(".js-dhcp-static-lease-container").append(row);

        // reset inputs
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
        var row = $("#dhcp-upstream-server").html().replace("{{ server }}", escapeHtml(field.val()))
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

    const dhcpCheckbox = document.getElementById('dhcp-iface');
    const rangeStart = document.getElementById('txtrangestart');
    const rangeEnd = document.getElementById('txtrangeend');
    const leaseTime = document.getElementById('txtrangeleasetime');

    function updateRequiredFields() {
        const isChecked = dhcpCheckbox.checked === true;

        if (isChecked) {
            rangeStart.setAttribute('required', 'required');
            rangeEnd.setAttribute('required', 'required');
            leaseTime.setAttribute('required', 'required');
        } else {
            rangeStart.removeAttribute('required');
            rangeEnd.removeAttribute('required');
            leaseTime.removeAttribute('required');

            rangeStart.classList.remove('is-invalid', 'is-valid');
            rangeEnd.classList.remove('is-invalid', 'is-valid');
            leaseTime.classList.remove('is-invalid', 'is-valid');
        }
    }

    // set initial state
    if (dhcpCheckbox) {
        updateRequiredFields();
        setTimeout(updateRequiredFields, 100);
        dhcpCheckbox.addEventListener('change', updateRequiredFields);
    }
}