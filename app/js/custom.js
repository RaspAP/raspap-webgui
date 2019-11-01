function msgShow(retcode,msg) {
    if(retcode == 0) {
        var alertType = 'success';
    } else if(retcode == 2 || retcode == 1) {
        var alertType = 'danger';
    }
    var htmlMsg = '<div class="alert alert-'+alertType+' alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+msg+'</div>';
    return htmlMsg;
}

function createNetmaskAddr(bitCount) {
  var mask=[];
  for(i=0;i<4;i++) {
    var n = Math.min(bitCount, 8);
    mask.push(256 - Math.pow(2, 8-n));
    bitCount -= n;
  }
  return mask.join('.');
}

function loadSummary(strInterface) {
    $.post('/ajax/networking/get_ip_summary.php',{interface:strInterface},function(data){
        jsonData = JSON.parse(data);
        console.log(jsonData);
        if(jsonData['return'] == 0) {
            $('#'+strInterface+'-summary').html(jsonData['output'].join('<br />'));
        } else if(jsonData['return'] == 2) {
            $('#'+strInterface+'-summary').append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+jsonData['output'].join('<br />')+'</div>');
        }
    });
}

function getAllInterfaces() {
    $.get('/ajax/networking/get_all_interfaces.php',function(data){
        jsonData = JSON.parse(data);
        $.each(jsonData,function(ind,value){
            loadSummary(value)
        });
    });
}

function setupTabs() {
    $('a[data-toggle="tab"]').on('shown.bs.tab',function(e){
        var target = $(e.target).attr('href');
        if(!target.match('summary')) {
            var int = target.replace("#","");
            loadCurrentSettings(int);
        }
    });
}

function loadCurrentSettings(strInterface) {
    $.post('/ajax/networking/get_int_config.php',{interface:strInterface},function(data){
        jsonData = JSON.parse(data);
        $.each(jsonData['output'],function(i,v) {
            var int = v['interface'];
            $.each(v,function(i2,v2) {
                switch(i2) {
                    case "static":
                        if(v2 == 'true') {
                            $('#'+int+'-static').click();
                            $('#'+int+'-nofailover').click();
                        } else {
                            $('#'+int+'-dhcp').click();
                        }
                    break;
                    case "failover":
                        if(v2 === 'true') {
                            $('#'+int+'-failover').click();
                        } else {
                            $('#'+int+'-nofailover').click();
                        }
                    break;
                    case "ip_address":
                        var arrIPNetmask = v2.split('/');
                        $('#'+int+'-ipaddress').val(arrIPNetmask[0]);
                        $('#'+int+'-netmask').val(createNetmaskAddr(arrIPNetmask[1]));
                    break;
                    case "routers":
                        $('#'+int+'-gateway').val(v2);
                    break;
                    case "domain_name_server":
                        svrsDNS = v2.split(" ");
                        $('#'+int+'-dnssvr').val(svrsDNS[0]);
                        $('#'+int+'-dnssvralt').val(svrsDNS[1]);
                    break;
                }
            });
        });
    });
}

function saveNetworkSettings(int) {

        var frmInt = $('#frm-'+int).find(':input');
        var arrFormData = {};
        $.each(frmInt,function(i3,v3){
            if($(v3).attr('type') == 'radio') {
                arrFormData[$(v3).attr('id')] = $(v3).prop('checked');
            } else {
                arrFormData[$(v3).attr('id')] = $(v3).val();
            }
        });
        arrFormData['interface'] = int;
        $.post('/ajax/networking/save_int_config.php',arrFormData,function(data){
            //console.log(data);
            var jsonData = JSON.parse(data);
            $('#msgNetworking').html(msgShow(jsonData['return'],jsonData['output']));
        });
}

function applyNetworkSettings() {
        var int = $(this).data('int');
        arrFormData = {};
        arrFormData['generate'] = '';
        $.post('/ajax/networking/gen_int_config.php',arrFormData,function(data){
            console.log(data);
            var jsonData = JSON.parse(data);
            $('#msgNetworking').html(msgShow(jsonData['return'],jsonData['output']));
        });
}

$(document).on("click", ".js-add-dhcp-static-lease", function(e) {
    e.preventDefault();
    var container = $(".js-new-dhcp-static-lease");
    var mac = $("input[name=mac]", container).val().trim();
    var ip  = $("input[name=ip]", container).val().trim();
    if (mac == "" || ip == "") {
        return;
    }

    var row = $("#js-dhcp-static-lease-row").html()
        .replace("{{ mac }}", mac)
        .replace("{{ ip }}", ip);
    $(".js-dhcp-static-lease-container").append(row);

    $("input[name=mac]", container).val("");
    $("input[name=ip]", container).val("");
});

