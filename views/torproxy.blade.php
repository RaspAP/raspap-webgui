@extends('layouts.app')
@section('content')
    <div class="row">
    <div class="col-lg-12">
      <div class="card"> 
        <div class="card-header"><i class="fa fa-eye-slash fa-fw"></i> TOR proxy</div>
        <div class="card-body">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" href="#basic" data-toggle="tab">Basic</a></li>
                <li class="nav-item"><a class="nav-link" href="#relay" data-toggle="tab">Relay</a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                {!! $status !!}

                <div class="tab-pane active" id="basic">
                    <h4>Basic settings</h4>
                    <form role="form" action="save_hostapd_conf" method="POST">
                    {!! CSRFTokenFieldTag() !!}
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">VirtualAddrNetwork</label>
                            <input type="text" class="form-control" name="virtualaddrnetwork" value="{{ $arrConfig['VirtualAddrNetwork'] }}" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">AutomapHostsSuffixes</label>
                            <input type="text" class="form-control" name="automaphostssuffixes" value="{{ $arrConfig['AutomapHostsSuffixes'] }}" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">AutomapHostsOnResolve</label>
                            <input type="text" class="form-control" name="automaphostsonresolve" value="{{ $arrConfig['AutomapHostsOnResolve'] }}" />
                        </div>
                    </div>  
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">TransListenAddress</label>
                            <input type="text" class="form-control" name="translistenaddress" value="{{ $arrConfig['TransListenAddress'] }}" />
                        </div>
                    </div>  
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">DNSPort</label>
                            <input type="text" class="form-control" name="dnsport" value="{{ $arrConfig['DNSPort'] }}" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">DNSListenAddress</label>
                            <input type="text" class="form-control" name="dnslistenaddress" value="{{ $arrConfig['DNSListenAddress'] }}" />
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="relay">
                    <h4>Relay settings</h4>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">ORPort</label>
                            <input type="text" class="form-control" name="orport" value="{{ $arrConfig['ORPort'] }}" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">ORListenAddress</label>
                            <input type="text" class="form-control" name="orlistenaddress" value="{{ $arrConfig['ORListenAddress'] }}" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">Nickname</label>
                            <input type="text" class="form-control" name="nickname" value="{{ $arrConfig['Nickname'] }}" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">Address</label>
                            <input type="text" class="form-control" name="address" value="{{ $arrConfig['Address'] }}" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">RelayBandwidthRate</label>
                            <input type="text" class="form-control" name="relaybandwidthrate" value="{{ $arrConfig['RelayBandwidthRate'] }}" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="code">RelayBandwidthBurst</label>
                            <input type="text" class="form-control" name="relaybandwidthburst" value="{{ $arrConfig['RelayBandwidthBurst'] }}" />
                        </div>
                    </div>
                </div>

                <input type="submit" class="btn btn-outline btn-primary" name="SaveTORProxySettings" value="Save settings" />
                @if ($torproxystatus[0] == 0) 
                    <input type="submit" class="btn btn-success" name="StartTOR" value="Start TOR" />
                @else
                    <input type="submit" class="btn btn-warning" name="StopTOR" value="Stop TOR" />
                @endif
                </form>
            </div><!-- /.tab-content -->
        </div><!-- /.card-body -->
        <div class="card-footer"> Information provided by tor</div>
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->
@endsection

