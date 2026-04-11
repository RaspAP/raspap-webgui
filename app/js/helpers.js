export function msgShow(retcode,msg) {
    if(retcode == 0) {
        var alertType = 'success';
    } else if(retcode == 2 || retcode == 1) {
        var alertType = 'danger';
    }
    var htmlMsg = '<div class="alert alert-'+alertType+' alert-dismissible" role="alert"><button type="button" class="btn-close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close"></button>'+msg+'</div>';
    return htmlMsg;
}

export function createNetmaskAddr(bitCount) {
    var mask=[];
    for(i=0;i<4;i++) {
        var n = Math.min(bitCount, 8);
        mask.push(256 - Math.pow(2, 8-n));
        bitCount -= n;
    }
    return mask.join('.');
}

export function setupTabs() {
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab',function(e){
        var target = $(e.target).attr('href');
        if(!target.match('summary')) {
            var int = target.replace("#","");
        }
    });
}

export function genPassword(pwdLen) {
    var pwdChars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    var rndPass = Array(pwdLen).fill(pwdChars).map(function(x) { return x[Math.floor(Math.random() * x.length)] }).join('');
    return rndPass;
}

export function setCSRFTokenHeader(event, xhr, settings) {
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    if (/^(POST|PATCH|PUT|DELETE)$/i.test(settings.type)) {
        xhr.setRequestHeader("X-CSRF-Token", csrfToken);
    }
}

export function getCSRFToken() {
    return $('meta[name=csrf_token]').attr('content');
}

export function formatProperty(prop) {
    if (Array.isArray(prop)) {
        if (typeof prop[0] === 'object') {
            return prop.map(item => {
                return Object.entries(item)
                    .map(([key, value]) => `${key}: ${value}`)
                    .join('<br/>');
            }).join('<br/>');
        }
        return prop.map(line => `${line}<br/>`).join('');
    }
    if (typeof prop === 'object') {
        return Object.entries(prop)
            .map(([key, value]) => `${key}: ${value}`)
            .join('<br/>');
    }
    return prop || 'None';
}

export function set_theme(theme) {
    $('link[title="main"]').attr('href', 'app/css/' + theme);
    // persist selected theme in cookie 
    setCookie('theme',theme,90);
}

export function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

export function getCookie(cname) {
    var regx = new RegExp(cname + "=([^;]+)");
    var value = regx.exec(document.cookie);
    return (value != null) ? unescape(value[1]) : null;
}

export function disableValidation(form) {
    form.removeAttribute("novalidate");
    form.classList.remove("needs-validation");
    form.querySelectorAll("[required]").forEach(function (field) {
        field.removeAttribute("required");
    });
}

export function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g,  "&amp;")
        .replace(/</g,  "&lt;")
        .replace(/>/g,  "&gt;")
        .replace(/"/g,  "&quot;")
        .replace(/'/g,  "&#39;");
}