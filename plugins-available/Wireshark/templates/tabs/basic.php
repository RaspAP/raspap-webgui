<div class="tab-pane fade show active" id="settings">
  <h4 class="mt-3"><?php echo _("Basic settings"); ?></h4>
  <div class="row">
    <div class="mb-3 col-12 mt-2">

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="cbxinterface"><?php echo _("Capture interface") ;?></label>
          <?php SelectorOptions('interface', $interfaces, $arrConfig['interface'], 'cbxinterface'); ?>
        </div>
      </div>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="txtoutputfile"><?php echo _("Output file"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" title="<?php echo _("Path where capture file will be saved (.pcap format)"); ?>"></i>
          <input type="text" id="txtoutputfile" class="form-control" name="output_file" 
                  value="<?php echo htmlspecialchars($arrConfig['output_file'] ?? '/tmp/capture.pcap', ENT_QUOTES); ?>" 
                  placeholder="/tmp/capture.pcap" disabled />
          <small class="form-text text-muted"><?php echo _("File will be saved with .pcap extension"); ?></small>
        </div>
      </div>

      <div class="row">
        <div class="mb-3 col-md-12">
          <label for="txtcapturefilter"><?php echo _("Capture filter (BPF syntax)"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Berkeley Packet Filter syntax. Leave empty to capture all traffic."); ?>"></i>
          <input type="text" id="txtcapturefilter" class="form-control" name="capture_filter" 
                  value="<?php echo htmlspecialchars($arrConfig['capture_filter'] ?? '', ENT_QUOTES); ?>" 
                  placeholder="port 80 or port 443" />
          <small class="form-text text-muted">
            <?php echo _("Examples: <code>port 80</code>, <code>host 192.168.1.1</code>, <code>tcp and not port 22</code>"); ?>
          </small>
        </div>
      </div>

      <h5 class="mt-3"><?php echo _("Capture limits"); ?></h5>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="txtpacketcount"><?php echo _("Packet count limit"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Stop capture after this many packets. Leave empty for unlimited."); ?>"></i>
          <input type="number" id="txtpacketcount" class="form-control" name="packet_count" min="1" 
                  value="<?php echo htmlspecialchars($arrConfig['packet_count'] ?? '', ENT_QUOTES); ?>" 
                  placeholder="e.g., 1000" />
        </div>

        <div class="mb-3 col-md-6">
          <label for="txtduration"><?php echo _("Duration limit (seconds)"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Stop capture after this many seconds. Leave empty for unlimited."); ?>"></i>
          <input type="number" id="txtduration" class="form-control" name="duration" min="1" 
                  value="<?php echo htmlspecialchars($arrConfig['duration'] ?? '', ENT_QUOTES); ?>" 
                  placeholder="e.g., 300" />
        </div>
      </div>

      <h5 class="mt-3"><?php echo _("Ring buffer settings"); ?></h5>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="txtringbuffersize"><?php echo _("File size (KB)"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Create new file when this size is reached. Leave empty to disable."); ?>"></i>
          <input type="number" id="txtringbuffersize" class="form-control" name="ring_buffer_size" min="100" 
                  value="<?php echo htmlspecialchars($arrConfig['ring_buffer_size'] ?? '', ENT_QUOTES); ?>" 
                  placeholder="e.g., 10000" />
          <small class="form-text text-muted"><?php echo _("10000 = 10 MB per file"); ?></small>
        </div>

        <div class="mb-3 col-md-6">
          <label for="txtringbufferfiles"><?php echo _("Number of files"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Maximum number of ring buffer files to keep. Oldest files are deleted."); ?>"></i>
          <input type="number" id="txtringbufferfiles" class="form-control" name="ring_buffer_files" min="2" max="100"
                  value="<?php echo htmlspecialchars($arrConfig['ring_buffer_files'] ?? '', ENT_QUOTES); ?>" 
                  placeholder="e.g., 5" />
        </div>
      </div>

      <h5 class="mt-3"><?php echo _("Advanced options"); ?></h5>

      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="txtsnaplen"><?php echo _("Snapshot length (bytes)"); ?></label>
          <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
              title="<?php echo _("Limit the amount of data captured per packet. Leave empty for full packets."); ?>"></i>
          <input type="number" id="txtsnaplen" class="form-control" name="snaplen" min="64" max="65535"
                  value="<?php echo htmlspecialchars($arrConfig['snaplen'] ?? '', ENT_QUOTES); ?>" 
                  placeholder="e.g., 96 for headers only" />
          <small class="form-text text-muted"><?php echo _("96 bytes captures headers only, reduces file size"); ?></small>
        </div>

        <div class="mb-3 col-md-6">
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" id="chkpromiscuous" name="promiscuous" value="1" 
                    <?php echo (isset($arrConfig['promiscuous']) && $arrConfig['promiscuous']) ? 'checked' : 'checked'; ?> />
            <label class="form-check-label" for="chkpromiscuous">
              <?php echo _("Promiscuous mode"); ?>
            </label>
            <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="auto" 
                title="<?php echo _("Capture all packets on the network segment, not just those destined for this interface"); ?>"></i>
          </div>
        </div>
      </div>

      <h5 class="mt-3"><?php echo _("Quick filter presets"); ?></h5>
      <div class="row">
        <div class="mb-3 col-md-12">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setFilter('')">
              <?php echo _("All Traffic"); ?>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setFilter('tcp port 80 or tcp port 443')">
              <?php echo _("HTTP/HTTPS"); ?>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setFilter('port 53')">
              <?php echo _("DNS"); ?>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setFilter('icmp')">
              <?php echo _("ICMP (Ping)"); ?>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setFilter('tcp port 22')">
              <?php echo _("SSH"); ?>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setFilter('not port 22')">
              <?php echo _("Exclude SSH"); ?>
            </button>
          </div>
        </div>
      </div>

      <div class="row mt-3">
      </div>
    </div>
  </div>
</div><!-- /.tab-pane | basic tab -->

<script>
// quick filter preset function
function setFilter(filter) {
  document.getElementById('txtcapturefilter').value = filter;
}
</script>

