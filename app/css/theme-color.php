<?php header("Content-Type: text/css; charset=utf-8"); ?>
<?php
require_once '../../includes/functions.php';
$color = getColorOpt();
?>

:root {
  --raspap-theme-color: <?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>;
  --raspap-theme-lighter: <?php echo htmlspecialchars(lightenColor($color, 20), ENT_QUOTES, 'UTF-8'); ?>;
  --raspap-theme-darker: <?php echo htmlspecialchars(darkenColor($color, 20), ENT_QUOTES, 'UTF-8'); ?>;
}
