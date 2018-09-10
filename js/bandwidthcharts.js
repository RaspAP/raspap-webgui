(function($) {
    "use strict";

    /**
     * Create a Morris.js barchart.
     */
    function CreateBarChart(placeholder, datasizeunits) {
        var barchart = new Morris.Bar({
            element: placeholder,
            xkey: 'date',
            ykeys: ['rx', 'tx'],
            labels: ['Received '+datasizeunits, 'Send '+datasizeunits]  // NOI18N
        });

      return barchart;
    }

    /**
     * Create a bootstrap data table.
     */
    function CreateDataTable(placeholder) {
        $("#"+placeholder).append('<br /><table id="tableBandwidth" class="table table-striped container-fluid"><thead><tr><th>date</th><th>rx</th><th>tx</th></tr></thead><tbody></tbody></table>');
    }

    /**
     * Figure out which tab is selected and remove all existing charts and then 
     * construct the proper barchart.
     */
    function ShowBandwidthChartHandler(e) {
        // Remove all charts
        $("#divBandwidthdaily").empty();
        $("#divBandwidthweekly").empty();
        $("#divBandwidthmonthly").empty();
        // Construct ajax uri for getting proper data.
        var timeunit = $("ul#tabbarBandwidth li.active a").attr("href").substr(1);
        var uri = 'ajax/bandwidth/get_bandwidth.php?';
        uri += 'inet=';
        uri += encodeURIComponent($("#cbxInterface"+timeunit+" option:selected").text());
        uri += '&tu=';
        uri += encodeURIComponent(timeunit.substr(0, 1));
        var datasizeunits = 'mb';
        uri += '&dsu='+encodeURIComponent(datasizeunits);
        // Init. chart
        var barchart = CreateBarChart('divBandwidth'+timeunit, datasizeunits);
        // Init. datatable
        var datatable = CreateDataTable('divBandwidth'+timeunit);
        // Get data for chart
        $.ajax({
            url: uri,
            dataType: 'json',
            beforeSend: function() {
                $("#divLoaderBandwidth"+timeunit).removeClass("hidden");
            }
        }).done(function(jsondata) {
            $("#divLoaderBandwidth"+timeunit).addClass("hidden");
            barchart.setData(jsondata);
            $('#tableBandwidth').DataTable({
                "searching": false,
                data: jsondata,
                "columns": [
                    { "data": "date" },
                    { "data": "rx", "title": "received "+datasizeunits },
                    { "data": "tx", "title": "send "+datasizeunits }]
            });
        }).fail(function(xhr, textStatus) {
            if (window.console) {
                console.error("server error");
            } else {
                alert("server error");
            }
        });
    }

    $('#tabbarBandwidth a[data-toggle="tab"]').on('shown.bs.tab', ShowBandwidthChartHandler);
    $('#cbxInterfacedaily').on('change', ShowBandwidthChartHandler);
    $('#cbxInterfaceweekly').on('change', ShowBandwidthChartHandler);
    $('#cbxInterfacemonthly').on('change', ShowBandwidthChartHandler);
    ShowBandwidthChartHandler();

})(jQuery);