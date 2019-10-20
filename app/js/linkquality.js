// Link quality gauge for ChartJS
// console.log(linkQ);

let data1 = {
    datasets: [{
      	data: [linkQ, 100-linkQ],
        borderWidth: 1,
        backgroundColor: ['#d4edda', '#eaecf4'],
        borderColor: 'rgba(176, 222, 187, 1)',
        hoverBackgroundColor: ['#c1e2c8', '#eaecf4'],
        hoverBorderWidth: 0  
    }],
};

let config = {
    type: 'doughnut',
    data: data1,
    options: {
        responsive: true,
        legend: {
            display: false,
        },
        rotation: (2/3)*Math.PI,//2+(1/3),
        circumference: (1+(2/3)) * Math.PI, // * Math.PI,
        cutoutPercentage: 80,
        animation: {
            animateScale: false,
            animateRotate: true
        },
        tooltips: {
            enabled: false
        }
    },
    centerText: {
        display: true,
        text: linkQ + "%"
    }
};

Chart.Chart.pluginService.register({
    beforeDraw: function(chart) {
        if (chart.config.centerText.display !== null &&
            typeof chart.config.centerText.display !== 'undefined' &&
	    chart.config.centerText.display) {
                drawLinkQ(chart);
            }
    }
});

function drawLinkQ(chart) {

    let width = chart.chart.width;
    let height = chart.chart.height;
    let ctx = chart.chart.ctx;

    ctx.restore();
    let fontSize = (height / 100).toFixed(2);
    ctx.font = fontSize + "em sans-serif";
    ctx.fillStyle = "rgba(25,25,25,1)";
    ctx.textBaseline = "middle";

    let text = chart.config.centerText.text;
    let textX = Math.round((width - ctx.measureText(text).width) * 0.5);
    let textY = height / 2;
    ctx.fillText(text, textX, textY);
    ctx.save();
}

window.onload = function() {
    let ctx = document.getElementById("canvas").getContext("2d");
    window.myDoughnut = new Chart(ctx, config);
};

