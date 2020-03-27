  <?php ob_start() ?>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
      <input type="submit" class="btn btn-outline btn-primary" name="saveadblocksettings" value="<?php echo _("Save settings"); ?>">
      <?php if ($dnsmasq_state) : ?>
        <input type="submit" class="btn btn-success" name="startadblock" value="<?php echo _("Start Ad Blocking"); ?>">
      <?php else : ?>
        <input type="submit" class="btn btn-warning" name="stopadblock" value="<?php echo _("Stop Ad Blocking"); ?>">
      <?php endif ?>
    <?php endif ?>
  <?php $buttons = ob_get_clean(); ob_end_clean() ?>

  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col">
              <i class="far fa-hand-paper mr-2"></i><?php echo _("Ad Blocking"); ?>
            </div>
            <div class="col">
              <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
                <span class="text service-status">adblock <?php echo _($serviceStatus) ?></span>
              </button>
            </div>
          </div><!-- /.row -->
        </div><!-- /.card-header -->
        <div class="card-body">
        <?php $status->showMessages(); ?>
          <form role="form" action="?page=adblock_conf" enctype="multipart/form-data" method="POST">
            <?php echo CSRFTokenFieldTag() ?>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" id="clienttab" href="#adblocklistsettings" data-toggle="tab"><?php echo _("Blocklist settings"); ?></a></li>
                <li class="nav-item"><a class="nav-link" id="logoutputtab" href="#adblocklogfileoutput" data-toggle="tab"><?php echo _("Logfile output"); ?></a></li>
            </ul>
            <!-- Tab panes -->
            <div class="tab-content">

              <!-- blocklist settings tab -->
              <div class="tab-pane active" id="adblocklistsettings">

                <div class="row">
                  <div class="col-md-6">
                    <h4 class="mt-3"><?php echo _("Blocklist settings"); ?></h4>

                      <div class="input-group">
                        <input type="hidden" name="adblock-enable" value="0">
                        <div class="custom-control custom-switch">
                          <input class="custom-control-input" id="adblock-enable" type="checkbox" name="adblock-enable" value="1" <?php echo $arrConf['addn-hosts'] ? ' checked="checked"' : "" ?> aria-describedby="adblock-description">
                        <label class="custom-control-label" for="adblock-enable"><?php echo _("Enable blocklists") ?></label>
                      </div>
                      <p id="adblock-description">
                        <small><?php echo _("Enable this option if you want RaspAP to <b>block DNS requests for ads, tracking and other virtual garbage</b>. Blocklists are gathered from multiple, actively maintained sources and automatically updated, cleaned, optimized and moderated on a daily basis.") ?></small>
                        <div class="mb-3">
                          <small class="text-muted"><?php echo _("This option adds <code>conf-file</code> and <code>addn-hosts</code> to the dnsmasq configuration.") ?></small>
                        </div>
                      </p>
                      </div>

                      <div class="row">
                        <div class="input-group col-md-12 mb-4">
                          <select class="custom-select custom-select-sm" id="cbxblocklist" onchange="clearBlocklistStatus()">
                            <option value=""><?php echo _("Choose a blocklist provider") ?></option>
                            <option disabled="disabled"></option>
                            <?php echo optionsForSelect(blocklistProviders()) ?>
                          </select>
                          <div class="input-group-append">
                            <button class="btn btn-sm btn-outline-secondary rounded-right" type="button" onclick="updateBlocklist()"><?php echo _("Update now"); ?></button>
                            <span id="cbxblocklist-status" class="input-group-addon check-hidden ml-2 mt-1"><i class="fas fa-check"></i></span>
                          </div>
                        </div>
                      </div>

                  </div>
                </div><!-- /.row -->
              </div><!-- /.tab-pane | advanded tab -->

              <!-- logging tab -->
              <div class="tab-pane fade" id="adblocklogfileoutput">
                <h4 class="mt-3"><?php echo _("Blocklist log"); ?></h4>
                <div class="row">
                  <div class="form-group col-md-8">
                    <?php
                        echo '<textarea class="logoutput"></textarea>';
                    ?>
                  </div>
                </div>
              </div>
              <?php echo $buttons ?>
              </form>
            </div>
        </div><!-- /.card-body -->
        <div class="card-footer"><?php echo _("Information provided by adblock"); ?></div>
  </div><!-- /.card -->
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->

