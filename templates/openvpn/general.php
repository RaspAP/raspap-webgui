<div class="tab-pane active" id="openvpnclient">
  <h4 class="mt-3"><?php echo _("Client settings"); ?></h4>
  <div class="row">
    <div class="col-lg-8">
      <div class="row mb-2">
         <div class="col-lg-12 mt-2 mb-2">
           <div class="row ml-1">
            <div class="info-item col-xs-3"><?php echo _("IPv4 Address"); ?></div>
            <div class="info-value col-xs-3"><?php echo htmlspecialchars($public_ip, ENT_QUOTES); ?><a class="text-gray-500" href="https://ipapi.co/<?php echo($public_ip); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt ml-2"></i></a></div>
           </div>
         </div>
      </div>
      <h5><?php echo _("Authentification Method"); ?></h5>
      <div class="col-sm-12 mt-2 mb-2 form-check">
          <input class="form-check-input" id="ovpn-userpw" name="sel1" value="userpw" data-toggle="" data-parent="#clientsettings" data-target="#UserPW" type="radio" checked>
          <label class="form-check-label"><?php echo _("Username and password"); ?></label>
      </div>
      <div class="col-sm-12 mt-2 mb-2 form-check">
          <input class="form-check-input" id="ovpn-certs" name="sel1" value="certs" data-toggle="" data-parent="#clientsettings" data-target="#Certs" type="radio">
          <label class="form-check-label"><?php echo _("Certificates"); ?></label>
      </div>
      <div class="col-sm-12 ml-2">
         <div class="panel-group" id="clientsettings">
           <div class="panel panel-default panel-collapse" id="PanelUserPW" >
             <div class="panel-heading">
               <h5 class="panel-title"><?php echo _("Enter username and password"); ?></h5>
             </div>
             <div class="panel-body">
                <div class="form-group col-lg-12">
                  <label for="code"><?php echo _("Username"); ?></label>
                  <input type="text" class="form-control" name="authUser" value="<?php echo htmlspecialchars($authUser, ENT_QUOTES); ?>" />
                </div>
                <div class="form-group col-lg-12">
                  <label for="code"><?php echo _("Password"); ?></label>
                  <input type="password" class="form-control" name="authPassword" value="<?php echo htmlspecialchars($authPassword, ENT_QUOTES); ?>" />
                </div>
             </div>
           </div><!-- panel -->
           <div class="panel panel-default panel-collapse collapse in" id="PanelCerts">
             <div class="panel-body">
               <div class="panel-heading">
                 <h5 class="panel-title"><?php echo _("Certificates in the configuration file"); ?></h5>
               </div>
               <p><?php echo _("RaspAP supports certificates by including them in the configuration file."); ?>
                 <ul>
                   <small>
                     <li><?php echo _("Signing certification authority (CA) certificate (e.g. <code>ca.crt</code>): enclosed in <code>&lt;ca> ... &lt;/ca></code> tags."); ?></li>
                     <li><?php echo _("Client certificate (public key) (e.g. <code>client.crt</code>): enclosed in <code>&lt;cert> ... &lt;/cert></code> tags."); ?></li>
                     <li><?php echo _("Private key of the client certificate (e.g. <code>client.key</code>): enclosed in <code>&lt;key> ... &lt;/key></code> tags."); ?></li>
                   </small>
                 </ul>
                </p>
              </div>
           </div> <!-- panel -->
         </div> <!-- panel-group -->
      </div> <!-- col -->
      <div class="col-sm-12 ">
         <div class="form-group">
            <h5 class="panel-title"><?php echo _("Configuration File"); ?></h4>
            <div class="custom-file">
             <input type="file" class="custom-file-input" name="customFile" id="customFile">
             <label class="custom-file-label" for="customFile"><?php echo _("Select OpenVPN configuration file (.ovpn)"); ?></label>
           </div>
         </div>
      </div> <!-- col -->
    </div><!-- col-8 -->
    <div class="col-sm-auto"></div>
  </div><!-- /.row -->
</div><!-- /.tab-pane | general tab -->

