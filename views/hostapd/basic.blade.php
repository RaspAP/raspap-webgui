<div class="tab-pane active" id="basic">
  <h4 class="mt-3">{{ _("Basic settings")  }}</h4>
  <div class="row">
    <div class="form-group col-md-6">
      <label for="cbxinterface">{{ _("Interface") }}</label>
        @include('components.select', [
          'name'=>'interface', 
          'options' => $interfaces, 
          'selected' => $arrConfig['interface'], 
          'id' => 'cbxinterface'
        ])
    </div>
  </div>
  <div class="row">
    <div class="form-group col-md-6">
      <label for="txtssid">{{ _("SSID") }}</label>
      <input type="text" id="txtssid" class="form-control" name="ssid" value="{{ $arrConfig['ssid'] }}" />
    </div>
  </div>
  <div class="row">
    <div class="form-group col-md-6">
      <label for="cbxhwmode">{{ _("Wireless Mode")  }}</label>
      @include('components.select',[
        'name'=>'hw_mode', 
        'options'=>$arr80211Standard, 
        'selected'=>$selectedHwMode, 
        'id'=>'cbxhwmode', 
        'event'=>'loadChannelSelect', 
        'disabled'=>$hwModeDisabled,
      ])
    </div>
  </div>
  <div class="row">
    <div class="form-group col-md-6">
      <label for="cbxchannel">{{ _("Channel") }}</label>
      {{-- this component is populated via ajax --}}
      @include('components.select', ['name'=>'channel', 'options'=>[], 'id'=>'cbxchannel'])
    </div>
  </div>
</div><!-- /.tab-pane | basic tab -->
