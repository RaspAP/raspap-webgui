import { getCSRFToken } from "../helpers.js";

function loadSummary(strInterface) {
    var csrfToken = getCSRFToken();
    $.post('ajax/networking/get_ip_summary.php',{'interface': strInterface, 'csrf_token': csrfToken},function(data){
        let jsonData = JSON.parse(data);
        if(jsonData['return'] == 0) {
            $('#'+strInterface+'-summary').html(jsonData['output'].join('<br />'));
        } else if(jsonData['return'] == 2) {
            $('#'+strInterface+'-summary').append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'+jsonData['output'].join('<br />')+'</div>');
        }
    });
}

export function getAllInterfaces() {
    $.get('ajax/networking/get_all_interfaces.php',function(data){
        let jsonData = JSON.parse(data);
        $.each(jsonData,function(ind,value){
            loadSummary(value)
        });
    });
}

function showNetworkingMessage(jsonData) {
    var msg = Array.isArray(jsonData.output) ? jsonData.output.join('<br />') : jsonData.output;
    var alertClass = jsonData.return == 0 ? 'alert-success' : 'alert-danger';
    $('#msgNetworking').html(
        '<div class="alert ' + alertClass + ' alert-dismissible" role="alert"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' + msg + '</div>'
    );
}

/**
 * Saves settings for a single network device row, or the mobile data
 * tab, via the shared save_net_dev_config.php endpoint
 * @param string int  'netdevices' or 'mobiledata'
 * @param string opts device name, for 'netdevices' (optional)
 */
export function saveNetDeviceSettings(int, opts) {
    var csrfToken = getCSRFToken();
    var arrFormData = {};

    if (int === 'netdevices') {
        arrFormData['opts'] = opts;
        arrFormData['int-new-mac-' + opts] = $('#int-new-mac-' + opts).val();
        arrFormData['int-new-type-' + opts] = $('#int-new-type-' + opts).val();
        arrFormData['int-vid-' + opts] = $('#int-vid-' + opts).val();
        arrFormData['int-pid-' + opts] = $('#int-pid-' + opts).val();
    } else if (int === 'mobiledata') {
        arrFormData['interface'] = 'mobiledata';
        $('#frm-mobiledata').serializeArray().forEach(function (field) {
            arrFormData[field.name] = field.value;
        });
    }

    $.post('ajax/networking/save_net_dev_config.php', {
        'arrFormData': arrFormData,
        'csrf_token': csrfToken
    }, function (data) {
        let jsonData = typeof data === 'object' ? data : JSON.parse(data);
        showNetworkingMessage(jsonData);
    });
}

export function initNetworking_ajax() {
    console.info("RaspAP Networking ajax module initialized");

    getAllInterfaces();
}