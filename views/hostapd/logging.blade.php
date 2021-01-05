<!-- logfile output tab -->
<div class="tab-pane fade" id="logoutput">
  <h4 class="mt-3">{{ _("Logging") }}</h4>
  <p>{!! _("Enable this option to log <code>hostapd</code> activity.")  !!}</p>

  <div class="custom-control custom-switch">
    <input class="custom-control-input" id="chxlogenable" name="logEnable" type="checkbox" value="1" {!! $arrHostapdConf['LogEnable'] == 1 ? 'checked="checked"' : '' !!} />
    <label class="custom-control-label" for="chxlogenable">{{ _("Logfile output") }}</label>
  </div>
  
  <div class="row">
    <div class="form-group col-md-8 mt-2">
          <textarea class="logoutput  {!! $hostapLog ? "" : "my-3"!!}">{{$hostapLog}}</textarea>
     </div>
  </div>
</div><!-- /.tab-pane | logging tab -->
