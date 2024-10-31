<div class="tab-pane active" id="providerclient">
  <h4 class="mt-3"><?php echo sprintf(_("%s settings"), $providerName) ;?></h4>
  <div class="row">
    <div class="col-lg-8">
      <div class="row mb-2">
         <div class="col-lg-12 mt-2 mb-2">
           <div class="row ms-1">
             <div class="info-item col-3">
               <i class="fas fa-globe-americas me-1"></i><?php echo _("IPv4 Address"); ?>
             </div>
             <div class="info-value col-4">
               <?php echo htmlspecialchars($publicIP, ENT_QUOTES); ?><a class="text-gray-500" href="https://ipapi.co/<?php echo($publicIP); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt ms-2"></i></a>
             </div>
           </div>
         </div>
      </div>
    </div>
  </div>
  <?php if (!empty($accountInfo)) : ?>
  <div class="row">
    <div class="col-md-6 mt-1">
      <div class="card">
	    <div class="card-body">
          <h5><?php echo _("Account details"); ?></h5>
            <?php foreach ($accountInfo as $item) {
                echo '<small>'. $item .'</small><br>';
            } ?>
            <a href="<?php echo($accountLink); ?>" target="_blank" class="btn btn-warning btn-sm mt-2"><i class="fas fa-external-link-alt ms-1 me-1"></i><?php echo _("My account") ?></a>
        </div><!-- /.card-body -->
      </div><!-- /.card -->
    </div>
  </div>
  <?php endif; ?>
  <div class="row">
    <div class="mb-3 col-md-6 mt-3">
      <h5><?php echo _("Server location"); ?></h5>
      <div>
        <small><?php echo _("Choosing <strong>Save settings</strong> will connect to the selected country."); ?></small>
      </div>
      <div class="mb-2">
        <small><?php echo sprintf(_("Choosing <strong>Connect %s</strong> will connect to a recommended server."), $providerName); ?></small>
      </div>
      <label for="cbxhwmode"><?php echo _("Country") ;?></label>
      <?php SelectorOptions('country', $countries, $country, 'cbxcountry'); ?>
    </div>
  </div>
</div><!-- /.tab-pane | general tab -->

