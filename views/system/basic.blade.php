<!-- basic tab -->
<div role="tabpanel" class="tab-pane active" id="basic">
  <div class="row">
    <div class="col-lg-6">
      <h4 class="mt-3">{{ _("System Information") }}</h4>
      <div class="info-item">{{ _("Hostname") }}</div><div>{{ $hostname }}</div>
      <div class="info-item">{{ _("Pi Revision") }}</div><div>{{ RPiVersion() }}</div>
      <div class="info-item">{{ _("Uptime") }}</div><div>{{ $uptime }}</div>
      <div class="mb-1">{{ _("Memory Used") }}</div>
      <div class="progress mb-2" style="height: 20px;">
        <div class="progress-bar bg-{{ $memused_status }}"
            role="progressbar" aria-valuenow="{{ $memused }}" aria-valuemin="0" aria-valuemax="100"
            style="width: {{ $memused }}%">{{ $memused }}%
        </div>
      </div>
      <div class="mb-1">{{ _("CPU Load") }}</div>
      <div class="progress mb-2" style="height: 20px;">
        <div class="progress-bar bg-{{ $cpuload_status }}"
            role="progressbar" aria-valuenow="{{ $cpuload }}" aria-valuemin="0" aria-valuemax="100"
            style="width: {{ $cpuload }}%">{{ $cpuload }}%
        </div>
      </div>
      <div class="mb-1">{{ _("CPU Temp") }}</div>
      <div class="progress mb-4" style="height: 20px;">
        <div class="progress-bar bg-{{ $cputemp_status }}"
            role="progressbar" aria-valuenow="{{ $cputemp }}" aria-valuemin="0" aria-valuemax="100"
            style="width: {{ ($cputemp*1.2) }}%">{{ $cputemp }}°C
        </div>
      </div>

      <form action="system_info" method="POST">
        {!! CSRFTokenFieldTag() !!}
            <a href="{!! $_GET['page'] !!}" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> {{ _("Refresh")  }}</a>

        @if (!RASPI_MONITOR_ENABLED)
            <input type="submit" class="btn btn-warning" name="system_reboot"   value="{{ _("Reboot") }}" />
            <input type="submit" class="btn btn-warning" name="system_shutdown" value="{{ _("Shutdown") }}" />
        @endif
     </form>
      </div>
    </div>
  </div>

