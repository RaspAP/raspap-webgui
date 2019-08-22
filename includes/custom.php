<?php

include_once('includes/config.php');
include_once('includes/functions.php');
include_once('includes/status_messages.php');


function DisplayCustomConfig()
{
    $status = new StatusMessages();
    $data = parse_ini_file(RASPI_CUSTOM_CONFIG, true);
    
    foreach (RASPI_CUSTOM_FIELDS as $title => $params){
        $titlecode = str_replace(' ', '', $title);
        if (isset($_POST[$titlecode])) {
            foreach($params as $key => $desc){
                $data[$titlecode][$key] = $_POST[$key];
            }
            write_php_ini($data, RASPI_CUSTOM_CONFIG);
        }
    }
    
    foreach (RASPI_CUSTOM_FIELDS as $title => $params)
    {
        $titlecode = str_replace(' ', '', $title);
        ?>

<div class="row">
    <div class="col-lg-12">
      <div class="panel panel-primary">
        <div class="panel-heading"><i class="fa fa-lock fa-fw"></i><?php echo _("$title"); ?></div>
        <div class="panel-body">
          <p><?php $status->showMessages(); ?></p>
          <form role="form" action="?page=custom_conf" method="POST">
            <?php echo CSRFTokenFieldTag() ?>

            <?php foreach ($params as $key => $desc){ ?>
                <div class="row">
                <div class="form-group col-md-4">
                    <label for="$key"><?php echo _($desc); ?></label>
                    <input type="text" class="form-control" name="<?php echo _($key); ?>" value="<?php echo _($data[$titlecode][$key]); ?>"/>
                </div>
                </div>
            <?php } ?>

        <input type="submit" class="btn btn-outline btn-primary" name="<?php echo _($titlecode); ?>" value="<?php echo _("Save settings"); ?>" />
        </form>
        </div><!-- /.panel-body -->
      </div><!-- /.panel-default -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->

<?php
}
}
?>
s
          
