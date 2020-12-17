<div class="tab-pane fade" id="security">
  <h4 class="mt-3">{{ _("Security settings") }}</h4>
  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label for="cbxwpa">{{ _("Security type") }}</label>
        @include('components.select', ['name'=>'wpa', 'options'=>$arrSecurity, 'selected'=>$arrConfig['wpa'], 'id'=>'cbxwpa'])
      </div>
      <div class="form-group">
        <label for="cbxwpapairwise">{{ _("Encryption Type") }}</label>
        @include('components.select', ['name'=>'wpa_pairwise', 'options'=>$arrEncType, 'selected'=>$arrConfig['wpa_pairwise'], 'id'=>'cbxwpapairwise'])
      </div>
      <label for="txtwpapassphrase">{{ _("PSK") }}</label>
      <div class="input-group">
        <input type="text" class="form-control" id="txtwpapassphrase" name="wpa_passphrase" value="{{ $arrConfig['wpa_passphrase'] }}" />
        <div class="input-group-append">
          <button class="btn btn-outline-secondary" type="button" id="gen_wpa_passphrase"><i class="fas fa-magic"></i></button>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <figure class="figure">
        <img src="app/img/wifi-qr-code.php" class="figure-img img-fluid" alt="RaspAP Wifi QR code" style="width:100%;">
        <figcaption class="figure-caption">{{ _("Scan this QR code with your phone to connect to this RaspAP.") }}</figcaption>
      </figure>
    </div>
  </div>
</div><!-- /.tab-pane | security tab -->