$(document).on("click", ".js-remove-dhcp-static-lease", function(e) {
    e.preventDefault();
    $(this).parents(".js-dhcp-static-lease-row").remove();
});

$(document).on("submit", ".js-dhcp-settings-form", function(e) {
    $(".js-add-dhcp-static-lease").trigger("click");
});

function setupBtns() {
    $('#btnSummaryRefresh').click(function(){getAllInterfaces();});

    $('.intsave').click(function(){
        var int = $(this).data('int');
        saveNetworkSettings(int);
    });

    $('.intapply').click(function(){
        applyNetworkSettings();
    });
}

function setCSRFTokenHeader(event, xhr, settings) {
    var csrfToken = $('meta[name=csrf_token]').attr('content');
    if (/^(POST|PATCH|PUT|DELETE)$/i.test(settings.type)) {
        xhr.setRequestHeader("X-CSRF-Token", csrfToken);
    }
}

function contentLoaded() {
    pageCurrent = window.location.href.split("?")[1].split("=")[1];
    pageCurrent = pageCurrent.replace("#","");
    switch(pageCurrent) {
        case "network_conf":
            getAllInterfaces();
            setupTabs();
            setupBtns();
        break;
    }
}

function loadWifiStations(refresh) {
    return function() {
        var complete = function() { $(this).removeClass('loading-spinner'); }
        var qs = refresh === true ? '?refresh' : '';
        $('.js-wifi-stations')
            .addClass('loading-spinner')
            .empty()
            .load('/ajax/networking/wifi_stations.php'+qs, complete);
    };
}

$(".js-reload-wifi-stations").on("click", loadWifiStations(true));

$(document).on("click", ".js-toggle-password", function(e) {
    var button = $(e.target)
    var field  = $(button.data("target"));
    if (field.is(":input")) {
        e.preventDefault();

        if (!button.data("__toggle-with-initial")) {
            button.data("__toggle-with-initial", button.text())
        }

        if (field.attr("type") === "password") {
            button.text(button.data("toggle-with"));
            field.attr("type", "text");
        } else {
            button.text(button.data("__toggle-with-initial"));
            field.attr("type", "password");
        }
    }
});

$(document).on("keyup", ".js-validate-psk", function(e) {
    var field  = $(e.target);
    var colors = field.data("colors").split(",");
    var target = $(field.data("target"));
    if (field.val().length < 8 || field.val().length > 63) {
        field.css("backgroundColor", colors[0]);
        target.attr("disabled", true);
    } else {
        field.css("backgroundColor", colors[1]);
        target.attr("disabled", false);
    }
});

$(function() {
    $('#theme-select').change(function() {
        var theme = themes[$( "#theme-select" ).val() ]; 
        set_theme(theme);
   });
});

function set_theme(theme) {
    $('link[title="main"]').attr('href', 'app/css/' + theme);

    // persist selected theme in cookie 
    setCookie('theme',theme,90);
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var regx = new RegExp(cname + "=([^;]+)");
    var value = regx.exec(document.cookie);
    return (value != null) ? unescape(value[1]) : null;
}

var themes = {
    "default": "custom.css",
    "hackernews" : "hackernews.css",
    "terminal" : "terminal.css",
}

// Toggles the sidebar navigation.
// Overrides the default SB Admin 2 behavior
$("#sidebarToggleTopbar").on('click', function(e) {
    $("body").toggleClass("sidebar-toggled");
    $(".sidebar").toggleClass("toggled d-none");
});

// Overrides SB Admin 2
$("#sidebarToggle, #sidebarToggleTop").on('click', function(e) {
    var toggled = $(".sidebar").hasClass("toggled");
    // Persist state in cookie
    setCookie('sidebarToggled',toggled, 90);
});

$(function() {
    if ($(window).width() < 768) {
        $('.sidebar').addClass('toggled');
        setCookie('sidebarToggled',false, 90);
    }
});

$(window).on("load resize",function(e) {
    if ($(window).width() > 768) {
        $('.sidebar').removeClass('d-none d-md-block');
        if (getCookie('sidebarToggled') == 'false') {
            $('.sidebar').removeClass('toggled');
        }
    }
});

// Adds active class to current nav-item
$(window).bind("load", function() {
    var url = window.location;
    $('ul.navbar-nav a').filter(function() {
				return this.href == url;
		}).parent().addClass('active');
});

$(document)
    .ajaxSend(setCSRFTokenHeader)
    .ready(contentLoaded)
    .ready(loadWifiStations());
