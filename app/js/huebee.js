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
    color = '#d8224c';
}
hueb.setColor(color);

// Change event
hueb.on( 'change', function( color, hue, sat, lum ) {
    setCookie('color',color,90);
})

