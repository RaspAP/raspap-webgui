// Initialize Huebee color picker
var elem = $(".theme-color-input")[0];
var hueb = new Huebee(elem, {
  notation: "hex",
  saturations: 2,
  customColors: ["#d8224c", "#dd4814", "#ea0", "#19f", "#333"],
  className: "light-picker",
  hue0: 210,
});

// Set custom color if defined
var themeColor = getCookie("theme-color");
if (themeColor == null || themeColor == "") {
  themeColor = "#2b8080";
}
hueb.setColor(themeColor);

// Change event
hueb.on("change", function (themeColor, hue, sat, lum) {
  setCookie("theme-color", themeColor, 90);
});
