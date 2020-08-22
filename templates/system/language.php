<!-- language tab -->  
<div role="tabpanel" class="tab-pane" id="language">
    <h4 class="mt-3"><?php echo _("Language settings") ;?></h4>
    <div class="row">
      <div class="form-group col-md-6">
        <label for="code"><?php echo _("Select a language"); ?></label>
        <?php SelectorOptions('locale', $arrLocales, $_SESSION['locale']); ?>
      </div>
    </div>
    <input type="submit" class="btn btn-outline btn-primary" name="SaveLanguage" value="<?php echo _("Save settings"); ?>" />
    <a href="?page=<?php echo $_GET['page'] ?>" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
</div>

