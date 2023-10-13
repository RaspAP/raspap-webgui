<div class="tab-pane active" id="providerclient">
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
    </div><!-- col-8 -->
    <div class="col-sm-auto"></div>
  </div><!-- /.row -->
</div><!-- /.tab-pane | general tab -->

