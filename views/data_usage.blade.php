@extends('layouts.app')
@section('content')
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-chart-bar mr-2"></i>{{ _("Data usage monitoring") }}
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <ul id="tabbarBandwidth" class="nav nav-tabs" role="tablist">
          <li class="nav-item"><a class="nav-link active" href="#hourly" aria-controls="hourly" role="tab" data-toggle="tab">{{ _("Hourly") }}</a></li>
          <li class="nav-item"><a class="nav-link" href="#daily" aria-controls="daily" role="tab" data-toggle="tab">{{ _("Daily") }}</a></li>
          <li class="nav-item"><a class="nav-link" href="#monthly" aria-controls="monthly" role="tab" data-toggle="tab">{{ _("Monthly") }}</a></li>
        </ul>
        <div id="tabsBandwidth" class="tabcontenttraffic tab-content">
          <div role="tabpanel" class="tab-pane active" id="hourly">
            <div class="row">
              <div class="col-lg-12">
                <h4 class="mt-3">{{ _('Hourly traffic amount') }}</h4>
                <label for="cbxInterfacehourly">{{ _('interface') }}</label>
                <select id="cbxInterfacehourly" class="form-control" name="interfacehourly">
                  @foreach ($interfaces as $interface)
                    <option value="{{ $interface }}">{{ $interface }}</option>
                  @endforeach
                </select>
                <div class="hidden alert alert-info" id="divLoaderBandwidthhourly">
                  {{ sprintf(_("Loading %s bandwidth chart"), _('hourly')) }}
                </div>
                <canvas id="divChartBandwidthhourly"></canvas>
                <div id="divTableBandwidthhourly"></div>
              </div>
            </div>
          </div><!-- /.tab-pane -->
          <div role="tabpanel" class="tab-pane fade" id="daily">
            <div class="row">
              <div class="col-lg-12">
                <h4 class="mt-3">{{ _('Daily traffic amount') }}</h4>
                <label for="cbxInterfacedaily">{{ _('interface') }}</label>
                <select id="cbxInterfacedaily" class="form-control" name="interfacedaily">
                  @foreach ($interfaces as $interface)
                    <option value="{{ $interface }}">{{ $interface }}</option>
                  @endforeach
                </select>
                <div class="hidden alert alert-info" id="divLoaderBandwidthdaily">
                  {{ sprintf(_("Loading %s bandwidth chart"), _('daily')) }}
                </div>
                <canvas id="divChartBandwidthdaily"></canvas>
                <div id="divTableBandwidthdaily"></div>
              </div>
            </div>
          </div><!-- /.tab-pane -->
          <div role="tabpanel" class="tab-pane fade" id="monthly">
            <div class="row">
              <div class="col-lg-12">
                <h4 class="mt-3">{{ _("Monthly traffic amount") }}</h4>
                <label for="cbxInterfacemonthly">{{ _('interface') }}</label>
                <select id="cbxInterfacemonthly" class="form-control" name="interfacemonthly">
                  @foreach ($interfaces as $interface)
                    <option value="{{ $interface }}">{{ $interface }}</option>
                  @endforeach
                </select>
                <div class="hidden alert alert-info" id="divLoaderBandwidthmonthly">
                  {{ sprintf(_("Loading %s bandwidth chart"), _('monthly')) }}
                </div>
                <canvas id="divChartBandwidthmonthly"></canvas>
                <div id="divTableBandwidthmonthly"></div>
              </div>
            </div>
          </div><!-- /.tab-pane -->
        </div><!-- /.tabsBandwidth -->
       </div><!-- /.card-body -->
       <div class="card-footer">{{ _("Information provided by vnstat") }}</div>
     </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
<script type="text/javascript"<?php //echo ' nonce="'.$csp_page_nonce.'"'; ?>>
// js translations:
var t = new Array();
t['send'] = '{!! addslashes(_('Send')) !!}';
t['receive'] = '{!! addslashes(_('Receive')) !!}';
</script>

@endsection

@section('footer_scripts')
    <script src='dist/datatables/jquery.dataTables.min.js'></script>
    <script src='app/js/bandwidthcharts.js'></script>
@endsection
