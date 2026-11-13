<?php
/**
 * Tests for the dnsmasq options written to 090_raspap.conf
 *
 * Usage: php tests/dnsmasq/run_tests.php
 */

define('RASPI_DHCPCD_LOG', '/var/log/dnsmasq.log');

if (!function_exists('_')) {
    function _($text)
    {
        return $text;
    }
}

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

$config = $dnsmasq->buildDefault([]);
check('the DNS server is enabled by default', !in_array('port=0', $config));

$config = $dnsmasq->buildDefault(['disable-dns' => '1']);
check('disable-dns writes port=0', in_array('port=0', $config));

$config = $dnsmasq->buildDefault(['disable-dns' => '0']);
check('disable-dns=0 keeps the DNS server enabled', !in_array('port=0', $config));

$config = $dnsmasq->buildDefault(['disable-dns' => '1', 'log-dhcp' => '1']);
check('port=0 is written alongside log-dhcp',
    in_array('port=0', $config) && in_array('log-dhcp', $config));

echo "\nvalidate()\n";

$errors = $dnsmasq->validate(['DNS1' => '']);
check('no error without disable-dns', empty($errors), implode('; ', $errors));

$errors = $dnsmasq->validate(['disable-dns' => '1', 'DNS1' => '10.3.141.1']);
check('no error with disable-dns and a DNS server', empty($errors), implode('; ', $errors));

$errors = $dnsmasq->validate(['disable-dns' => '1', 'DNS1' => '']);
check('disable-dns requires a DNS server', count($errors) === 1);

$errors = $dnsmasq->validate(['disable-dns' => '1']);
check('disable-dns requires a DNS server when DNS1 is missing', count($errors) === 1);

echo $failures === 0 ? "\nAll tests passed\n" : "\n$failures test(s) failed\n";
exit($failures === 0 ? 0 : 1);
