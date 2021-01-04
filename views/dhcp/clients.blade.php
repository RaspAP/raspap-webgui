<div class="tab-pane fade" id="client-list">
  <h4 class="mt-3 mb-3">Client list</h4>
  <div class="row">
    <div class="col-lg-12">
      <div class="card mb-3">
        <div class="card-header">{{ _("Active DHCP leases") }}</div>
        <!-- /.panel-heading -->
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>{{ _("Expire time") }}</th>
                  <th>{{ _("MAC Address") }}</th>
                  <th>{{ _("IP Address") }}</th>
                  <th>{{ _("Host name") }}</th>
                  <th>{{ _("Client ID") }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($leases as $lease)
                <tr>
                  @foreach (explode(' ', $lease) as $prop)
                  <td>{{ $prop }}</td>
                  @endforeach
                </tr>
                @endforeach
              </tbody>
            </table>
          </div><!-- /.table-responsive -->
        </div><!-- /.card-body -->
      </div><!-- /.card -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
</div><!-- /.tab-pane -->
