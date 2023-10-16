<div class="tab-pane active" id="providerclient">
  <h4 class="mt-3"><?php echo sprintf(_("%s settings"), $providerName) ;?></h4>
  <div class="row">
    <div class="col-lg-8">
      <div class="row mb-2">
         <div class="col-lg-12 mt-2 mb-2">
           <div class="row ml-1">
             <div class="info-item col-xs-3">
               <i class="fas fa-globe mr-1"></i><?php echo _("IPv4 Address"); ?>
             </div>
             <div class="info-value col-xs-3">
               <?php echo htmlspecialchars($publicIP, ENT_QUOTES); ?><a class="text-gray-500" href="https://ipapi.co/<?php echo($publicIP); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt ml-2"></i></a>
             </div>
           </div>
         </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mt-1">
      <div class="card">
	    <div class="card-body">

          <h5><?php echo _("Account details"); ?></h5>
            <?php foreach ($accountInfo as $item) {
                echo '<small>'. $item .'</small><br>';
            } ?>
            <input type="button" class="btn btn-warning mt-2" data-toggle="modal" data-target="#provider-confirm-logout" value="<?php echo _("Logout"); ?>" />
        </div><!-- /.card-body -->
      </div><!-- /.card -->
    </div>
  </div>
  <div class="row">
    <div class="form-group col-md-6 mt-3">
      <h5><?php echo _("Server location"); ?></h5>
      <label for="cbxhwmode"><?php echo _("Country") ;?></label>
        <?php SelectorOptions('countries', $countries, $arrConfig['country'], 'cbxcountry'); ?>
    </div>
  </div>
</div><!-- /.tab-pane | general tab -->

