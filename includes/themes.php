<?php
/**
*
*
*/
function DisplayThemeConfig(){

  $cselected = '';
  $hselected = '';
  $tselected = '';

  switch( $_COOKIE['theme'] ) {
    case "custom.css":
      $cselected = "selected";
      break;
    case "hackernews.css":
      $hselected = "selected";
      break;
    case "terminal.css":
      $tselected = "selected";
      break;
  }

  ?>
  <div class="row">
  <div class="col-lg-12">
  <div class="panel panel-primary">
  <div class="panel-heading"><i class="fa fa-wrench fa-fw"></i> Change Theme</div>
  <div class="panel-body">
    <div class="row">
    <div class="col-md-6">
    <div class="panel panel-default">
    <div class="panel-body">
      <h4>Theme settings</h4>

  <div class="row">
          <div class="form-group col-md-6">
            <label for="code">Select a theme</label>  
              <select class="form-control" id="theme-select">Select a Theme
                <option value="default" class="theme-link" <?php echo $cselected; ?>>RaspAP (default)</option>
                <option value="hackernews" class="theme-link"<?php echo $hselected; ?>>HackerNews</option>
                <option value="terminal" class="theme-link" <?php echo $tselected; ?>>Terminal</option>
              </select>
          </div>
        </div>

    </div><!-- /.panel-body -->
    </div><!-- /.panel-default -->
    </div><!-- /.col-md-6 -->
    </div><!-- /.row -->

    <form action="?page=system_info" method="POST">
      <input type="button" class="btn btn-outline btn-primary" value="Refresh" onclick="document.location.reload(true)" />
    </form>

  </div><!-- /.panel-body -->
  </div><!-- /.panel-primary -->
  </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
  <?php
}
?>

