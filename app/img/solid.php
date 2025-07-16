<?php
header("Content-Type: image/svg+xml");

require_once '../../includes/functions.php';
$color = getColorOpt();

$showJoint = isset($_GET['joint']);
$showDevice1 = isset($_GET['device-1']);
$showOut = isset($_GET['out']);
$showDevice2 = isset($_GET['device-2']);
$showDevice3 = isset($_GET['device-3']);
$showDevice4 = isset($_GET['device-4']);
?>

<svg xmlns="http://www.w3.org/2000/svg" width="227" height="596" viewBox="0 0 227 596" fill="none">
<?php 
// Device positions array (y-coordinates)
$devicePositions = [
    'device-1' => 0.75,
    'out' => 297.75,
    'device-2' => 198.75,
    'device-3' => 397.058,
    'device-4' => 595.211
];

// Calculate joint line segments
if ($showJoint) {
    $activeDevices = array_filter([$showDevice1, $showDevice2, $showDevice3, $showDevice4]);
    $activeYs = [];
    
    foreach ($devicePositions as $device => $y) {
        if (isset($_GET[$device])) {
            $activeYs[] = $y;
        }
    }
    
    // Add top/bottom if first/last device is connected
    if ($showDevice1) array_unshift($activeYs, 0);
    if ($showDevice4) $activeYs[] = 596;
    
    // Draw segments between consecutive points
    for ($i = 1; $i < count($activeYs); $i++) {
        $y1 = $activeYs[$i-1];
        $y2 = $activeYs[$i];
        echo "<line x1='112.75' y1='$y1' x2='112.75' y2='$y2' stroke='" . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . "' stroke-width='4'/>";
    }
}
?>

<?php if ($showDevice1): ?>
<line x1="113.231" y1="0.75" x2="7.69496e-06" y2="0.75001" stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="6" id="device-1"/>
<?php endif; ?>
<?php if ($showOut): ?>
<line x1="226.231" y1="297.75" x2="113" y2="297.75" stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="4" id="out"/>
<?php endif; ?>
<?php if ($showDevice2): ?>
<line x1="113.231" y1="198.75" x2="7.69496e-06" y2="198.75" stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="4" id="device-2"/>
<?php endif; ?>
<?php if ($showDevice3): ?>
<line x1="113.231" y1="397.058" x2="7.69496e-06" y2="397.058" stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="4" id="device-3"/>
<?php endif; ?>
<?php if ($showDevice4): ?>
<line x1="113.231" y1="595.211" x2="7.69496e-06" y2="595.211" stroke="<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>" stroke-width="4" id="device-4"/>
<?php endif; ?>
</svg>
