<?php header("Content-Type: text/css; charset=utf-8"); ?>
<?php
require_once '../../includes/functions.php';
$themeColor = getThemeColorOpt();
?>

:root {
  --raspap-theme-color: <?php echo htmlspecialchars($themeColor, ENT_QUOTES, 'UTF-8'); ?>;
  --raspap-theme-color-lighter: <?php echo htmlspecialchars(lightenColor($themeColor, 20), ENT_QUOTES, 'UTF-8'); ?>;
  --raspap-theme-color-darker: <?php echo htmlspecialchars(darkenColor($themeColor, 20), ENT_QUOTES, 'UTF-8'); ?>;
}
