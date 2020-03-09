<div class="tab-pane fade" id="client-list">
  <h4 class="mt-3 mb-3">Client list</h4>
  <div class="row">
    <div class="col-lg-12">
      <div class="card mb-3">
        <div class="card-header"><?php echo _("Active DHCP leases"); ?></div>
        <!-- /.panel-heading -->
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th><?php echo _("Expire time"); ?></th>
                  <th><?php echo _("MAC Address"); ?></th>
                  <th><?php echo _("IP Address"); ?></th>
                  <th><?php echo _("Host name"); ?></th>
                  <th><?php echo _("Client ID"); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($leases as $lease) : ?>
                <tr>
                  <?php foreach (explode(' ', $lease) as $prop) : ?>
                  <td><?php echo htmlspecialchars($prop, ENT_QUOTES) ?></td>
                  <?php endforeach ?>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div><!-- /.table-responsive -->
        </div><!-- /.card-body -->
      </div><!-- /.card -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
</div><!-- /.tab-pane -->
