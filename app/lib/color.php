<?php header("Content-Type: text/css; charset=utf-8"); ?>

<?php
if (!isset($_COOKIE['color'])) {
    $color = "#d8224c";
} else {
    $color = $_COOKIE['color'];
}
?>

.card .card-header {
  border-color: <?php echo $color; ?>;
  color: #fff;
  background-color: <?php echo $color; ?>;
}

.btn-primary {
  color: <?php echo $color; ?>;
  border-color: <?php echo $color; ?>;
  background-color: #fff;
}
