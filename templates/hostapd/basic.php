<div class="tab-pane active" id="basic">
  <div class="d-flex">
    <h4 class="mt-3"><?php echo _("Basic settings") ;?></h4>
    <a href="https://docs.raspap.com/ap-basics/#basics" target="_blank"><i class="fas fa-book ml-2 mt-4 text-muted"></i></a>
  </div>
  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="cbxinterface"><?php echo _("Interface") ;?></label>
      <?php SelectorOptions('interface', $interfaces, $arrConfig['interface'], 'cbxinterface', 'getChannel'); ?>
    </div>
  </div>
  <div class="row">
    <div class="mb-3 col-md-6" required>
      <label for="txtssid"><?php echo _("SSID"); ?></label>
      <input type="text" id="txtssid" class="form-control" name="ssid" value="<?php echo htmlspecialchars($arrConfig['ssid'], ENT_QUOTES); ?>" required />
      <div class="invalid-feedback">
        <?php echo _("Please provide a valid SSID."); ?>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="cbxhwmode"><?php echo _("Wireless Mode") ;?></label>
      <?php SelectorOptions('hw_mode', $arr80211Standard, $arrConfig['selected_hw_mode'], 'cbxhwmode', 'getChannel'); ?>
    </div>
  </div>
  <div class="row">
    <div class="mb-3 col-md-6">
      <label for="cbxchannel"><?php echo _("Channel"); ?></label>
      <?php
      $selectablechannels = Array();
      SelectorOptions('channel', $selectablechannels, intval($arrConfig['channel']), 'cbxchannel'); ?>
    </div>
  </div>
</div><!-- /.tab-pane | basic tab -->
