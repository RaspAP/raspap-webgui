export function initAuth() {
    console.info("RaspAP Auth module initialized");

    $('#js-auth-save-logout').click(function() {
        var form = $('#auth-basic')[0];
        if (form.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        }
        $('#auth-basic').addClass('was-validated');

        if (form.checkValidity()) {
            if ($('#chxnonprivenable').prop("checked")) {
                $('#auth-save-logout').modal('show');
            } else {
                $('#auth-basic').submit();
            }
        }
    });

    $('#authlogout').click(function() {
        var user = $('#auth-basic #limited_user').val();
        var pass = $('#auth-basic #limited_pass').val();
        $('#modal_limited_user').val(user);
        $('#modal_limited_pass').val(pass);
        $('#auth-logout').submit();
    });

    $('input[name="nonprivEnabled"]').change(function() {
        if ($('input[name="nonprivEnabled"]:checked').val() == '1') {
            $('#admin_user').prop('disabled', true);
            $('#admin_user').removeAttr('required');
            $("[id^=limited]").removeAttr('disabled');
            $("[id^=limited]").prop('required', true);
        } else {
            $('#admin_user').prop('disabled', false);
            $('#admin_user').prop('required', true);
            $("[id^=limited]").prop('disabled', true);
            $("[id^=limited]").prop('required', false);
        }
    });
}