(function($, _t) {
    "use strict";

    /**
     * Create a Morris.js barchart.
     */
    function CreateBarChart(placeholder, datasizeunits) {
        var barchart = new Morris.Bar({
            element: placeholder,
            xkey: 'date',
            ykeys: ['rx', 'tx'],
            labels: [_t['receive']+' '+datasizeunits.toUpperCase(), 
                     _t['send']+' '+datasizeunits.toUpperCase()]
        });

      return barchart;
    }

    /**
     * Create a jquery bootstrap datatable.
     */
    function CreateDataTable(placeholder, timeunits) {
        $("#"+placeholder).append('<table id="tableBandwidth'+timeunits+
            '" class="table table-responsive table-striped container-fluid"><thead>'+
            '<tr><th>date</th><th>rx</th><th>tx</th></tr></thead><tbody></tbody></table>');
    }

    /**
     * Figure out which tab is selected and remove all existing charts and then 
     * construct the proper barchart.
     */
    function ShowBandwidthChartHandler(e) {
        // Remove all morrisjs charts
        $('#divChartBandwidthhourly').empty();
        $('#divChartBandwidthdaily').empty();
        $('#divChartBandwidthmonthly').empty();
        // Remove all datatables
        $('#divTableBandwidthhourly').empty();
        $('#divTableBandwidthdaily').empty();
        $('#divTableBandwidthmonthly').empty();
        // Construct ajax uri for getting the proper data.
        var timeunit = $('ul#tabbarBandwidth li.active a').attr('href').substr(1);
        var uri = 'ajax/bandwidth/get_bandwidth.php?';
        uri += 'inet=';
        uri += encodeURIComponent($('#cbxInterface'+timeunit+' option:selected').text());
        uri += '&tu=';
        uri += encodeURIComponent(timeunit.substr(0, 1));
        var datasizeunits = 'mb';
        uri += '&dsu='+encodeURIComponent(datasizeunits);
        // Init. chart
        var barchart = CreateBarChart('divChartBandwidth'+timeunit, datasizeunits);
        // Init. datatable html
        var datatable = CreateDataTable('divTableBandwidth'+timeunit, timeunit);
        // Get data for chart
        $.ajax({
            url: uri,
            dataType: 'json',
            beforeSend: function() {
                $('#divLoaderBandwidth'+timeunit).removeClass('hidden');
            }
        }).done(function(jsondata) {
            $('#divLoaderBandwidth'+timeunit).addClass('hidden');
            barchart.setData(jsondata);
            $('#tableBandwidth'+timeunit).DataTable({
                'searching': false,
                'paging': false,
                'data': jsondata,
                'order': [[ 0, 'ASC' ]],
                'columns': [
                    { 'data': 'date' },
                    { 'data': 'rx', "title": _t['receive']+' '+datasizeunits.toUpperCase() },
                    { 'data': 'tx', "title": _t['send']+' '+datasizeunits.toUpperCase() }]
            });
        }).fail(function(xhr, textStatus) {
            if (window.console) {
                console.error('server error');
            } else {
                alert("server error");
            }
        });
    }

    $(document).ready(function() {
        $('#tabbarBandwidth a[data-toggle="tab"]').on('shown.bs.tab', ShowBandwidthChartHandler);
        $('#cbxInterfacehourly').on('change', ShowBandwidthChartHandler);
        $('#cbxInterfacedaily').on('change', ShowBandwidthChartHandler);
        $('#cbxInterfacemonthly').on('change', ShowBandwidthChartHandler);
        ShowBandwidthChartHandler();
    });

})(jQuery, t);

