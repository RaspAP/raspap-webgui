  <div role="tabpanel" class="tab-pane fade in" id="mobiledata">
    <div class="row">
      <div class="col-lg-6">
        <h4 class="mt-3"><?php echo _("Mobile data settings") ?></h4>
        <form id="frm-mobiledata">
          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
          <div class="mb-3">
            <label for="pin-mobile"><?php echo _("SIM card PIN number") ?></label>
            <input type="number" class="form-control" name="pin-mobile" id="pin-mobile" placeholder="0000" value="<?php echo htmlspecialchars($mobileData["pin"]); ?>" />
          </div>
          <h4 class="mt-3"><?php echo _("APN Settings") ?></h4>
          <div class="mb-3">
            <label for="apn-mobile"><?php echo _("Access Point Name (APN)") ?></label>
            <input type="text" class="form-control" name="apn-mobile" id="apn-mobile" placeholder="web.myprovider.com" value="<?php echo htmlspecialchars($mobileData["apn"]); ?>" />
          </div>
          <div class="mb-3">
            <label for="apn-user-mobile"><?php echo _("Username") ?></label>
            <input type="text" class="form-control" name="apn-user-mobile" id="apn-user-mobile" value="<?php echo htmlspecialchars($mobileData["apn_user"]); ?>" />
          </div>
          <div class="mb-3">
            <label for="apn-pw-mobile"><?php echo _("Password") ?></label>
            <div class="input-group">
              <input type="password" class="form-control" id="apn-pw-mobile" name="apn-pw-mobile" value="<?php echo htmlspecialchars($mobileData["apn_pw"]); ?>" />
              <div class="input-group-text js-toggle-password" data-bs-target="[name=apn-pw-mobile]" data-toggle-with="fas fa-eye-slash">
                <i class="fas fa-eye mx-2"></i>
              </div>
            </div>
          </div>
          <a href="#" class="btn btn-outline-primary intsave" data-int="mobiledata"><?php echo _("Save settings") ?></a>
        </form>
      </div>
    </div>
  </div><!-- /.tab-panel -->

