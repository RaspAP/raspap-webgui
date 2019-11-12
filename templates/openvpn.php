  <div class="row">
    <div class="col-lg-12">
      <div class="card">
      <div class="card-header"><i class="fas fa-key fa-fw mr-2"></i>Configure OpenVPN</div>
        <div class="card-body">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" href="#openvpnclient" data-toggle="tab">Client settings</a></li>
                <li class="nav-item"><a class="nav-link" href="#openvpnserver" data-toggle="tab">Server settings</a></li>
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
                          <div class="custom-file">
                            <input type="file" class="custom-file-input" id="customFile">
                            <label class="custom-file-label" for="customFile">Select OpenVPN configuration file (.ovpn)</label>
                          </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">Client Log</label>
                            <input type="text" class="form-control" id="disabledInput" name="log-append" type="text" placeholder="<?php echo htmlspecialchars($arrClientConfig['log-append'], ENT_QUOTES); ?>" disabled="disabled" />
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="openvpnserver">
                    <h4>Server settings</h4>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Port</label> 
                        <input type="text" class="form-control" name="openvpn_port" value="<?php echo htmlspecialchars($arrServerConfig['port'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Protocol</label>
                        <input type="text" class="form-control" name="openvpn_proto" value="<?php echo htmlspecialchars($arrServerConfig['proto'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Root CA certificate</label>
                        <input type="text" class="form-control" name="openvpn_rootca" placeholder="<?php echo htmlspecialchars($arrServerConfig['ca'], ENT_QUOTES); ?>" disabled="disabled" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Server certificate</label>
                        <input type="text" class="form-control" name="openvpn_cert" placeholder="<?php echo htmlspecialchars($arrServerConfig['cert'], ENT_QUOTES); ?>" disabled="disabled" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Diffie Hellman parameters</label>
                        <input type="text" class="form-control" name="openvpn_dh" placeholder="<?php echo htmlspecialchars($arrServerConfig['dh'], ENT_QUOTES); ?>" disabled="disabled" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">KeepAlive</label>
                        <input type="text" class="form-control" name="openvpn_keepalive" value="<?php echo htmlspecialchars($arrServerConfig['keepalive'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                        <label for="code">Server log</label>
                        <input type="text" class="form-control" name="openvpn_status" placeholder="<?php echo htmlspecialchars($arrServerConfig['status'], ENT_QUOTES); ?>" disabled="disabled" />
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

