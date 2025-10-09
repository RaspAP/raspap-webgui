<?php header("Content-Type: image/svg+xml; charset=utf-8"); ?>
<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
$color = getColorOpt();
$static = (isset($_GET['static']) && $_GET['static'] == '1') ||
    (defined('RASPI_UI_STATIC_LOGO') && RASPI_UI_STATIC_LOGO === true);
?>
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<svg
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:cc="http://creativecommons.org/ns#"
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   xmlns:svg="http://www.w3.org/2000/svg"
   xmlns="http://www.w3.org/2000/svg"
   viewBox="0 180 352 290"
   xml:space="preserve"
   id="svg2"
   version="1.1">
<style>
<?php if (!$static): ?>
    .wave {
      opacity: 0.4;
      animation: pulse 1.8s infinite;
    }
    .wave1 { animation-delay: 0.3s; }
    .wave2 { animation-delay: 0.6s; }

    @keyframes pulse {
      0%   { opacity: 0.4; }
      20%  { opacity: 1; }
      60%  { opacity: 0.4; }
      100% { opacity: 0.4; }
    }
<?php else: ?>
    .wave {
      opacity: 1.0;
    }
<?php endif; ?>
</style>

  <!-- inner solid circle -->
  <circle cx="128" cy="384" r="60" fill="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>"/>

  <!-- outer ring -->
  <circle cx="128" cy="384" r="100" fill="none" stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="25"/>

  <!-- arcs -->
  <path class="wave wave1" d="M128 234 A 150 150 0 0 1 278 384" fill="none" stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="25"/>
  <path class="wave wave2" d="M128 184 A 200 200 0 0 1 328 384" fill="none" stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="25"/>

</svg>

