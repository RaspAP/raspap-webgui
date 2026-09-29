import { getCSRFToken } from "../helpers.js";

export function initAuth_ajax() {
    console.info("RaspAP auth ajax module initialized");

    $('#js-auth-logout').on('click', function (e) {
        var csrfToken = getCSRFToken();
        $.post('ajax/auth/admin_logout.php',{'csrf_token': csrfToken},function(data){
            let jsonData = JSON.parse(data);
            window.location.reload();
        });
    });

    $("input[name='uploadAvatar']").change(function () {
        if (this.files.length > 0) {
            if (!window.FormData) {
                return false;
            }
            var csrfToken = getCSRFToken();
            var formData = new FormData(this.form);
            formData.append('csrf_token', csrfToken);
            $.ajax({
                url: "ajax/system/sys_avatar_upload.php",
                method: 'POST',
                dataType: 'json',
                data: formData,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data['status'] === 'ok') {
                        var uploaded = data['uploaded'];
                        var placeholder = $("i.fas.fa-user-circle.fa-4x");
                        $(".avatar .embed-avatar").css({"background-image": "url('" + uploaded + "')"});
                        $(".avatar-placeholder").hide();
                        if (placeholder.length > 0) {
                            var avatar = $("<img>").addClass("avatar topbar-avatar").attr("src", uploaded );
                            placeholder.replaceWith(avatar);
                        } else {
                            var avatar = $("img.avatar.topbar-avatar");
                            avatar.attr("src", uploaded);
                        }
                    } else if (data['status'] === 'failed') {
                        return data['message'];
                    }
                },
                error: function (data) {
                return data['message'];
                }
            });
        }
    });
}