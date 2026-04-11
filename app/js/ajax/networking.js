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

export function initNetworking_ajax() {
    console.info("RaspAP Networking ajax module initialized");

    getAllInterfaces();
}