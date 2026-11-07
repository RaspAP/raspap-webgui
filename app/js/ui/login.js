export function initLogin() {
    console.info("RaspAP login module initialized");

    const params = new URLSearchParams(window.location.search);
    const redirectUrl = $('#redirect-url').val() || params.get('action') || '/';
    $('#modal-admin-login').modal('show');
    $('#redirect-url').val(redirectUrl);
    const $first = $('#newpass').length ? $('#newpass') : $('#username');
    $first.focus();
    $first.addClass("focusedInput");
}