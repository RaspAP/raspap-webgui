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
    $.post('/ajax/networking/get_ip_summary.php',{interface:strInterface,csrf_token:csrf},function(data){
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
        $.each(jsonData,function(ind,value){loadSummary(value)});
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
    $.post('/ajax/networking/get_int_config.php',{interface:strInterface,csrf_token:csrf},function(data){
        jsonData = JSON.parse(data);
        //console.log(data);
        $.each(jsonData['output'],function(i,v) {
            //console.log(i);
            var int = v['interface'];
            //console.log('interface : '+int);
            $.each(v,function(i2,v2) {
                //console.log(i2+":"+v2);
                switch(i2) {
                    case "static":
                        if(v2 == 'true') {
                            $('#'+int+'-static').click();
                        }
                    break;
                    case "interface":
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

function setupBtns() {
    $('#btnSummaryRefresh').click(function(){getAllInterfaces();});
    $('.intsave').click(function(){
        var int = $(this).data('int');
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
        arrFormData['csrf_token'] = csrf;
        $.post('/ajax/networking/save_int_config.php',arrFormData,function(data){
            console.log(data);
        });
    });
}

$().ready(function(){
    csrf = $('#csrf_token').val();
    pageCurrent = window.location.href.split("?")[1].split("=")[1];
    pageCurrent = pageCurrent.replace("#","");
    switch(pageCurrent) {
        case "network_conf":
            getAllInterfaces();
            setupTabs();
            setupBtns();
        break;
    }
});


