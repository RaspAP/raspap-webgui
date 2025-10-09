 <div role="tabpanel" class="tab-pane" id="diagnostic">

  <h4 class="mt-3"><?php echo _("Speedtest") ?></h4>
  <div class="row">
    <div class="col-md-8">

    <div id="loading" class="visible">
      <p id="message"><span class="loadCircle"></span><?php echo _("Selecting a server"); ?>...</p>
    </div>
    <div id="testWrapper" class="hidden">
      <button id="startStopBtn" type="button" class="btn btn-outline btn-primary" onclick="startStop()"></button>
      <a class="privacy" href="#" onclick="I('privacyPolicy').style.display=''"><?php echo _("Privacy"); ?></a>

      <div class="col-sm-4 centered">
          <div id="serverArea">
            <?php echo _("Server"); ?>: <select id="server" class="form-select" onchange="s.setSelectedServer(SPEEDTEST_SERVERS[this.value]);"></select>
          </div>
      </div>
      <div id="test">
        <div class="testGroup">
          <div class="testArea2">
            <div class="testName"><?php echo _("Ping"); ?></div>
            <div id="pingText" class="meterText" style="color:#AA6060"></div>
            <div class="unit"><?php echo _("ms"); ?></div>
          </div>
          <div class="testArea2">
            <div class="testName"><?php echo _("Jitter"); ?></div>
            <div id="jitText" class="meterText" style="color:#AA6060"></div>
            <div class="unit"><?php echo _("ms"); ?></div>
          </div>
        </div>
        <div class="testGroup">
          <div class="testArea">
            <div class="testName"><?php echo _("Download"); ?></div>
            <canvas id="dlMeter" class="meter"></canvas>
            <div id="dlText" class="meterText"></div>
            <div class="unit"><?php echo _("Mbps"); ?></div>
          </div>
          <div class="testArea">
            <div class="testName"><?php echo _("Upload"); ?></div>
            <canvas id="ulMeter" class="meter"></canvas>
            <div id="ulText" class="meterText"></div>
            <div class="unit"><?php echo _("Mbps"); ?></div>
          </div>
        </div>
        <div id="ipArea">
          <span id="ip"></span>
        </div>
      </div>
    </div><!-- /.testWrapper -->

    <div id="privacyPolicy" style="display:none">
      <h4>Speedtest Privacy Policy</h4>
      <p>RaspAP's <a href="https://speedtest.raspap.com/">Speedtest server</a> is configured with telemetry enabled.</p>
      <h5>Data we collect</h5>
      <p>
          At the end of the test, the following data is collected and stored:
          <ul>
              <li>Test ID</li>
              <li>Time of testing</li>
              <li>Test results (download and upload speed, ping and jitter)</li>
              <li>IP address</li>
              <li>ISP information</li>
              <li>Approximate location (inferred from IP address, not GPS)</li>
              <li>User agent and browser locale</li>
          </ul>
      </p>
      <h5>How we use the data</h5>
      <p>
          Data collected through this service is used to:
          <ul>
              <li>Allow sharing of test results (sharable image for forums, etc.)</li>
              <li>To improve the service offered to you (for instance, to detect problems on our side)</li>
          </ul>
          No personal information is disclosed to third parties.
      </p>
      <h5>Your consent</h5>
      <p>
          By starting the test, you consent to the terms of this privacy policy.
      </p>
      <h5>Data removal</h5>
      <p>
          If you wish to have your information deleted, simply provide us with your IP address. Without this information we won't be able to comply with your request. Contact <a href="mailto:&#115;&#117;&#112;&#112;&#111;&#114;&#116;&#64;&#114;&#97;&#115;&#112;&#97;&#112;&#46;&#99;&#111;&#109;">&#115;&#117;&#112;&#112;&#111;&#114;&#116;&#64;&#114;&#97;&#115;&#112;&#97;&#112;&#46;&#99;&#111;&#109;</a> for all deletion requests.
      </p>
      <br/>
      <div class="closePrivacyPolicy">
          <a class="privacy" href="#" onclick="I('privacyPolicy').style.display='none'">Close</a>
      </div>
      <br/>
    </div><!-- /.privacyPolicy -->

    </div><!-- /.col -->
  </div><!-- /.row -->
</div><!-- /.tabpanel -->

