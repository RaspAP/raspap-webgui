@extends('layouts.app')
@section('content')
<div class="row">
  <div class="col-lg-12">
   <div class="card">
    <div class="card-header">
      <div class="row">
        <div class="col">
          <i class="fas fa-network-wired mr-2"></i>{{ _("Networking") }}
        </div>
      </div><!-- ./row -->
     </div><!-- ./card-header -->
      <div class="card-body">
        <div id="msgNetworking"></div>
        <ul class="nav nav-tabs">
          <li role="presentation" class="nav-item"><a class="nav-link active" href="#summary" aria-controls="summary" role="tab" data-toggle="tab">{{ _("Summary") }}</a></li>
          @if (!$bridgedEnabled) {{-- No interface details when bridged.  $bridgedEnabled is injected in renderTemplate() --}}
                @foreach ($interfaces as $interface)
                  <li role="presentation" class="nav-item"><a class="nav-link" href="#{{ $interface }}" aria-controls="{{ $interface }}" role="tab" data-toggle="tab">{{ $interface }}</a></li>
                @endforeach
          @endif
        </ul>
        <div class="tab-content">
          <div role="tabpanel" class="tab-pane active" id="summary">
            <h4 class="mt-3">{{ _("Internet connection") }}</h4>
            <div class="row">
             <div class="col-sm-12"">
              <div class="card ">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>{{ _("Interface") }}</th>
                          <th>{{ _("IP Address") }}</th>
                          <th>{{ _("Gateway") }}</th>
                          <th colspan="2">{{ _("Internet Access") }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        @if (isset($routeInfo["error"]) || empty($routeInfo)) 
                            <tr><td colspan=5>No route to the internet found</td></tr>
                        @else
                            @foreach($rInfo as $route) 
                              <tr>
                                <td>{{ $route["interface"] }}</td>
                                <td>{{ $route["ip-address"] }}</td>
                                <td>{{ $route["gateway"] }}<br>{{$route["gw-name"]}}</td>
                                <td><i class="fas {{ $route["access-ip"] ? "fa-check" : "fa-times" }}"></i><br>{{ RASPI_ACCESS_CHECK_IP }}</td>
                                <td><i class="fas {{ $route["access-dns"] ? "fa-check" : "fa-times" }}"></i><br>{{ RASPI_ACCESS_CHECK_DNS }}</td>
                              </tr>
                            @endforeach
                        @endif
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
             </div>
            </div>
            <h4 class="mt-3">{{ _("Current settings")  }}</h4>
            <div class="row">
              @foreach ($interfaces as $interface)
              <div class="col-md-6 mb-3">
                <div class="card">
                  <div class="card-header">{{ $interface }}</div>
                  <div class="card-body">
                    <pre class="unstyled" id="{{ $interface }}-summary"></pre>
                  </div>
                </div>
              </div>
              @endforeach
            </div><!-- /.row -->
            <div class="col-lg-12">
              <div class="row">
                <a href="#" class="btn btn-outline btn-primary" id="btnSummaryRefresh"><i class="fas fa-sync-alt"></i> {{ _("Refresh") }}</a>
              </div><!-- /.row -->
            </div><!-- /.col-lg-12 -->
          </div><!-- /.tab-pane -->

          @foreach ($interfaces as $interface)
          <div role="tabpanel" class="tab-pane fade in" id="{{ $interface }}">
            <div class="row">
              <div class="col-lg-6">

                <form id="frm-{{ $interface }}">
                  {!! CSRFTokenFieldTag() !!}
                  <div class="form-group">
                    <h4 class="mt-3">{{ _("Adapter IP Address Settings")  }}</h4>
                    <div class="btn-group" role="group" data-toggle="buttons">
                      {{-- FIXME: (mp035) these radio buttons do not currently reflect the address type when the page loads. --}}
                      <label class="btn btn-primary">
                        <input class="mr-2" type="radio" name="{{ $interface }}-addresstype" id="{{ $interface }}-dhcp" autocomplete="off">{{ _("DHCP")  }}
                      </label>
                      <label class="btn btn-primary">
                        <input class="mr-2" type="radio" name="{{ $interface }}-addresstype" id="{{ $interface }}-static" autocomplete="off">{{ _("Static IP")  }}
                      </label>
                    </div><!-- /.btn-group -->
                    <h4 class="mt-3">{{ _("Enable Fallback to Static Option")  }}</h4>
                    <div class="btn-group" role="group" data-toggle="buttons">
                      <label class="btn btn-primary">
                        <input class="mr-2" type="radio" name="{{ $interface }}-dhcpfailover" id="{{ $interface }}-failover" autocomplete="off">{{ _("Enabled")  }}
                      </label>
                      <label class="btn btn-warning">
                        <input class="mr-2" type="radio" name="{{ $interface }}-dhcpfailover" id="{{ $interface }}-nofailover" autocomplete="off">{{ _("Disabled")  }}
                      </label>
                    </div><!-- /.btn-group -->
                  </div><!-- /.form-group -->

                  <hr />

                  <h4>{{ _("Static IP Options")  }}</h4>
                  <div class="form-group">
                    <label for="{{ $interface }}-ipaddress">{{ _("IP Address")  }}</label>
                    <input type="text" class="form-control" id="{{ $interface }}-ipaddress" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="{{ $interface }}-netmask">{{ _("Subnet Mask")  }}</label>
                    <input type="text" class="form-control" id="{{ $interface }}-netmask" placeholder="255.255.255.0">
                  </div>
                  <div class="form-group">
                    <label for="{{ $interface }}-gateway">{{ _("Default Gateway")  }}</label>
                    <input type="text" class="form-control" id="{{ $interface }}-gateway" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="{{ $interface }}-dnssvr">{{ _("DNS Server")  }}</label>
                    <input type="text" class="form-control" id="{{ $interface }}-dnssvr" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="{{ $interface }}-dnssvralt">{{ _("Alternate DNS Server")  }}</label>
                    <input type="text" class="form-control" id="{{ $interface }}-dnssvralt" placeholder="0.0.0.0">
                  </div>
                  <div class="form-group">
                    <label for="{{ $interface }}-metric">{{ _("Metric")  }}</label>
                    <input type="text" class="form-control" id="{{ $interface }}-metric" placeholder="0">
                  </div>
                  @if (!RASPI_MONITOR_ENABLED)
                      <a href="#" class="btn btn-outline btn-primary intsave" data-int="{{ $interface }}">{{ _("Save settings")  }}</a>
                      <a href="#" class="btn btn-warning intapply" data-int="{{ $interface }}">{{ _("Apply settings")  }}</a>
                  @endif
                </form>

              </div>
            </div><!-- /.tab-panel -->
          </div>
          @endforeach

        </div><!-- /.tab-content -->
      </div><!-- /.card-body -->
      <div class="card-footer">{{ _("Information provided by /sys/class/net") }}</div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div>
@endsection
