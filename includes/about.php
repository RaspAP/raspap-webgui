<?php

include_once( 'includes/status_messages.php' );

function DisplayAbout() {
/**
 *
 * Displays info about the RaspAP project
 *
 */
?>
  <div class="row">
  <div class="col-lg-12">
  <div class="panel panel-primary">
  <div class="panel-heading"><i class="fa fa-info-circle fa-fw"></i> <?php echo _("About RaspAP"); ?></div>
  <div class="panel-body">

    <div class="panel panel-default">
    <div class="panel-body">
      <div class="row">
        <div class="col-lg-6" style="text-align: center">
          <h3><?php echo _("RaspAP") . " v" . RASPI_VERSION; ?></h3>
          <h5><a href="https://github.com/billz/raspap-webgui/blob/master/LICENSE">GNU General Public License v3.0</a></h5>
          <p><img src="img/authors-8bit-200px.png"></p>
          <p>RaspAP is a co-creation of <a href="https://github.com/billz">@billz</a> and <a href="https://github.com/sirlagz">@SirLagz</a><br />
          with the contributions of our <a href="https://github.com/billz/raspap-webgui/graphs/contributors">community</a>.</p>
          <p><i class="fa fa-github fa-fw"></i> <a href="https://github.com/billz/raspap-webgui">https://github.com/billz/raspap-webgui</a></p>
       </div><!-- /.col-lg-6 -->
      </div><!-- /.row -->

    </div><!-- ./panel-body-->
    </div><!-- /.panel-default -->
  
  </div><!-- /.panel-body -->
  <div class="panel-footer"></div>
  </div><!-- /.panel-primary -->
  </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
<?php
}


