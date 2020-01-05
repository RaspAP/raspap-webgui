  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col">
              <i class="fas fa-key fa-fw mr-2"></i><?php echo _("Configure OpenVPN"); ?>
            </div>
            <div class="col">
              <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
                <span class="text service-status">openvpn <?php echo _($serviceStatus) ?></span>
              </button>
            </div>
          </div><!-- /.row -->
        </div><!-- /.card-header -->
        <div class="card-body">
        <?php $status->showMessages(); ?>
          <form role="form" action="?page=openvpn_conf" enctype="multipart/form-data" method="POST">
            <?php echo CSRFTokenFieldTag() ?>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" id="clienttab" href="#openvpnclient" data-toggle="tab"><?php echo _("Client settings"); ?></a></li>
                <li class="nav-item"><a class="nav-link" id="logoutputtab" href="#openvpnlogoutput" data-toggle="tab"><?php echo _("Logfile output"); ?></a></li>
            </ul>
            <!-- Tab panes -->
            <div class="tab-content">
              <div class="tab-pane active" id="openvpnclient">
                <h4 class="mt-3"><?php echo _("Client settings"); ?></h4>
                  <div class="row">
                    <div class="col-md-6 mt-2 mb-2">
                      <div class="info-item"><?php echo _("IPv4 Address"); ?></div>
                      <div class="info-item"><?php echo htmlspecialchars($public_ip, ENT_QUOTES); ?><a class="text-gray-500" href="https://ipapi.co/<?php echo($public_ip); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt ml-2"></i></a></div>
                    </div>
                  </div>
                  <div class="row">
                   <div class="form-group col-md-6">
                    <label for="code"><?php echo _("Username"); ?></label>
                      <input type="text" class="form-control" name="authUser" value="<?php echo htmlspecialchars($authUser, ENT_QUOTES); ?>" />
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="code"><?php echo _("Password"); ?></label>
                      <input type="password" class="form-control" name="authPassword" value="<?php echo htmlspecialchars($authPassword, ENT_QUOTES); ?>" />
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      <div class="custom-file">
                        <input type="file" class="custom-file-input" name="customFile" id="customFile">
                        <label class="custom-file-label" for="customFile"><?php echo _("Select OpenVPN configuration file (.ovpn)"); ?></label>
                      </div>
                    </div>
                </div>
              </div>
              <div class="tab-pane fade" id="openvpnlogoutput">
                <h4 class="mt-3"><?php echo _("Client log"); ?></h4>
                <div class="row">
                  <div class="form-group col-md-8">
                    <?php
                        $log = file_get_contents('/tmp/openvpn.log');
                        echo '<textarea class="logoutput">'.htmlspecialchars($log, ENT_QUOTES).'</textarea>';
                    ?>
                  </div>
                </div>
              </div>
              <?php if (!RASPI_MONITOR_ENABLED) : ?>

                  <input type="submit" class="btn btn-outline btn-primary" name="SaveOpenVPNSettings" value="Save settings" />
                  <?php if ($serviceStatus==='down') {
					  echo '<input type="submit" class="btn btn-success" name="StartOpenVPN" value="Start OpenVPN" />' , PHP_EOL;
				  } else {
                    echo '<input type="submit" class="btn btn-warning" name="StopOpenVPN" value="Stop OpenVPN" />' , PHP_EOL;
                  }
                  ?>

              <?php endif ?>
                        <input type="submit" class="btn btn-outline btn-warning" name="EnableNAT" title="Warning, AP clients will be able to connect to Internet" value="Enable NAT" />
                        <input type="submit" class="btn btn-outline btn-warning" name="DisableNAT" title="AP clients will NOT be able to connect to Internet" value="Disable NAT"/>
<br/>
<select id = "Copy" name="Copy" size="5">
<?php
foreach (scan_dir("/home/pi/openVPN/conf") as $item){
	echo '<option value = "'.$item.'">'.$item.'</option>';
}
?>
</select>
<br/>
<input type="submit" class="btn btn-outline btn-primary" name="GetVPNGateServers" value="Refresh VPNGate Servers" />

<input type="submit" class="btn btn-outline btn-primary" name="UseVPNGateServer" value="Use Selected VPNGate Server" />
              </form>
            </div>
        </div><!-- /.card-body -->

    <div class="card-footer"> Information provided by openvpn <a href="/download/openvpn.zip">Download VPN Gate zip file</a> | <a href="https://ipleak.net" target="_blank">Check for IP Leak</a></div>
  </div><!-- /.card -->
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->

