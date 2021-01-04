@extends('layouts.app')
@section('content')
<div class="row">
  <div class="col-lg-12">
    <div class="card">

      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-exchange-alt mr-2"></i>{{ _("DHCP Server") }}
          </div>
          <div class="col">
            <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
              <span class="icon text-gray-600"><i class="fas fa-circle service-status-{{ $serviceStatus  }}"></i></span>
              <span class="text service-status">dnsmasq {{ _($serviceStatus)  }}</span>
            </button>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        {!! $status->showMessages() !!}
        <form method="POST" action="dhcpd_conf" class="js-dhcp-settings-form">
          {!! CSRFTokenFieldTag() !!}

          <!-- Nav tabs -->
          <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link active" href="#server-settings" data-toggle="tab">{{ _("Server settings") }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#advanced" data-toggle="tab">{{ _("Advanced") }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#static-leases" data-toggle="tab">{{ _("Static Leases")  }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#client-list" data-toggle="tab">{{ _("Client list") }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#logging" data-toggle="tab">{{ _("Logging") }}</a></li>
          </ul>

          <!-- Tab panes -->
          <div class="tab-content">
            @include("dhcp.general")
            @include("dhcp.advanced")
            @include("dhcp.clients")
            @include("dhcp.static_leases")
            @include("dhcp.logging")
          </div><!-- /.tab-content -->

          @if(!RASPI_MONITOR_ENABLED)
            <input type="submit" class="btn btn-outline btn-primary" value="{{ _("Save settings") }}" name="savedhcpdsettings" />
            @if($dnsmasq_state)
              <input type="submit" class="btn btn-warning" value="{{ _("Stop dnsmasq")  }}" name="stopdhcpd" />
            @else
              <input type="submit" class="btn btn-success" value="{{ _("Start dnsmasq")  }}" name="startdhcpd" />
            @endif
          @endif
        </form>
      </div><!-- ./ card-body -->

      <div class="card-footer"> {{ _("Information provided by Dnsmasq") }}</div>

    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
@endsection
