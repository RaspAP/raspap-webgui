<!-- about sponsors tab -->
<div class="tab-pane fade" id="aboutsponsors">
  <div class="row">
    <div class="col-lg-12 mt-3">
      <?php 
      $Parsedown = new Parsedown();
      $strContent = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/BACKERS.md');
      echo $Parsedown->text($strContent);
      ?>
    </div>
  </div><!-- /.row -->
</div><!-- /.tab-pane | sponsors tab -->

