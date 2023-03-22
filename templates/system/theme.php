<!-- theme tab -->
<div role="tabpanel" class="tab-pane" id="theme">
  <h4 class="mt-3"><?php echo _("Change theme") ;?></h4>
    <div class="row">
    <div class="form-group col-xs-3 col-sm-3">
      <label for="code"><?php echo _("Select a theme"); ?></label>
      <?php SelectorOptions("theme", $themes, $selectedTheme, "theme-select") ?>
    </div>
    <div class="col-xs-3 col-sm-3">
      <label for="code"><?php echo _("Color"); ?></label>
      <input class="form-control color-input" value="#2b8080" aria-label="color" />
    </div>
    </div>
    <form action="system_info" method="POST">
    <?php echo CSRFTokenFieldTag() ?>
    <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
    </form>
</div>


