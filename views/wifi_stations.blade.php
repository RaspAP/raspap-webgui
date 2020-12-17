@if (empty($networks))
  <p class="lead text-center">{{ _('No Wifi stations found')  }}</p>
  <p class="text-center">{{ _('Click "Rescan" to search for nearby Wifi stations.')  }}</p>
@endif

<?php $index = 0; ?>
<div class="row ml-1 mr-1 w-100">
  @foreach ($networks as $ssid => $network)
 
    <div class="{{ count($networks) == 1 ? 'col-sm-12' : 'col-sm-6'}} align-items-stretch mb-3">
    <div class="card h-100 {{ count($networks) == 1 ? 'w-50' : 'w-100'}}">
      <div class="card-body">
        <input type="hidden" name="ssid{{ $index  }}" value="{{$ssid}}" />
        <?php if (strlen($ssid) == 0) {
            $ssid = "(unknown)";
        } ?>
        <h5 class="card-title">{{ $ssid }}</h5>

        <div class="info-item-wifi">{{ _("Status") }}</div>
        <div>
          @if ($network['configured'])
            <i class="fas fa-check-circle"></i>
          @endif
          @if ($network['connected'])
            <i class="fas fa-exchange-alt"></i>
          @endif
          @if (!$network['configured'] && !$network['connected'])
            {{_("Not configured")}}
          @endif
        </div>

        <div class="info-item-wifi">{{ _("Channel") }}</div>
        <div>
          @if ($network['visible'])
              {{ $network['channel'] }}
          @else
            <span class="label label-warning"> X </span>
          @endif
        </div>

        <div class="info-item-wifi">{{ _("RSSI") }}</div>
        <div>
        @if (isset($network['RSSI']) && $network['RSSI'] >= -200) 
            {{$network['RSSI']}}dB (         
            @if($network['RSSI'] >= -50)
                {{ 100 }}
            @elseif ($network['RSSI'] <= -100) 
                {{ 0 }}
            @else
                {{ 2*($network['RSSI'] + 100) }}
            @endif
            %)
        @else
             not found
        @endif
        </div>

        @if (array_key_exists('priority', $network))
          <input type="hidden" name="priority{{ $index  }}" value="{{ $network['priority'] }}" />
        @endif
        <input type="hidden" name="protocol{{ $index  }}" value="{{ $network['protocol'] }}" />

        <div class="info-item-wifi">{{ _("Security") }}</div>
        <div>{{ $network['protocol']  }}</div>

        <div class="form-group">
          <div class="info-item-wifi">{{ _("Passphrase") }}</div>
              <div class="input-group">
            @if ($network['protocol'] === 'Open')
              <input type="password" disabled class="form-control" aria-describedby="passphrase" name="passphrase{{ $index  }}" value="" />
            @else
              <input type="password" class="form-control js-validate-psk" aria-describedby="passphrase" name="passphrase{{ $index }}" value="{{ $network['passphrase'] }}" data-target="#update{{ $index  }}" data-colors="#ffd0d0,#d0ffd0">
              <div class="input-group-append">
            <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="[name=passphrase{{ $index }}]" data-toggle-with="{{ _("Hide")  }}">Show</button>
              </div>
            @endif
          </div>
        </div>

        <div class="btn-group btn-block ">
            @if ($network['configured'])
              <input type="submit" class="col-xs-4 col-md-4 btn btn-warning" value="{{ _("Update") }}" id="update{{ $index }}" name="update{{ $index }}"{{ $network['protocol'] === 'Open' ? ' disabled' : '' }} />
              <button type="submit" class="col-xs-4 col-md-4 btn btn-info" value="{{ $index }}" name="connect">{{ _("Connect") }}</button>
            @else
              <input type="submit" class="col-xs-4 col-md-4 btn btn-info" value="{{ _("Add") }}" id="update{{ $index }}" name="update{{ $index }}" {{ $network['protocol'] === 'Open' ? '' : ' disabled' }} />
            @endif
              <input type="submit" class="col-xs-4 col-md-4 btn btn-danger" value="{{ _("Delete") }}" name="delete{{ $index }}" {{ $network['configured'] ? '' : ' disabled' }} />
        </div><!-- /.btn-group -->
      </div><!-- /.card-body -->
    </div><!-- /.card -->
  </div><!-- /.col-sm -->
  <?php $index += 1; ?>
  @endforeach
</div><!-- /.row -->

