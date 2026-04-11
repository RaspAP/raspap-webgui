function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var regx = new RegExp(cname + "=([^;]+)");
    var value = regx.exec(document.cookie);
    return (value != null) ? unescape(value[1]) : null;
}

// Initialize Huebee color picker
var elem = document.querySelector('.color-input');
var hueb = new Huebee( elem, {
    notation: 'hex',
    saturations: 2,
    customColors: [ '#d8224c', '#dd4814', '#ea0', '#19f', '#333' ],
    className: 'light-picker',
    hue0: 210
});

// Set custom color if defined
var color = getCookie('color');
if (color == null || color == '') {
    color = '#2b8080';
}
hueb.setColor(color);

// Change event
hueb.on( 'change', function( color, hue, sat, lum ) {
    setCookie('color',color,90);
})

