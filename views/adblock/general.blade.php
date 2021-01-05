<!-- blocklist settings tab -->
<div class="tab-pane active" id="adblocklistsettings">
  <div class="row">
    <div class="col-md-6">
      <h4 class="mt-3">{{ _("Blocklist settings") }}</h4>
        <div class="input-group">
          <input type="hidden" name="adblock-enable" value="0">
          <div class="custom-control custom-switch">
            <input class="custom-control-input" id="adblock-enable" type="checkbox" name="adblock-enable" value="1" {!! $enabled ? ' checked="checked"' : "" !!} aria-describedby="adblock-description">
          <label class="custom-control-label" for="adblock-enable">{{ _("Enable blocklists")  }}</label>
        </div>
        <p id="adblock-description">
          <small>{{ _("Enable this option if you want RaspAP to <b>block DNS requests for ads, tracking and other virtual garbage</b>. Blocklists are gathered from multiple, actively maintained sources and automatically updated, cleaned, optimized and moderated on a daily basis.")  }}</small>
          <div>
            <small class="text-muted">{{ _("This option adds <code>conf-file</code> and <code>addn-hosts</code> to the dnsmasq configuration.")  }}</small>
          </div>
        </p>
        </div>
        <div class="row">
          <div class="col-md-12">
            <p id="blocklist-updated">
              <div><small>{{ _("Hostnames blocklist last updated")  }}: <span class="font-weight-bold" id="notracking-hostnames">
                {{ blocklistUpdated('hostnames.txt') }}</span></small></div>
              <div><small>{{ _("Domains blocklist last updated")  }}: <span class="font-weight-bold" id="notracking-domains">
                {{ blocklistUpdated('domains.txt') }}</span></small></div>
            </p>
            <div class="input-group col-md-12 mb-4">
              <select class="custom-select custom-select-sm" id="cbxblocklist" onchange="clearBlocklistStatus()">
                <option value="">{{ _("Choose a blocklist provider")  }}</option>
                <option disabled="disabled"></option>
                @include('components.select_options', ['options'=>blocklistProviders()])
              </select>
              <div class="input-group-append">
                <button class="btn btn-sm btn-outline-secondary rounded-right" type="button" onclick="updateBlocklist()">{{ _("Update now") }}</button>
                <span id="cbxblocklist-status" class="input-group-addon check-hidden ml-2 mt-1"><i class="fas fa-check"></i></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /.row -->
  </div><!-- /.tab-pane | advanded tab -->

