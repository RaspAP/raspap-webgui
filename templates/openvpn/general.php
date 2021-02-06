<div class="tab-pane active" id="openvpnclient">
  <h4 class="mt-3"><?php echo _("Client settings"); ?></h4>
  <div class="row">
    <div class="col">
      <div class="row">
        <div class="col-lg-12 mt-2 mb-2">
          <div class="info-item"><?php echo _("IPv4 Address"); ?></div>
          <div class="info-item"><?php echo htmlspecialchars($public_ip, ENT_QUOTES); ?><a class="text-gray-500" href="https://ipapi.co/<?php echo($public_ip); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt ml-2"></i></a></div>
        </div>
      </div>
      <div class="row">
       <div class="form-group col-lg-12">
        <label for="code"><?php echo _("Username"); ?></label>
          <input type="text" class="form-control" name="authUser" value="<?php echo htmlspecialchars($authUser, ENT_QUOTES); ?>" />
        </div>
      </div>
      <div class="row">
        <div class="form-group col-lg-12">
          <label for="code"><?php echo _("Password"); ?></label>
          <input type="password" class="form-control" name="authPassword" value="<?php echo htmlspecialchars($authPassword, ENT_QUOTES); ?>" />
        </div>
      </div>
      <div class="row">
        <div class="form-group col-lg-12">
          <div class="custom-file">
            <input type="file" class="custom-file-input" name="customFile" id="customFile">
            <label class="custom-file-label" for="customFile"><?php echo _("Select OpenVPN configuration file (.ovpn)"); ?></label>
          </div>
        </div>
      </div>
    </div><!-- col-->
    <div class="col-sm">
        <a href="https://go.nordvpn.net/aff_c?offer_id=15&aff_id=36402&url_id=902"><img src="app/img/180x150.png" class="rounded float-left mb-3 mt-3"></a>
    </div>
  </div><!-- /.row -->
</div><!-- /.tab-pane | general tab -->

