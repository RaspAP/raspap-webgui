<!-- logfile output tab -->
<div class="tab-pane fade" id="logging">

  <h4>{{ _("Logging")  }}</h4>
  <p>{{ _("Enable these options to log DHCP server activity.")  }}</p>

  <div class="custom-control custom-switch">
    <input class="custom-control-input" id="log-dhcp" type="checkbox" name="log-dhcp" value="1" {!! $conf['log-dhcp'] ? ' checked="checked"' : "" !!} aria-describedby="log-dhcp-requests">
    <label class="custom-control-label" for="log-dhcp">{{ _("Log DHCP requests")  }}</label>
  </div>
  <div class="custom-control custom-switch">
    <input class="custom-control-input" id="log-queries" type="checkbox" name="log-queries" value="1" {!! $conf['log-queries'] ? ' checked="checked"' : "" !!} aria-describedby="log-dhcp-queries">
    <label class="custom-control-label" for="log-queries">{{ _("Log DNS queries")  }}</label>
  </div>

  <textarea class="logoutput {!! $dnsmasq_log ? "" : "my-3"!!}">{{ $dnsmasq_log }}</textarea>

</div><!-- /.tab-pane -->
