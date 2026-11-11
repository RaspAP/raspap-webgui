<?php
/**
 * Tests for the dnsmasq logging options written to 090_raspap.conf
 *
 * Usage: php tests/dnsmasq/run_tests.php
 */

define('RASPI_DHCPCD_LOG', '/var/log/dnsmasq.log');

require_once __DIR__ . '/../../src/RaspAP/Networking/Hotspot/DnsmasqManager.php';

use RaspAP\Networking\Hotspot\DnsmasqManager;

$failures = 0;

function check($name, $condition, $detail = '')
{
    global $failures;
    if ($condition) {
        echo "  ok    $name\n";
    } else {
        echo "  FAIL  $name" . ($detail !== '' ? " ($detail)" : '') . "\n";
        $failures++;
    }
}

$dnsmasq = new DnsmasqManager();

echo "buildDefault()\n";

$config = $dnsmasq->buildDefault([]);
check('no logging options by default',
    !in_array('log-queries', $config) && !in_array('log-queries=extra', $config)
    && !in_array('log-dhcp', $config));

$config = $dnsmasq->buildDefault(['log-queries' => '1']);
check('log-queries writes plain log-queries',
    in_array('log-queries', $config) && !in_array('log-queries=extra', $config));

$config = $dnsmasq->buildDefault(['log-queries' => '1', 'log-queries-extra' => '1']);
check('log-queries-extra writes log-queries=extra',
    in_array('log-queries=extra', $config) && !in_array('log-queries', $config));

$config = $dnsmasq->buildDefault(['log-queries-extra' => '1']);
check('log-queries-extra is ignored when query logging is off',
    !in_array('log-queries', $config) && !in_array('log-queries=extra', $config));

$config = $dnsmasq->buildDefault(['log-dhcp' => '1', 'log-queries' => '1', 'log-queries-extra' => '1']);
check('log-dhcp is written alongside log-queries=extra',
    in_array('log-dhcp', $config) && in_array('log-queries=extra', $config));

echo $failures === 0 ? "\nAll tests passed\n" : "\n$failures test(s) failed\n";
exit($failures === 0 ? 0 : 1);
