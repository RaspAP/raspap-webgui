<div class="tab-pane fade" id="openvpnconfigs">
  <h4 class="mt-3 mb-3"><?php echo _("Configurations"); ?></h4>
    <div class="openvpn-configs js-openvpn-configs-container">
          <?php foreach ($clients as $client) :
              if ($client == "login.conf") {
                  $label = file_get_meta(RASPI_OPENVPN_CLIENT_LOGIN,'#\sfilename\s(.*)');
                  $btn_class = "active";
              } else {
                  $label = trim(pathinfo($client, PATHINFO_FILENAME), "_login");
                  $client = $label;
                  $btn_class = "disabled";
              }
          ?>
          <div class="row openvpn-client-row js-openvpn-client-row mt-2">
          <div class="col-md-5 col-xs-5">
            <label><?php echo htmlspecialchars($label, ENT_QUOTES); ?></label>
          </div>
          <div class="col-md-2 col-xs-2">
            <button type="button" class="btn btn-outline-success <?php echo $btn_class; ?> js-activate-openvpn-client" data-record-id="<?php echo htmlspecialchars($client, ENT_QUOTES); ?>" data-toggle="modal" data-target="#ovpn-confirm-activate" /><i class="far fa-check-circle"></i></button>
          </div>
          <div class="col-md-2 col-xs-3">
            <button type="button" class="btn btn-outline-danger js-remove-openvpn-client" data-record-id="<?php echo htmlspecialchars($client, ENT_QUOTES); ?>" data-toggle="modal" data-target="#ovpn-confirm-delete" /><i class="far fa-trash-alt"></i></button>
          </div>
        </div><!-- ./row openvpn -->
      <?php endforeach ?>
   </div><!-- /.openvpn-configs -->
  <div class="mb-3">
  </div>
</div><!-- /.tab-pane | manage configs tab -->


