<div class="tab-pane active" id="server-settings">
  <h4 class="mt-3">DHCP server settings</h4>
  <div class="row">
    <div class="form-group col-md-6">
      <label for="code">Interface</label>
      <select class="form-control" name="interface">
        @foreach ($interfaces as $interface)
          <option value="{{ $interface }}" {!! $interface === $conf['interface'] ? ' selected="selected"' : '' !!} >{{ $interface }}</option>
        @endforeach
      </select>
    </div>
  </div>
  <div class="row">
    <div class="form-group col-md-6">
      <label for="code">{{ _("Starting IP Address") }}</label>
      <input type="text" class="form-control"name="RangeStart" value="{{ $RangeStart }}" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code">{{ _("Ending IP Address") }}</label>
      <input type="text" class="form-control" name="RangeEnd" value="{{ $RangeEnd }}" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-xs-3 col-sm-3">
      <label for="code">{{ _("Lease Time") }}</label>
      <input type="text" class="form-control" name="RangeLeaseTime" value="{{ $arrRangeLeaseTime[1] }}" />
    </div>
    <div class="col-xs-3 col-sm-3">
      <label for="code">{{ _("Interval") }}</label>
      <select name="RangeLeaseTimeUnits" class="form-control" >
        <option value="m" >{{ _("Minute(s)") }}</option>
        <option value="h" >{{ _("Hour(s)") }}</option>
        <option value="d" >{{ _("Day(s)") }}</option>
        <option value="infinite" >{{ _("Infinite") }}</option>
      </select>
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code">{{ _("DNS Server") }} 1</label>
      <input type="text" class="form-control"name="DNS1" value="{{ $DNS1 }}" />
    </div>
  </div>

  <div class="row">
    <div class="form-group col-md-6">
      <label for="code">{{ _("DNS Server") }} 2</label>
      <input type="text" class="form-control" name="DNS2" value="{{ $DNS2 }}" />
    </div>
  </div>

</div><!-- /.tab-pane -->
