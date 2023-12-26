<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <link href="dist/bootstrap/css/bootstrap.css" rel="stylesheet">
    <link href="dist/sb-admin-2/css/sb-admin-2.min.css" rel="stylesheet">
  </head>
  <body id="page-top">
    <div class="row">
      <div class="col-lg-12">
        <h3 class="mt-3"><?php echo _("An exception occurred"); ?></h3>
        <?php echo '<pre>'; print_r($trace); echo '</pre>'; ?>
      </div>
    </div>
  </body>
</html>

