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
		    maintainAspectRatio: false,
        scales: {
          xAxes: [{
            scaleLabel: {
              display: true
            },
            ticks: {
              maxRotation: 0,
              minRotation: 0
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

  function ShowBandwidthChartHandler(e) {
    // Remove hourly chartjs chart
    $('#divDBChartBandwidthhourly').empty();
    // Construct ajax uri for getting the proper data
    var timeunit = 'hourly'; 
    var uri = 'ajax/bandwidth/get_bandwidth.php?';
    uri += 'inet=';
    uri += encodeURIComponent($('#divInterface').text());
    uri += '&tu=';
    uri += encodeURIComponent(timeunit.substr(0, 1));
    var datasizeunits = 'mb';
    uri += '&dsu='+encodeURIComponent(datasizeunits);
    // Get data for chart
    $.ajax({
      url: uri,
      dataType: 'json',
      beforeSend: function() {}
    }).done(function(jsondata) {
      // Map json values to label array
      var labels = jsondata.map(function(e) {
        return e.date;
      });
      // Init. chart with label series
      var barchart = CreateChart('divDBChartBandwidth'+timeunit, labels);
      var dataRx = jsondata.map(function(e) {
        return e.rx;
      });
      var dataTx = jsondata.map(function(e) {
        return e.tx;
      });
      addData(barchart, dataTx, dataRx, datasizeunits);
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
  function addData(chart, dataTx, dataRx, datasizeunits) {
   chart.data.datasets.push({
      label: 'Send'+' '+datasizeunits.toUpperCase(),
      yAxisID: 'y-axis-1',
      borderColor: 'rgba(75, 192, 192, 1)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      data: dataTx
    });
   chart.data.datasets.push({
      label: 'Receive'+' '+datasizeunits.toUpperCase(),
      yAxisID: 'y-axis-1',
      borderColor: 'rgba(192, 192, 192, 1)',
      backgroundColor: 'rgba(192, 192, 192, 0.2)',
      data: dataRx
    });
    chart.update();
  }
  $(document).ready(function() {
    ShowBandwidthChartHandler();
  });

})(jQuery, t);

