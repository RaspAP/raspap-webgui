<?php
/**
 * Tests for the allowed networks of the web interface (System > Advanced)
 *
 * Usage: php tests/webaccess/run_tests.php
 */

require_once __DIR__ . '/../../src/RaspAP/System/WebAccess.php';

use RaspAP\System\WebAccess;

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

echo "parseNetworks()\n";

check('empty input allows all networks', WebAccess::parseNetworks('') === []);
check('whitespace only allows all networks', WebAccess::parseNetworks("  \n ") === []);

$networks = WebAccess::parseNetworks('10.3.141.0/24, 192.168.1.10  fd00::/8');
check('networks are separated by commas or whitespace',
    $networks === ['10.3.141.0/24', '192.168.1.10', 'fd00::/8'], json_encode($networks));

$networks = WebAccess::parseNetworks('10.3.141.1/24,192.168.1.77/16');
check('host bits are cleared', $networks === ['10.3.141.0/24', '192.168.0.0/16'], json_encode($networks));

$networks = WebAccess::parseNetworks('FD00:0:0:0:0:0:0:1, 10.0.0.1/32, fd00::1/128');
check('addresses are canonical and a full prefix is a single address',
    $networks === ['fd00::1', '10.0.0.1'], json_encode($networks));

$networks = WebAccess::parseNetworks('10.3.141.0/24, 10.3.141.5/24');
check('duplicates are removed', $networks === ['10.3.141.0/24'], json_encode($networks));

check('0.0.0.0/0 is a valid network', WebAccess::parseNetworks('0.0.0.0/0') === ['0.0.0.0/0']);

foreach (['10.3.141.256', '10.3.141.0/33', '10.3.141.0/', '10.3.141.0/24/1', 'fd00::/129',
    '10.3.141.0/-1', '10.3.141.0/2a', 'raspap.local', '10.3.141.0/24;', '"10.3.141.0/24"',
    '10.3.141.0/24 {'] as $input) {
    check("'$input' is invalid", WebAccess::parseNetworks($input) === null);
}

$many = implode(',', array_map(fn($i) => "10.0.$i.0/24", range(1, WebAccess::MAX_NETWORKS)));
check('MAX_NETWORKS networks are accepted', count(WebAccess::parseNetworks($many) ?? []) === WebAccess::MAX_NETWORKS);
check('more than MAX_NETWORKS networks are invalid', WebAccess::parseNetworks($many . ',10.1.0.0/24') === null);

echo "allows()\n";

$networks = ['10.3.141.0/24', '192.168.1.10', 'fd00::/8'];
check('client in an allowed network', WebAccess::allows($networks, '10.3.141.23'));
check('allowed single address', WebAccess::allows($networks, '192.168.1.10'));
check('IPv6 client in an allowed network', WebAccess::allows($networks, 'fd12:3456::1'));
check('IPv4-mapped client in an allowed network', WebAccess::allows($networks, '::ffff:10.3.141.23'));
check('client outside of the allowed networks', !WebAccess::allows($networks, '192.168.1.11'));
check('IPv6 client outside of the allowed networks', !WebAccess::allows($networks, '2001:db8::1'));
check('IPv4 loopback always has access', WebAccess::allows($networks, '127.0.0.1'));
check('IPv6 loopback always has access', WebAccess::allows($networks, '::1'));
check('invalid client address has no access', !WebAccess::allows($networks, ''));
check('prefix boundary is respected', WebAccess::allows(['10.3.141.128/25'], '10.3.141.200')
    && !WebAccess::allows(['10.3.141.128/25'], '10.3.141.127'));
check('0.0.0.0/0 allows all IPv4 clients', WebAccess::allows(['0.0.0.0/0'], '203.0.113.9'));

echo "readNetworks()\n";

$file = tempnam(sys_get_temp_dir(), 'raspap-access');
check('missing file allows all networks', WebAccess::readNetworks($file . '.missing') === []);

file_put_contents($file, <<<'CONF'
# Managed by RaspAP. Changes on System > Advanced replace this file.
# Denies access to the web interface from outside of the allowed networks.
$HTTP["remoteip"] != "127.0.0.0/8" {
    $HTTP["remoteip"] != "::1" {
        $HTTP["remoteip"] != "10.3.141.0/24" {
            $HTTP["remoteip"] != "fd00::/8" {
                url.access-deny = ( "" )
            }
        }
    }
}

CONF);
$networks = WebAccess::readNetworks($file);
check('networks of raspap-webaccess.sh are read without loopback',
    $networks === ['10.3.141.0/24', 'fd00::/8'], json_encode($networks));
check('read networks parse to the same list',
    WebAccess::parseNetworks(implode(', ', $networks)) === $networks);
unlink($file);

echo $failures === 0 ? "\nAll tests passed\n" : "\n$failures test(s) failed\n";
exit($failures === 0 ? 0 : 1);
