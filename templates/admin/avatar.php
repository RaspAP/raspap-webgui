<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" class="btn btn-warning" name="resetAvatar" value="<?php echo _("Reset avatar"); ?>" />
  <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<!-- avatar tab -->
<div class="tab-pane" id="authavatar">
  <div class="row">
    <div class="mb-3 col-md-6">
      <h4 class="mt-3"><?php echo _("Avatar"); ?></h4>
      <form role="form" id="avatarForm" method="post" enctype="multipart/form-data">
        <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
        <div class="mb-3">
          <?php echo _("Click or tap to upload a new user avatar."); ?><br />
          <small class="text-muted"><?php echo _("Image files of type <code>JPG, GIF or PNG</code> are accepted. Max file size: 2 MB."); ?></small>
        </div>
        <label class="avatar m-3" for="uploadAvatar">
          <?php echo $avatar; ?>
          <span class="camera">
            <i class="fa fa-camera"></i>
          </span>
          <input type="file" name="uploadAvatar" id="uploadAvatar" class="upload-avatar" accept="image/*" style="display: none;">
        </label>
        <div><?php echo $buttons; ?></div>
      </form>
    </div>
  </div>
</div><!-- /.tab-pane | avatar tab -->

