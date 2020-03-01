    <div class="row">
    <div class="col-lg-12">
      <div class="card"> 
        <div class="card-header"><i class="fa fa-eye-slash fa-fw"></i> TOR proxy</div>
        <div class="card-body">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" href="#basic" data-toggle="tab">Basic</a></li>
                <li class="nav-item"><a class="nav-link" href="#relay" data-toggle="tab">Relay</a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <p><?php echo $status; ?></p>

                <div class="tab-pane active" id="basic">
                    <h4>Basic settings</h4>
                    <form role="form" action="?page=save_hostapd_conf" method="POST">
                    <?php echo CSRFTokenFieldTag() ?>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">VirtualAddrNetwork</label>
                            <input type="text" class="form-control" name="virtualaddrnetwork" value="<?php echo htmlspecialchars($arrConfig['VirtualAddrNetwork'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">AutomapHostsSuffixes</label>
                            <input type="text" class="form-control" name="automaphostssuffixes" value="<?php echo htmlspecialchars($arrConfig['AutomapHostsSuffixes'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">AutomapHostsOnResolve</label>
                            <input type="text" class="form-control" name="automaphostsonresolve" value="<?php echo htmlspecialchars($arrConfig['AutomapHostsOnResolve'], ENT_QUOTES); ?>" />
                        </div>
                    </div>  
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">TransListenAddress</label>
                            <input type="text" class="form-control" name="translistenaddress" value="<?php echo htmlspecialchars($arrConfig['TransListenAddress'], ENT_QUOTES); ?>" />
                        </div>
                    </div>  
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">DNSPort</label>
                            <input type="text" class="form-control" name="dnsport" value="<?php echo htmlspecialchars($arrConfig['DNSPort'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">DNSListenAddress</label>
                            <input type="text" class="form-control" name="dnslistenaddress" value="<?php echo htmlspecialchars($arrConfig['DNSListenAddress'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="relay">
                    <h4>Relay settings</h4>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">ORPort</label>
                            <input type="text" class="form-control" name="orport" value="<?php echo htmlspecialchars($arrConfig['ORPort'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">ORListenAddress</label>
                            <input type="text" class="form-control" name="orlistenaddress" value="<?php echo htmlspecialchars($arrConfig['ORListenAddress'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">Nickname</label>
                            <input type="text" class="form-control" name="nickname" value="<?php echo htmlspecialchars($arrConfig['Nickname'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">Address</label>
                            <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($arrConfig['Address'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">RelayBandwidthRate</label>
                            <input type="text" class="form-control" name="relaybandwidthrate" value="<?php echo htmlspecialchars($arrConfig['RelayBandwidthRate'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">RelayBandwidthBurst</label>
                            <input type="text" class="form-control" name="relaybandwidthburst" value="<?php echo htmlspecialchars($arrConfig['RelayBandwidthBurst'], ENT_QUOTES); ?>" />
                        </div>
                    </div>
                </div>

                <input type="submit" class="btn btn-outline btn-primary" name="SaveTORProxySettings" value="Save settings" />
                <?php
                if ($torproxystatus[0] == 0) {
                    echo '<input type="submit" class="btn btn-success" name="StartTOR" value="Start TOR" />' , PHP_EOL;
                } else {
                    echo '<input type="submit" class="btn btn-warning" name="StopTOR" value="Stop TOR" />' , PHP_EOL;
                };
                ?>
                </form>
            </div><!-- /.tab-content -->
        </div><!-- /.card-body -->
        <div class="card-footer"> Information provided by tor</div>
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->

