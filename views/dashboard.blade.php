@extends('layouts.app')

@section('content')
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
    <div class="col">
      <i class="fas fa-tachometer-alt fa-fw mr-2"></i>{{ _("Dashboard") }}
    </div>
    <div class="col">
      <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
        <span class="icon"><i class="fas fa-circle service-status-{{$ifaceStatus}}"></i></span>
        <span class="text service-status">{{strtolower($apInterface) .' '. _($ifaceStatus)}}</span>
      </button>
    </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <div class="row">

          <div class="col-lg-12">
            <div class="card mb-3">
              <div class="card-body">
                <h4>{{ _("Hourly traffic amount") }}</h4>
                <div id="divInterface" class="d-none">{{ $apInterface }}</div>
                <div class="col-md-12">
                  <canvas id="divDBChartBandwidthhourly"></canvas>
                </div>
              </div><!-- /.card-body -->
            </div><!-- /.card-->
          </div>

          <div class="col-sm-6 align-items-stretch">
            <div class="card h-100">
              <div class="card-body wireless">
                <h4>{{ _("Wireless Client") }}</h4>
                <div class="row justify-content-md-center">
                <div class="col-md">
                  <div class="info-item">{{ _("Connected To") }}</div><div>{{ $connectedSSID }}</div>
                  <div class="info-item">{{ _("Interface") }}</div><div>{{ $clientInterface }}</div>
                  <div class="info-item">{{ _("AP Mac Address") }}</div><div>{{ $connectedBSSID }}</div>
                  <div class="info-item">{{ _("Bitrate") }}</div><div>{{ $bitrate }}</div>
                  <div class="info-item">{{ _("Signal Level") }}</div><div>{{ $signalLevel }}</div>
                  <div class="info-item">{{ _("Transmit Power") }}</div><div>{{ $txPower }}</div>
                  <div class="info-item">{{ _("Frequency") }}</div><div>{{ $frequency }}</div>
                </div>
              <div class="col-md mt-2 d-flex justify-content-center">
                <script>var linkQ = {!!json_encode($strLinkQuality)!!};</script>
                <div class="chart-container">
                  <canvas id="divChartLinkQ"></canvas>
                </div>
                </div><!--row-->
              </div>
             </div><!-- /.card-body -->
            </div><!-- /.card -->
          </div><!-- /.col-md-6 -->
          <div class="col-sm-6">
            <div class="card h-100 mb-3">
              <div class="card-body">
                <h4>{{ _("Connected Devices") }}</h4>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        @if ($bridgedEnable)
                          <th>{{ _("MAC Address") }}</th>
                        @else
                          <th>{{ _("Host name") }}</th>
                          <th>{{ _("IP Address") }}</th>
                          <th>{{ _("MAC Address") }}</th>
                        @endif
                      </tr>
                    </thead>
                    <tbody>
                        @if ($bridgedEnable)
                          <tr>
                            <td><small class="text-muted">{{ _("Bridged AP mode is enabled. For Hostname and IP, see your router's admin page.") }}</small></td>
                          </tr>
                        @endif
                        @foreach(array_slice($clients,0, 2) as $client)
                        <tr>
                          @if ($bridgedEnable)
                            <td>{{ $client }}</td>
                          @else
                            <?php $props = explode(' ', $client) ?>
                            <td>{{ $props[3] }}</td>
                            <td>{{ $props[2] }}</td>
                            <td>{{ $props[1] }}</td>
                          @endif
                        </tr>
                        @endforeach
                    </tbody>
                  </table>
                  @if (sizeof($clients) >2)
                      <div class="col-lg-12 float-right">
                        <a class="btn btn-outline-info" role="button" href="{!! $moreLink !!}">{{ _("More") }}  <i class="fas fa-chevron-right"></i></a>
                      </div>
                  @elseif (sizeof($clients) == 0)
                      <div class="col-lg-12 mt-3">{{ _("No connected devices") }}</div>
                  @endif
                </div><!-- /.table-responsive -->
              </div><!-- /.card-body -->
            </div><!-- /.card -->
          </div><!-- /.col-md-6 -->
        </div><!-- /.row -->

        <div class="col-lg-12 mt-3">
          <div class="row">
            <form action="wlan0_info" method="POST">
                {!! CSRFTokenFieldTag() !!}
                @if (!RASPI_MONITOR_ENABLED)
                    @if (!$wlan0up)
                    <input type="submit" class="btn btn-success" value="{{ _("Start").' '.$clientInterface }}" name="ifup_wlan0" />
                    @else
                    <input type="submit" class="btn btn-warning" value="{{ _("Stop").' '.$clientInterface }}"  name="ifdown_wlan0" />
                    @endif
                @endif
              <a href="." class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> {{ _("Refresh") }}</a>
            </form>
          </div>
        </div>

      </div><!-- /.card-body -->
      <div class="card-footer">{{ _("Information provided by ip and iw and from system") }}</div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

@endsection

@section('footer_scripts')

<script type="text/javascript"{{-- nonce="{!! $csp_page_nonce !!}" --}}>
// js translations:
var t = new Array();
t['send'] = '{!! addslashes(_('Send')) !!}';
t['receive'] = '{!! addslashes(_('Receive')) !!}';
</script>

<script type="text/javascript" src="app/js/dashboardchart.js"></script> 
<script type="text/javascript" src="app/js/linkquality.js"></script> 

@endsection
