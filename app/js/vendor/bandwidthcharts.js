(function($, _t) {
  "use strict";

  /**
   * Create a Chart.js barchart.
   */
  function CreateChart(ctx, labels) {
    var barchart = new Chart(ctx,{
      type: 'line',
      options: {
        responsive: true,
        scales: {
          xAxes: [{
            scaleLabel: {
              display: true,
              labelString: 'date',
            },
            ticks: {
              maxRotation: 90,
              minRotation: 80
            }
          }],
          yAxes: [{
            id: 'y-axis-1',
            type: 'linear',
            display: true,
            position: 'left',
            ticks: {
              beginAtZero: true
            }
          }]
        }
      },
      data: {
        labels: labels,
        datasets: []
      }
    });

    return barchart;
  }

  /**
   * Create a jquery bootstrap datatable.
   */
  function CreateDataTable(placeholder, timeunits) {
    $("#"+placeholder).append('<table id="tableBandwidth'+timeunits+
      '" class="table table-striped"><thead>'+
      '<tr><th>date</th><th>rx</th><th>tx</th></tr></thead><tbody></tbody></table>');
  }

  /**
   * Figure out which tab is selected and remove all existing charts and then
   * construct the proper barchart.
   */
  function ShowBandwidthChartHandler(e) {
    // Remove all chartjs charts
    $('#divChartBandwidthhourly').empty();
    $('#divChartBandwidthdaily').empty();
    $('#divChartBandwidthmonthly').empty();
    // Remove all datatables
    $('#divTableBandwidthhourly').empty();
    $('#divTableBandwidthdaily').empty();
    $('#divTableBandwidthmonthly').empty();
    // Construct ajax uri for getting the proper data.
    var timeunit = $('ul#tabbarBandwidth li.nav-item a.nav-link.active').attr('href').substr(1);
    var uri = 'ajax/bandwidth/get_bandwidth.php?';
    uri += 'inet=';
    uri += encodeURIComponent($('#cbxInterface'+timeunit+' option:selected').text());
    uri += '&tu=';
    uri += encodeURIComponent(timeunit.substr(0, 1));
    var datasizeunits = 'mb';
    uri += '&dsu='+encodeURIComponent(datasizeunits);
    // Init. datatable html
    var datatable = CreateDataTable('divTableBandwidth'+timeunit, timeunit);
    // Get data for chart
    $.ajax({
      url: uri,
      dataType: 'json',
      beforeSend: function() {
        $('#divLoaderBandwidth'+timeunit).show();
      }
    }).done(function(jsondata) {
      $('#divLoaderBandwidth'+timeunit).hide();
      // Map json values to label array
      var labels = jsondata.map(function(e) {
        return e.date;
      });
      // Init. chart with label series
      var barchart = CreateChart('divChartBandwidth'+timeunit, labels);
      var dataRx = jsondata.map(function(e) {
        return e.rx;
      });
      var dataTx = jsondata.map(function(e) {
        return e.tx;
      });

      addData(barchart, dataRx, dataTx, datasizeunits);
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
  /**
   * Add data array to datasets of current chart.
   */
  function addData(chart, dataRx, dataTx, datasizeunits) {
    chart.data.datasets.push({
      label: 'Receive'+' '+datasizeunits.toUpperCase(),
      yAxisID: 'y-axis-1',
      borderColor: 'rgba(75, 192, 192, 1)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      data: dataRx
    });
    chart.data.datasets.push({
      label: 'Send'+' '+datasizeunits.toUpperCase(),
      yAxisID: 'y-axis-1',
      borderColor: 'rgba(192, 192, 192, 1)',
      backgroundColor: 'rgba(192, 192, 192, 0.2)',
      data: dataTx
    });
    chart.update();
  }

  $(document).ready(function() {
    $('#tabbarBandwidth a[data-toggle="tab"]').on('shown.bs.tab', ShowBandwidthChartHandler);
    $('#cbxInterfacehourly').on('change', ShowBandwidthChartHandler);
    $('#cbxInterfacedaily').on('change', ShowBandwidthChartHandler);
    $('#cbxInterfacemonthly').on('change', ShowBandwidthChartHandler);
    ShowBandwidthChartHandler();
  });

})(jQuery, t);

