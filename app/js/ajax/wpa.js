export function initWPA_ajax() {
    console.info("RaspAP WPA ajax module initialized");

    function loadWifiStations(refresh) {
        console.log('loadWifiStations called');
        var complete = function() {
        $(this).removeClass('loading-spinner');
        $('#foundPortalModal').modal('show');
        }
        var qs = refresh === true ? '?refresh' : '';
        $('.js-wifi-stations')
            .addClass('loading-spinner')
            .empty()
            .load('ajax/networking/wifi_stations.php'+qs, complete);
    }
    $(".js-reload-wifi-stations").on("click", () => loadWifiStations(true));

    loadWifiStations();
}