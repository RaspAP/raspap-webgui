<?php
include_once( 'includes/status_messages.php' );


/**
 * Generate html output for tab with vnstat traffic amount information.
 */
function DisplayVnstat()
{
  $status = new StatusMessages();
  exec('vnstat -m ', $stdoutvnstatmonthly, $exitcodemonthly);
  if ($exitcodemonthly !== 0) {
      $status->addMessage(sprinf(_('Getting vnstat %s information failed.'), _('daily')), 'error');
  }
  
  exec('vnstat -w ', $stdoutvnstatweekly, $exitcodeweekly);
  if ($exitcodeweekly !== 0) {
      $status->addMessage(sprinf(_('Getting vnstat %s information failed.'), _('weekly')), 'error');
  }
  
  exec('vnstat -d ', $stdoutvnstatdaily, $exitcodedaily);
  if ($exitcodedaily !== 0) {
      $status->addMessage(sprinf(_('Getting vnstat %s information failed.'), _('monthly')), 
                          'error');
  }
?>
  <div class="row">
  <div class="col-lg-12">
  <div class="panel panel-primary">
  <div class="panel-heading"><i class="fa fa-bar-chart fa-fw"></i> <?php echo _("Bandwidth monitoring"); ?></div>
  <div class="panel-body">

  <div class="row">
  <div class="col-md-12">
  <div class="panel panel-default">
  <div class="panel-body">
  <p><?php $status->showMessages(); ?></p>

  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active tabtrafficdaily"><a href="#daily" aria-controls="daily" role="tab" data-toggle="tab"><?php echo _("Daily"); ?></a></li>
    <li role="presentation" class="tabtrafficweekly"><a href="#weekly" aria-controls="weekly" role="tab" data-toggle="tab"><?php echo _("Weekly"); ?></a></li>
    <li role="presentation" class="tabtrafficmonthly"><a href="#monthly" aria-controls="monthly" role="tab" data-toggle="tab"><?php echo _("Monthly"); ?></a></li>
  </ul>

  <div class="tabcontenttraffic tab-content">
    <div role="tabpanel" class="tab-pane active" id="daily">
      <div class="row">
        <div class="col-lg-6">
          <h4>Daily traffic amount</h4>
<?php
foreach ($stdoutvnstatdaily as &$linedaily) {
    if (empty($linedaily)) {
        continue;
    }

    echo '          <code>' , str_replace(' ', '&nbsp;', htmlentities($linedaily, ENT_QUOTES)) ,
       '</code><br />';
}

?>
          <br />
        </div>
      </div>
    </div>

    <div role="tabpanel" class="tab-pane" id="weekly">
      <div class="row">
        <div class="col-lg-6">
          <h4><?php echo _("Weekly traffic amount"); ?></h4>
<?php
foreach ($stdoutvnstatweekly as &$lineweekly) {
    if (empty($lineweekly)) {
        continue;
    }

    echo '          <code>' , str_replace(' ', '&nbsp;', htmlentities($lineweekly, ENT_QUOTES)) ,
       '</code><br />', PHP_EOL;
}

?>
          
        </div>
      </div>
    </div>

    <div role="tabpanel" class="tab-pane" id="monthly">
      <div class="row">
        <div class="col-lg-6">
          <h4><?php echo _("Monthly traffic amount"); ?></h4>
<?php
foreach ($stdoutvnstatmonthly as &$linemonthly) {
    if (empty($linemonthly)) {
        continue;
    }

    echo '          <code>' , str_replace(' ', '&nbsp;', htmlentities($linemonthly, ENT_QUOTES)) , 
       '</code><br />' , PHP_EOL;
}

?>
          <br />
        </div>
      </div>
    </div>

    </div><!-- /.tabcontenttraffic -->

    </div><!-- /.panel-default -->
    </div><!-- /.col-md-6 -->
    </div><!-- /.row -->
  </div><!-- /.panel-body -->

  </div><!-- /.panel-primary -->
  <div class="panel-footer"><?php echo _("Information provided by vnstat"); ?></div>
  </div><!-- /.panel-primary -->
  </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
<?php
}

