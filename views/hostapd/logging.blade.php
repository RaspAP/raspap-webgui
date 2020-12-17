<!-- logfile output tab -->
<div class="tab-pane fade" id="logoutput">
  <h4 class="mt-3">{{ _("Logging") }}</h4>
  <div class="row">
    <div class="form-group col-md-8">
      @if ($hostapLog !== null)
          <br /><textarea class="logoutput">{{$hostapLog}}</textarea>
      @else
          <br />Logfile output not enabled
      @endif
     </div>
  </div>
</div><!-- /.tab-pane | logging tab -->
