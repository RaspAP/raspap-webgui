<div class="tab-pane active" id="server-settings">
  <h4 class="mt-3">DHCP server settings</h4>
  <div class="row">
    <div class="form-group col-md-6">
      <label for="code">Interface</label>
      <select class="form-control" name="interface">
        <?php foreach ($interfaces as $if) : ?>
          <?php $if_quoted = htmlspecialchars($if, ENT_QUOTES) ?>
          <?php $selected  = $if === $conf['interface'] ? ' selected="selected"' : '' ?>
          <option value="<?php echo $if_quoted ?>"<?php echo $selected ?>><?php echo $if_quoted ?></option>
        <?php endforeach ?>
      </select>
    </div>
  </div>
  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("Starting IP Address"); ?></label>
      <input type="text" class="form-control"name="RangeStart" value="<?php echo htmlspecialchars($RangeStart, ENT_QUOTES); ?>" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("Ending IP Address"); ?></label>
      <input type="text" class="form-control" name="RangeEnd" value="<?php echo htmlspecialchars($RangeEnd, ENT_QUOTES); ?>" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-xs-3 col-sm-3">
      <label for="code"><?php echo _("Lease Time"); ?></label>
      <input type="text" class="form-control" name="RangeLeaseTime" value="<?php echo htmlspecialchars($arrRangeLeaseTime[1], ENT_QUOTES); ?>" />
    </div>
    <div class="col-xs-3 col-sm-3">
      <label for="code"><?php echo _("Interval"); ?></label>
      <select name="RangeLeaseTimeUnits" class="form-control" >
        <option value="m"<?php echo $mselected; ?>><?php echo _("Minute(s)"); ?></option>
        <option value="h"<?php echo $hselected; ?>><?php echo _("Hour(s)"); ?></option>
        <option value="d"<?php echo $dselected; ?>><?php echo _("Day(s)"); ?></option>
        <option value="infinite"<?php echo $infiniteselected; ?>><?php echo _("Infinite"); ?></option>
      </select>
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("DNS Server"); ?> 1</label>
      <input type="text" class="form-control"name="DNS1" value="<?php echo htmlspecialchars($DNS1, ENT_QUOTES); ?>" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code"><?php echo _("DNS Server"); ?> 2</label>
      <input type="text" class="form-control" name="DNS2" value="<?php echo htmlspecialchars($DNS2, ENT_QUOTES); ?>" />
    </div>
  </div>

</div><!-- /.tab-pane -->
