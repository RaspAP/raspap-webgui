<!-- advanced tab -->
<div class="tab-pane" id="advanced">

  <div class="row">
    <div class="col-md-6">
      <h5>{{ _("Upstream DNS servers")  }}</h5>

      <div class="input-group">
        <input type="hidden" name="no-resolv" value="0">
        <div class="custom-control custom-switch">
          <input class="custom-control-input" id="no-resolv" type="checkbox" name="no-resolv" value="1" {!! $conf['no-resolv'] ? ' checked="checked"' : "" !!} aria-describedby="no-resolv-description">
          <label class="custom-control-label" for="no-resolv">{{ _("Only ever query DNS servers configured below")  }}</label>
        </div>
        <p id="no-resolv-description">
          <small>{{ _("Enable this option if you want RaspAP to <b>send DNS queries to the servers configured below exclusively</b>. By default RaspAP also uses its upstream DHCP server's name servers.")  }}</small>
          <br><small class="text-muted">{!! _("This option adds <code>no-resolv</code> to the dnsmasq configuration.")  !!}</small>
        </p>
      </div>

      <div class="js-dhcp-upstream-servers">
        @foreach ($upstreamServers as $server)
          <div class="form-group input-group input-group-sm js-dhcp-upstream-server">
            <input type="text" class="form-control" name="server[]" value="{{ $server  }}">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary js-remove-dhcp-upstream-server" type="button"><i class="fas fa-minus"></i></button>
            </div>
          </div>
        @endforeach
      </div>

      <div class="form-group">
        <label for="add-dhcp-upstream-server-field">{{ _("Add upstream DNS server")  }}</label>
        <div class="input-group">
          <input type="text" class="form-control" id="add-dhcp-upstream-server-field" aria-describedby="new-dhcp-upstream-server" placeholder="<?php printf(_("e.g. %s"), "208.67.222.222") ?>">
          <div class="input-group-append">
            <button type="button" class="btn btn-outline-secondary js-add-dhcp-upstream-server"><i class="fas fa-plus"></i></button>
          </div>
        </div>
        <p id="new-dhcp-upstream-server" class="form-text text-muted">
          <small>
            {{ _("Format: ")  }}
            <code class="text-muted">{{ "[/[<domain>]/[domain/]][<ipaddr>[#<port>][@<source-ip>|<interface>[#<port>]]" }}</code>
          </small>
        </p>
        <select class="custom-select custom-select-sm js-field-preset" data-field-preset-target="#add-dhcp-upstream-server-field">
          <option value="">{{ _("Choose a hosted server")  }}</option>
          <option disabled="disabled"></option>
          @include('components.select_options', ['options'=>dnsServers()])
        </select>
      </div>
    </div>

    <template id="dhcp-upstream-server">
      <div class="form-group input-group input-group-sm js-dhcp-upstream-server">
        <input type="text" class="form-control" name="server[]" value="{{ server }}">
        <div class="input-group-append">
          <button class="btn btn-outline-secondary js-remove-dhcp-upstream-server" type="button"><i class="fas fa-minus"></i></button>
        </div>
      </div>
    </template>
  </div><!-- /.row -->

</div><!-- /.tab-pane | advanded tab -->
