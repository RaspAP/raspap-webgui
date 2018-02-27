<?php

include_once( 'includes/status_messages.php' );

/*
* Example script for a custom settings page
* Based on includes/hostapd.php script
*
*/
function DisplayCustomPage1(){

  $status = new StatusMessages();

  # Definition of dropdown list options
  $arrUpperBound = array('20','18','16','14','12');
  $arrLowerBound = array('11','9','7','5','3','1');


  if( isset($_POST['saveCP1settings']) ) {
    if (CSRFValidate()) {
      SaveSettingsFile($status);
    } else {
      error_log('CSRF violation');
    }
  }

  # Read existing configuration data, else use default data
  if ( ! $arrCustomConf = parse_ini_file('config/custompage1.ini')) {
    $status->addMessage('Could not find an existing configuration file', 'warning');
  }

  ?>

  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-primary">
        <div class="panel-heading"><i class="fa <?php echo RASPI_CUSTOMPAGE1_ICON; ?> fa-fw"></i> <?php echo RASPI_CUSTOMPAGE1_NAME; ?></div>
        <!-- /.panel-heading -->
          <div class="panel-body">
  	        <p><?php $status->showMessages(); ?></p>
            <form role="form" action="?page=custompage1_conf" method="POST">
              <!-- Nav tabs -->
              <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#url" data-toggle="tab">URL</a>
                </li>
                <li>
                  <a href="#bounds" data-toggle="tab">Bounds</a>
                </li>
              </ul>

              <!-- Tab panes -->
              <div class="tab-content">

                <div class="tab-pane fade in active" id="url">
                  <h4>Resource location settings</h4>
                  <?php CSRFToken() ?>
                  <div class="row">
                    <div class="form-group col-md-4">
                      <label for="code">Web page address</label>
                      <input type="text" class="form-control" name="url" value="<?php echo $arrCustomConf['url']; ?>" />
                    </div>
                  </div>
                </div>

                <div class="tab-pane fade" id="bounds">
                  <h4>Boundary settings</h4>
                  <div class="row">
                    <div class="form-group col-md-4">
                      <label for="code">Upper bound</label>
                      <?php SelectorOptions('upper_bound', $arrUpperBound, $arrCustomConf['upper_bound']); ?>
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-4">
                      <label for="code">Lower bound</label>
                      <?php SelectorOptions('lower_bound', $arrLowerBound, $arrCustomConf['lower_bound']); ?>
                    </div>
                  </div>
                </div>


              </div><!-- /.tab-content  -->
              <input type="submit" class="btn btn-outline btn-primary" name="saveCP1settings" value="Save settings" />
            </form>
          </div><!-- /.panel-body -->
          <div class="panel-footer"> Custom page 1 footer</div>
        </div><!-- /.panel-primary -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
<?php 
}

function SaveCustomPage1($status) {

  $good_input = true;

  // Verify input
  if (strlen($_POST['url']) == 0 ) {
    $status->addMessage('URL is empty.  Please enter some data.', 'danger');
    $good_input = false;
  }

  if ($good_input) {

    $ini_data = ["url" => $_POST['url'], "upper_bound" => $_POST['upper_bound'], "lower_bound" => $_POST['lower_bound']];

    if ( write_php_ini($ini_data,'config/custompage1.ini')) {
      $status->addMessage('Successfully saved configuration data', 'success');
    } else {
      $status->addMessage('Unable to save configuration data', 'danger');
      return false;
    }
    
  }
  return true;
}
?>


    