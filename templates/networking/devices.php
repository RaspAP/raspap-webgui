<div role="tabpanel" class="tab-pane fade in" id="netdevices">
  <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
    <h4 class="mb-0"><?php echo _("Network devices") ?></h4>
    <button type="button" onClick="window.location.reload();" class="btn btn-sm btn-outline-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></button>
  </div>
  <div class="row">
   <div class="col-sm-12">
    <div class="card">
      <div class="card-body">
        <form id="frm-netdevices">
          <?php echo \RaspAP\Tokens\CSRF::hiddenField();?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th class="w-25"><?php echo _("Device"); ?></th>
                  <th><?php echo _("Interface"); ?></th>
                  <th></th>
                  <th><?php echo _("MAC address"); ?></th>
                  <th><?php echo _("USB vid/pid"); ?></th>
                  <th><?php echo _("Device type"); ?></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($deviceData as $dev): ?>
                <tr>
                  <td><?= htmlspecialchars($dev['vendor'] . ' ' . $dev['model']) ?></td>
                  <td><?= htmlspecialchars($dev['name']) ?></td>
                  <td><?= $dev['is_ap'] ? '<i class="fas fa-bullseye text-secondary mt-1"></i>' : '' ?></td>
                  <td><input type="text" class="form-control mac_address" name="int-new-mac-<?= htmlspecialchars($dev['name']) ?>" id="int-new-mac-<?= $dev['name'] ?>" value="<?= htmlspecialchars($dev['mac']) ?>"></td>
                  <td><?= $dev['vid'] && $dev['pid'] ? $dev['vid'].'/'.$dev['pid'] : '-' ?></td>
                  <?php
                      $typeEnable = !empty($dev['vid']) && !empty($dev['pid']);
                      $isDisabled = $dev['is_static'] || !$typeEnable;
                      ?>
                      <td>
                        <select
                          class="selectpicker form-select"
                          name="int-new-type-<?= htmlspecialchars($dev['name']) ?>"
                          id="int-new-type-<?= htmlspecialchars($dev['name']) ?>"
                          <?= $isDisabled ? 'disabled' : '' ?>
                        >
                          <?php foreach ($dev['device_type_options'] as $opt): ?>
                            <option
                              value="<?= htmlspecialchars($opt['value']) ?>"
                              <?= $opt['selected'] ?>
                            ><?= htmlspecialchars($opt['label']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                  <td>
                    <input type="hidden" name="int-vid-<?= htmlspecialchars($dev['name']) ?>" id="int-vid-<?= $dev['name'] ?>" value="<?= htmlspecialchars($dev['vid']) ?>">
                    <input type="hidden" name="int-pid-<?= htmlspecialchars($dev['name']) ?>" id="int-pid-<?= $dev['name'] ?>" value="<?= htmlspecialchars($dev['pid']) ?>">
                    <button type="button" class="btn btn-primary intsave" data-opts="<?= htmlspecialchars($dev['name']) ?>" data-int="netdevices"><?php echo _("Update"); ?>
                  </td>
                </tr>
              <?php endforeach ?>
             </tbody>
           </table>
          </div>
        </form>
      </div>
     </div>
   </div>
  </div>
</div><!-- /.tab-panel -->

