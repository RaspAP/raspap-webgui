  <div class="row">
    <div class="col-lg-12">
      <div class="card">
      <div class="card-header"><i class="fas fa-key fa-fw mr-2"></i>Configure OpenVPN</div>
        <div class="card-body">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" id="clienttab" href="#openvpnclient" data-toggle="tab"><?php echo _("Client settings"); ?></a></li>
                <li class="nav-item"><a class="nav-link" id="logoutputtab" href="#openvpnlogoutput" data-toggle="tab"><?php echo _("Logfile output"); ?></a></li>
            </ul>
            <!-- Tab panes -->
            <div class="tab-content">
                <p><?php echo $status; ?></p>
                <div class="tab-pane active" id="openvpnclient">
                    <h4>Client settings</h4>
                    <form role="form" action="?page=save_hostapd_conf" method="POST">
                    <?php echo CSRFTokenFieldTag() ?>

                    <div class="row">
					  <div class="form-group col-md-6">
						  <label for="code"><?php echo _("Username"); ?></label>
						  <input type="text" class="form-control" name="Username" value="<?php echo htmlspecialchars($Username, ENT_QUOTES); ?>" />
					  </div>
					</div>
                    <div class="row">
					  <div class="form-group col-md-6">
						  <label for="code"><?php echo _("Password"); ?></label>
						  <input type="password" class="form-control" name="Password" value="<?php echo htmlspecialchars($Password, ENT_QUOTES); ?>" />
					  </div>
					</div>
                    <div class="row">
                        <div class="form-group col-md-6">
                          <div class="custom-file">
                            <input type="file" class="custom-file-input" id="customFile">
                            <label class="custom-file-label" for="customFile">Select OpenVPN configuration file (.ovpn)</label>
                          </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="openvpnlogoutput">
				  <h4><?php echo _("Client log"); ?></h4>
				  <div class="row">
					<div class="form-group col-md-8">
					  <?php
						  $log = file_get_contents('/tmp/hostapd.log');
						  echo '<br /><textarea class="logoutput">'.htmlspecialchars($log, ENT_QUOTES).'</textarea>';
					  ?>
					</div>
				  </div>
                </div>
                <input type="submit" class="btn btn-outline btn-primary" name="SaveOpenVPNSettings" value="Save settings" />
                <?php
                if ($hostapdstatus[0] == 0) {
                    echo '<input type="submit" class="btn btn-success" name="StartOpenVPN" value="Start OpenVPN" />' , PHP_EOL;
                } else {
                    echo '<input type="submit" class="btn btn-warning" name="StopOpenVPN" value="Stop OpenVPN" />' , PHP_EOL;
                }
                ?>
                </form>
            </div>
        </div><!-- /.card-body -->
    <div class="card-footer"> Information provided by openvpn</div>
  </div><!-- /.card -->
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->

