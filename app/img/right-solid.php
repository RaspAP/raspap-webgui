<?php
header("Content-Type: image/svg+xml");

require_once '../../includes/functions.php';
$color = getColorOpt();
$showDevice1 = isset($_GET['device-1']);
$showOut = isset($_GET['out']);
$showDevice2 = isset($_GET['device-2']);
?>

<svg width="313" height="594" viewBox="0 0 313 594" fill="none" xmlns="http://www.w3.org/2000/svg">
<g id="Frame 1">
<g id="right connection frame">
<g id="solid">
<?php if ($showDevice2): ?>
    <line id="joint-device-2" y1="-0.75" x2="154" y2="-0.75" 
          transform="matrix(4.37114e-08 1 1 -4.37114e-08 114 297)" 
          stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="4"/>
<?php endif; ?>

<?php if ($showDevice1): ?>
    <line id="joint-device-1" style="display: inline;" 
          y1="-0.75" x2="154" y2="-0.75" 
          transform="matrix(4.37114e-08 1 1 -4.37114e-08 114 144)" 
          stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="4"/>
    
    <line id="device-1" style="display: inline;" 
          y1="-0.75" x2="113.231" y2="-0.75" 
          transform="matrix(1 8.74228e-08 8.74228e-08 -1 114 144)" 
          stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="4"/>
<?php endif; ?>

<?php if ($showOut): ?>
    <line id="out" style="display: inline;" 
          y1="-0.75" x2="113.231" y2="-0.75" 
          transform="matrix(1 8.74228e-08 8.74228e-08 -1 -0.000305176 297)" 
          stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="4"/>
<?php endif; ?>

<?php if ($showDevice2): ?>
    <line id="device-2" style="display: inline;" 
          y1="-0.75" x2="113.231" y2="-0.75" 
          transform="matrix(1 8.74228e-08 8.74228e-08 -1 113 450)" 
          stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="4"/>
<?php endif; ?>
</g>
</g>
</g>
</svg>
