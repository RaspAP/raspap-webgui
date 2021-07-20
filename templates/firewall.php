<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-shield-alt mr-2"></i><?php echo _("Firewall"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <h4><?php echo _("Client Firewall"); ?></h4>
        <?php if ( $fw_conf["firewall-enable"]) : ?>
           <i class="fas fa-circle mr-2 service-status-up"></i><?php echo _("Firewall is ENABLED"); ?>
        <?php else : ?>
           <i class="fas fa-circle mr-2 service-status-down"></i><?php echo _("Firewall is OFF "); ?>
        <?php endif ?>
        <div class="row">
          <div class="col-md-6">
            <p class="mr-2"><small><?php echo _("The default firewall will allow only outgoing and already established traffic. No UDP traffic is allowed. There are no restrictions for the access point.") ?></small></p>
          </div>
        </div>
        <form id="frm-firewall" action="firewall_conf" method="POST" >
          <?php echo CSRFTokenFieldTag(); ?>
          <h5><?php echo _("Exceptions for Services"); ?></h4>
          <div class="row">
            <div class="form-group col-md-6">
                <div class="custom-control custom-switch">
                    <input class="custom-control-input" id="ssh-enable" type="checkbox" name="ssh-enable" value="1" aria-describedby="exceptions-description" <?php if ($fw_conf["ssh-enable"]) echo "checked"; ?> >
                    <label class="custom-control-label" for="ssh-enable"><?php echo _("allow SSH access on port 22") ?></label>
                </div>
                <div class="custom-control custom-switch">
                    <input class="custom-control-input" id="http-enable" type="checkbox" name="http-enable" value="1" aria-describedby="exceptions-description" <?php if ($fw_conf["http-enable"]) echo "checked"; ?> >
                    <label class="custom-control-label" for="http-enable"><?php echo _("allow access to the RaspAP GUI") ?></label>
                </div>
                <p class="mb-0" id="exceptions-description">
                    <small><?php echo _("Allow access for some services from the client side.") ?></small>
                </p>
            </div>
          </div>
          <h5><?php echo _("Exclusions from the firewall"); ?></h4>
          <div class="row">
            <div class="form-group col-md-6">
                <label for="excl-device"><?php echo _("Exclude device(s)") ?></label>
                <input class="form-control" id="excl-devices" type="text" name="excl-devices" value="<?php echo $fw_conf["excl-devices"] ?>" aria-describedby="exclusion-description"  >
                <p class="mb-0" id="exclusion-description">
                    <small><?php echo _("Exclude the given network device(s) (separated by a comma) from firewall rules.<br>Current client devices: <code>$str_clients</code><br>The access point <code>". $ap_device ."</code> is per default excluded.") ?></small>
                </p>
            </div>
          </div>
          <?php if ($fw_conf["firewall-enable"]) : ?>
              <input type="submit" class="btn btn-outline btn-primary" value="<?php echo _("Apply changes"); ?>" name="apply-firewall" />
              <input type="submit" class="btn btn-warning firewall-apply" value="<?php echo _("Disable Firewall") ?>"  name="firewall-disable" data-toggle="modal" data-target="#firewallModal"/>
          <?php else : ?>
              <input type="submit" class="btn btn-outline btn-primary" value="<?php echo _("Save"); ?>" name="save-firewall" />
              <input type="submit" class="btn btn-success firewall-apply" value="<?php echo _("Enable Firewall") ?>" name="firewall-enable" data-toggle="modal" data-target="#firewallModal"/>
          <?php endif ?>
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<!-- Modal -->
<div class="modal fade" id="firewallModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title" id="ModalLabel">
            <i class="fas fa-sync-alt mr-2"></i>
            <?php if($fw_conf["firewall-enable"]) echo _("Disable the firewall ..."); else  echo _("Enable the Firewall ..."); ?>
        </div>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span>
      </div>
  </div>
</div>

