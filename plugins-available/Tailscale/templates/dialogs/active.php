    <h5><?php echo _("Exit node activated"); ?></h5>
    <div class="my-3">
      <?php echo sprintf(
        _("The device <code>%s</code> is connected with the address <code>%s</code> and offers an <strong>exit node</strong>."),
        trim(htmlspecialchars($this->hostname)),
        trim(htmlspecialchars($address))
      ); ?>
    </div>

    <div>
      <?php
        $link = '<a class="text-gray-500" href="https://tailscale.com/kb/1103/exit-nodes#use-the-exit-node" target="_blank" rel="noopener noreferrer">Tailscale  documentation<i class="fas fa-external-link-alt fa-sm mx-1"></i></a>';
        echo sprintf(_('See the %s on how to use this exit node with your devices.'), $link); ?>
    </div>

