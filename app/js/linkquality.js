// Link quality gauge for ChartJS
// console.log(linkQ);

// Support for dark terminal theme
theme = getCookie('theme');
if (theme == 'terminal.css') {
    var bgColor1 = '#000';
    var bgColor2 = '#000';
    var borderColor = 'rgba(46, 230, 0, 1)';
    var labelColor = 'rgba(46, 230, 0, 1)';
} else {
    var bgColor1 = '#d4edda';
    var bgColor2 = '#eaecf4';
    var borderColor = 'rgba(147, 210, 162, 1)';
    var labelColor = 'rgba(130, 130, 130, 1)';
}

let data1 = {
    datasets: [{
      	data: [linkQ, 100-linkQ],
        backgroundColor: [bgColor1, bgColor2],
        borderColor: borderColor,
    }],
};

let config = {
    type: 'doughnut',
    data: data1,
    options: {
        responsive: true,
        tooltips: {enabled: false},
        hover: {mode: null},
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
    ctx.fillStyle = labelColor;
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

