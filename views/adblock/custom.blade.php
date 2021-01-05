<!-- logging tab -->
<div class="tab-pane fade" id="adblockcustom">
  <h4 class="mt-3">{{ _("Custom blocklist") }}</h4>
    <div class="row">
      <div class="col-md-6">
        <div class="input-group">
          <input type="hidden" name="adblock-custom-enable" value="0">
          <div class="custom-control custom-switch">
            <input class="custom-control-input" id="adblock-custom-enable" type="checkbox" name="adblock-custom-enable" value="1" {!! $custom_enabled ? ' checked="checked"' : "" !!} aria-describedby="adblock-description">
          <label class="custom-control-label" for="adblock-custom-enable">{{ _("Enable custom blocklist")  }}</label>
          </div>
        </div>
        <p id="adblock-description">
          <small>{{ _("Define custom hosts to be blocked by entering an IPv4 or IPv6 address followed by any whitespace (spaces or tabs) and the host name.")  }}</small>
          <small>{{ _("<b>IPv4 example:</b> 0.0.0.0 badhost.com")  }}</small>
          <div>
            <small class="text-muted">{{ _("This option adds an <code>addn-hosts</code> directive to the dnsmasq configuration.")  }}</small>
          </div>
        </p>
      </div>
    </div>
    <div class="row">
      <div class="form-group col-md-8">
      @if (strlen($adblock_custom) == 0) 
        {{ _("Custom blocklist not defined") }}
      @else
        <textarea class="logoutput" name="adblock-custom-hosts">{{ $adblock_custom_content }}</textarea>
      @endif
    </div>
  </div>
</div><!-- /.tab-pane -->
