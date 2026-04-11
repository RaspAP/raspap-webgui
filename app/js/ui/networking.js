import { getAllInterfaces } from "../ajax/networking.js";

export function initNetworking() {
    console.info("RaspAP Networking module initialized");

    // setup buttons
    $('#btnSummaryRefresh').click(function(){getAllInterfaces();});
    $('.intsave').click(function(){
        var int = $(this).data('int');
        saveNetworkSettings(int);
    });
    $('.intapply').click(function(){
        applyNetworkSettings();
    });
}