<!-- advanced tab -->
<div role="tabpanel" class="tab-pane" id="advanced">
  <h4 class="mt-3">{{ _("Advanced settings")  }}</h4>
    @if (!RASPI_MONITOR_ENABLED)
    <form action="system_info" method="POST">
    {!! CSRFTokenFieldTag() !!}
      <div class="row">
        <div class="form-group col-md-6">
          <label for="code">{{ _("Web server port")  }}</label>
          <input type="text" class="form-control" name="serverPort" value="{{ $serverPort }}" />
        </div>
      </div>
      <div class="row">
        <div class="form-group col-md-6">
          <label for="code">{{ _("Web server bind address")  }}</label>
          <input type="text" class="form-control" name="serverBind" value="{{ $serverBind }}" />
        </div>
      </div>
      <input type="submit" class="btn btn-outline btn-primary" name="SaveServerSettings" value="{{ _("Save settings") }}" />
      <input type="submit" class="btn btn-warning" name="RestartLighttpd" value="{{ _("Restart lighttpd") }}" />
    </form>
    @endif
</div>


