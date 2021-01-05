  @extends('layouts.app')
  @section('content')
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col">
              <i class="far fa-hand-paper mr-2"></i>{{ _("Ad Blocking") }}
            </div>
            <div class="col">
              <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-{{ $serviceStatus  }}"></i></span>
                <span class="text service-status">adblock {{ _($serviceStatus)  }}</span>
              </button>
            </div>
          </div><!-- /.row -->
        </div><!-- /.card-header -->
        <div class="card-body">
        {!! $status->showMessages() !!}
          <form role="form" action="adblock_conf" enctype="multipart/form-data" method="POST">
            {!! CSRFTokenFieldTag() !!}
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" id="blocklisttab" href="#adblocklistsettings" data-toggle="tab">{{ _("Blocklist settings") }}</a></li>
                <li class="nav-item"><a class="nav-link" id="customtab" href="#adblockcustom" data-toggle="tab">{{ _("Custom blocklist") }}</a></li>
                <li class="nav-item"><a class="nav-link" id="logoutputtab" href="#adblocklogfileoutput" data-toggle="tab">{{ _("Logging") }}</a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
              @include("adblock.general")
              @include("adblock.stats")
              @include("adblock.custom")
              @include("adblock.logging")
            </div><!-- /.tab-content -->

            @if (!RASPI_MONITOR_ENABLED)
              <input type="submit" class="btn btn-outline btn-primary" name="saveadblocksettings" value="{{ _("Save settings") }}">
                @if ($dnsmasq_state)
                  <input type="submit" class="btn btn-warning" name="restartadblock" value="{{ _("Restart Ad Blocking") }}">
                @else
                  <input type="submit" class="btn btn-success" name="startadblock" value="{{ _("Start Ad Blocking") }}">
                @endif
            @endif
    
          </form>
        </div><!-- /.card-body -->
        <div class="card-footer">{{ _("Information provided by adblock") }}</div>
      </div><!-- /.card -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
  @endsection

