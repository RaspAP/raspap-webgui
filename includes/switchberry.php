<?php

use RaspAP\Messages\StatusMessage;
use RaspAP\Switchberry\SwitchberryService;

/**
 * Render and handle the Switchberry hardware management page.
 */
function DisplaySwitchberry(): void
{
    $messages = new StatusMessage();
    $service = new SwitchberryService();
    $switchberry = $service->status();
    $config = switchberryConfigDefaults($switchberry['config'] ?? []);

    if (!RASPI_MONITOR_ENABLED && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $result = null;

        if (isset($_POST['SaveSwitchberryPtp'])) {
            $updated = $config;
            $updated['ptp_role'] = switchberryPostEnum('ptp_role', ['GM', 'CLIENT', 'TC', 'BC', 'NONE'], 'NONE');
            $updated['ptp'] = [
                'transport' => switchberryPostEnum('ptp_transport', ['UNICAST', 'MULTICAST'], 'UNICAST'),
                'master_ip' => switchberryPostString('ptp_master_ip'),
                'time_traceable' => isset($_POST['ptp_time_traceable'])
            ];
            $tcPorts = [];
            $bcPorts = [];
            for ($port = 1; $port <= 5; $port++) {
                $tcPrefix = 'tc_port_' . $port . '_';
                $bcPrefix = 'bc_port_' . $port . '_';
                $tcPorts[] = [
                    'port' => $port,
                    'rx_latency_ns' => switchberryPostNullableInt($tcPrefix . 'rx_latency_ns'),
                    'tx_latency_ns' => switchberryPostNullableInt($tcPrefix . 'tx_latency_ns'),
                    'asymmetry_ns' => switchberryPostNullableInt($tcPrefix . 'asymmetry_ns')
                ];
                $bcPorts[] = [
                    'port' => $port,
                    'mode' => switchberryPostEnum($bcPrefix . 'mode', ['AUTO', 'UPSTREAM', 'DOWNSTREAM', 'DISABLED'], 'AUTO'),
                    'local_priority' => switchberryPostNullableInt($bcPrefix . 'local_priority'),
                    'log_announce_interval' => switchberryPostNullableInt($bcPrefix . 'log_announce_interval'),
                    'log_sync_interval' => switchberryPostNullableInt($bcPrefix . 'log_sync_interval'),
                    'log_min_delay_req_interval' => switchberryPostNullableInt($bcPrefix . 'log_min_delay_req_interval'),
                    'delay_asymmetry_ns' => switchberryPostNullableInt($bcPrefix . 'delay_asymmetry_ns'),
                    'ingress_latency_ns' => switchberryPostNullableInt($bcPrefix . 'ingress_latency_ns'),
                    'egress_latency_ns' => switchberryPostNullableInt($bcPrefix . 'egress_latency_ns'),
                    'ip_address' => switchberryPostString($bcPrefix . 'ip_address'),
                    'cidr' => switchberryPostNullableInt($bcPrefix . 'cidr'),
                    'gateway' => switchberryPostString($bcPrefix . 'gateway'),
                    'unicast_master' => switchberryPostString($bcPrefix . 'unicast_master')
                ];
            }
            $updated['tc'] = [
                'delay_mechanism' => switchberryPostEnum('tc_delay_mechanism', ['E2E', 'P2P'], 'E2E'),
                'one_step' => isset($_POST['tc_one_step']),
                'detect_l2' => isset($_POST['tc_detect_l2']),
                'detect_ipv4' => isset($_POST['tc_detect_ipv4']),
                'detect_ipv6' => isset($_POST['tc_detect_ipv6']),
                'ieee_802_1as' => isset($_POST['tc_ieee_802_1as']),
                'unicast' => isset($_POST['tc_unicast']),
                'alternate_master' => isset($_POST['tc_alternate_master']),
                'priority_queue' => isset($_POST['tc_priority_queue']),
                'domain_check' => isset($_POST['tc_domain_check']),
                'domain' => switchberryPostNullableInt('tc_domain'),
                'ports' => $tcPorts
            ];
            $updated['bc'] = [
                'profile' => switchberryPostEnum('bc_profile', ['IEEE1588', 'G8275_1', 'G8275_2'], 'IEEE1588'),
                'network_transport' => switchberryPostEnum('bc_network_transport', ['L2', 'UDPV4', 'UDPV6'], 'L2'),
                'delay_mechanism' => switchberryPostEnum('bc_delay_mechanism', ['E2E', 'P2P', 'AUTO'], 'P2P'),
                'domain' => switchberryPostNullableInt('bc_domain'),
                'two_step' => isset($_POST['bc_two_step']),
                'priority1' => switchberryPostNullableInt('bc_priority1'),
                'priority2' => switchberryPostNullableInt('bc_priority2'),
                'clock_class' => switchberryPostNullableInt('bc_clock_class'),
                'local_priority' => switchberryPostNullableInt('bc_local_priority'),
                'announce_receipt_timeout' => switchberryPostNullableInt('bc_announce_receipt_timeout'),
                'tx_timestamp_timeout' => switchberryPostNullableInt('bc_tx_timestamp_timeout'),
                'summary_interval' => switchberryPostNullableInt('bc_summary_interval'),
                'clock_servo' => switchberryPostEnum('bc_clock_servo', ['PI', 'LINREG'], 'PI'),
                'sync_system_clock' => isset($_POST['bc_sync_system_clock']),
                'ports' => $bcPorts
            ];
            $result = $service->configure($updated);
        } elseif (isset($_POST['SaveSwitchberryClockmatrix'])) {
            $updated = $config;
            $channels = [];
            foreach ([5, 6] as $channel) {
                $prefix = 'clockmatrix_' . $channel . '_';
                $channels[] = [
                    'channel' => $channel,
                    'override_tuning' => isset($_POST[$prefix . 'override_tuning']),
                    'loop_bandwidth_value' => switchberryPostNullableInt($prefix . 'loop_bandwidth_value'),
                    'loop_bandwidth_unit' => switchberryPostEnum($prefix . 'loop_bandwidth_unit', ['UHZ', 'MHZ', 'HZ', 'KHZ'], 'MHZ'),
                    'phase_slope_limit_ns_per_s' => switchberryPostNullableInt($prefix . 'phase_slope_limit_ns_per_s'),
                    'damping_factor' => switchberryPostNullableInt($prefix . 'damping_factor'),
                    'combo_mode' => switchberryPostEnum($prefix . 'combo_mode', ['AUTOMATIC', 'INDEPENDENT', 'FOLLOW_OTHER'], 'AUTOMATIC')
                ];
            }
            $updated['clockmatrix'] = ['channels' => $channels];
            $result = $service->configure($updated);
        } elseif (isset($_POST['SaveSwitchberryGnss'])) {
            $updated = $config;
            $updated['gps'] = [
                'present' => isset($_POST['gnss_use_as_source']),
                'role' => switchberryPostNullableEnum('gnss_source_role', ['TIME_ONLY', 'FREQ_ONLY', 'TIME_AND_FREQ']),
                'priority' => switchberryPostNullableInt('gnss_source_priority')
            ];
            $updated['gnss_receiver'] = [
                'manage_receiver' => isset($_POST['gnss_manage_receiver']),
                'dynamic_model' => switchberryPostEnum(
                    'gnss_dynamic_model',
                    ['PORTABLE', 'STATIONARY', 'PEDESTRIAN', 'AUTOMOTIVE', 'SEA', 'AIRBORNE_1G', 'AIRBORNE_2G', 'AIRBORNE_4G', 'WRIST', 'BIKE'],
                    'STATIONARY'
                ),
                'measurement_rate_ms' => switchberryPostNullableInt('gnss_measurement_rate_ms'),
                'minimum_elevation_deg' => switchberryPostNullableInt('gnss_minimum_elevation_deg'),
                'constellations' => [
                    'gps' => isset($_POST['gnss_constellation_gps']),
                    'galileo' => isset($_POST['gnss_constellation_galileo']),
                    'glonass' => isset($_POST['gnss_constellation_glonass']),
                    'beidou' => isset($_POST['gnss_constellation_beidou']),
                    'sbas' => isset($_POST['gnss_constellation_sbas']),
                    'qzss' => isset($_POST['gnss_constellation_qzss'])
                ]
            ];
            $result = $service->configure($updated);
        } elseif (isset($_POST['SaveSwitchberryTiming'])) {
            $updated = $config;
            $updated['gps'] = [
                'present' => isset($_POST['gps_present']),
                'role' => switchberryPostNullableEnum('gps_role', ['TIME_ONLY', 'FREQ_ONLY', 'TIME_AND_FREQ']),
                'priority' => switchberryPostNullableInt('gps_priority')
            ];
            $updated['cm4'] = [
                'used_as_source' => isset($_POST['cm4_used_as_source']),
                'role' => switchberryPostNullableEnum('cm4_role', ['TIME_ONLY', 'FREQ_ONLY', 'TIME_AND_FREQ']),
                'priority' => switchberryPostNullableInt('cm4_priority')
            ];
            $updated['synce'] = [
                'used_as_source' => isset($_POST['synce_used_as_source']),
                'priority' => switchberryPostNullableInt('synce_priority'),
                'recover_port' => switchberryPostNullableInt('synce_recover_port')
            ];
            $updated['network'] = [
                'mode' => switchberryPostEnum('network_mode', ['DHCP', 'STATIC'], 'DHCP'),
                'ip_address' => switchberryPostString('network_ip_address'),
                'cidr' => switchberryPostNullableInt('network_cidr'),
                'gateway' => switchberryPostString('network_gateway'),
                'dns' => switchberryPostString('network_dns')
            ];
            $result = $service->configure($updated);
        } elseif (isset($_POST['SaveSwitchberrySma'])) {
            $updated = $config;
            $smas = [];
            for ($number = 1; $number <= 4; $number++) {
                $prefix = 'sma_' . $number . '_';
                $smas[] = [
                    'name' => 'SMA' . $number,
                    'direction' => switchberryPostEnum(
                        $prefix . 'direction',
                        ['INPUT', 'OUTPUT', 'UNUSED'],
                        'UNUSED'
                    ),
                    'role' => switchberryPostNullableEnum(
                        $prefix . 'role',
                        ['TIME_ONLY', 'FREQ_ONLY', 'TIME_AND_FREQ']
                    ),
                    'priority' => switchberryPostNullableInt($prefix . 'priority'),
                    'frequency_hz' => switchberryPostNullableInt($prefix . 'frequency_hz')
                ];
            }
            $updated['smas'] = $smas;
            $result = $service->configure($updated);
        } elseif (isset($_POST['ClockmatrixAction'])) {
            $rawAction = is_string($_POST['ClockmatrixAction']) ? $_POST['ClockmatrixAction'] : '';
            $parts = explode(':', $rawAction, 2);
            if (count($parts) === 2 && in_array($parts[0], ['normal', 'reacquire', 'holdover', 'freerun'], true)) {
                $result = $service->clockmatrixAction((int) $parts[1], $parts[0]);
            } else {
                $result = ['ok' => false, 'error' => 'Invalid ClockMatrix action'];
            }
        } elseif (isset($_POST['GnssAction'])) {
            $action = is_string($_POST['GnssAction']) ? $_POST['GnssAction'] : '';
            if (in_array($action, ['restart', 'hotstart', 'warmstart', 'coldstart'], true)) {
                $result = $service->gnssAction($action);
            } else {
                $result = ['ok' => false, 'error' => 'Invalid GNSS receiver action'];
            }
        } elseif (isset($_POST['SwitchberryPortAction'])) {
            $rawAction = is_string($_POST['SwitchberryPortAction']) ? $_POST['SwitchberryPortAction'] : '';
            $parts = explode(':', $rawAction, 2);
            if (count($parts) === 2 && in_array($parts[0], ['enable', 'disable'], true)) {
                $result = $service->setPort((int) $parts[1], $parts[0]);
            } else {
                $result = ['ok' => false, 'error' => 'Invalid port action'];
            }
        } elseif (isset($_POST['RestartSwitchberryStack'])) {
            $result = $service->restart();
        } elseif (isset($_POST['RebootSwitchberry'])) {
            $result = $service->reboot();
        }

        if (is_array($result)) {
            switchberryAddResultMessage($messages, $result);
            if (!empty($result['ok'])) {
                $switchberry = $service->status();
                $config = switchberryConfigDefaults($switchberry['config'] ?? ($result['config'] ?? []));
            }
        }
    }

    echo renderTemplate('switchberry', compact('messages', 'switchberry', 'config'));
}

/**
 * Merge an installed configuration with safe display defaults.
 */
function switchberryConfigDefaults(array $config): array
{
    $defaults = [
        'ptp_role' => 'NONE',
        'gps' => ['present' => false, 'role' => null, 'priority' => null],
        'cm4' => ['used_as_source' => false, 'role' => null, 'priority' => null],
        'synce' => ['used_as_source' => false, 'priority' => null, 'recover_port' => null],
        'smas' => [
            ['name' => 'SMA1', 'direction' => 'UNUSED', 'role' => null, 'priority' => null, 'frequency_hz' => null],
            ['name' => 'SMA2', 'direction' => 'UNUSED', 'role' => null, 'priority' => null, 'frequency_hz' => null],
            ['name' => 'SMA3', 'direction' => 'UNUSED', 'role' => null, 'priority' => null, 'frequency_hz' => null],
            ['name' => 'SMA4', 'direction' => 'UNUSED', 'role' => null, 'priority' => null, 'frequency_hz' => null]
        ],
        'clockmatrix' => [
            'channels' => [
                ['channel' => 5, 'override_tuning' => false, 'loop_bandwidth_value' => 1200, 'loop_bandwidth_unit' => 'mHz', 'phase_slope_limit_ns_per_s' => 7500, 'damping_factor' => 5, 'combo_mode' => 'AUTOMATIC'],
                ['channel' => 6, 'override_tuning' => false, 'loop_bandwidth_value' => 100, 'loop_bandwidth_unit' => 'mHz', 'phase_slope_limit_ns_per_s' => 100, 'damping_factor' => 0, 'combo_mode' => 'AUTOMATIC']
            ]
        ],
        'gnss_receiver' => [
            'manage_receiver' => false,
            'dynamic_model' => 'STATIONARY',
            'measurement_rate_ms' => 1000,
            'minimum_elevation_deg' => 5,
            'constellations' => [
                'gps' => true, 'galileo' => true, 'glonass' => true,
                'beidou' => true, 'sbas' => true, 'qzss' => true
            ]
        ],
        'network' => ['mode' => 'DHCP', 'ip_address' => null, 'cidr' => null, 'gateway' => null, 'dns' => null],
        'ptp' => ['transport' => 'UNICAST', 'master_ip' => null, 'time_traceable' => true],
        'tc' => [
            'delay_mechanism' => 'E2E', 'one_step' => true,
            'detect_l2' => true, 'detect_ipv4' => true, 'detect_ipv6' => true,
            'ieee_802_1as' => false, 'unicast' => true, 'alternate_master' => true,
            'priority_queue' => true, 'domain_check' => false, 'domain' => 0,
            'ports' => array_map(static fn(int $port): array => [
                'port' => $port, 'rx_latency_ns' => 425, 'tx_latency_ns' => 243, 'asymmetry_ns' => 0
            ], range(1, 5))
        ],
        'bc' => [
            'profile' => 'IEEE1588', 'network_transport' => 'L2', 'delay_mechanism' => 'E2E',
            'domain' => 0, 'two_step' => false, 'priority1' => 128, 'priority2' => 128,
            'clock_class' => 248, 'local_priority' => 128, 'announce_receipt_timeout' => 3,
            'tx_timestamp_timeout' => 1000, 'summary_interval' => 0, 'clock_servo' => 'PI',
            'sync_system_clock' => false,
            'ports' => array_map(static fn(int $port): array => [
                'port' => $port, 'interface' => 'lan' . $port, 'mode' => 'AUTO',
                'local_priority' => 128, 'log_announce_interval' => 1,
                'log_sync_interval' => 0, 'log_min_delay_req_interval' => 0,
                'delay_asymmetry_ns' => 0, 'ingress_latency_ns' => 0,
                'egress_latency_ns' => 0, 'unicast_master' => null,
                'ip_address' => null, 'cidr' => null, 'gateway' => null
            ], range(1, 5))
        ]
    ];
    return array_replace_recursive($defaults, $config);
}

function switchberryPostString(string $name): ?string
{
    $raw = $_POST[$name] ?? '';
    $value = is_string($raw) ? trim($raw) : '';
    return $value === '' ? null : $value;
}

function switchberryPostNullableInt(string $name): ?int
{
    $raw = $_POST[$name] ?? '';
    $value = is_string($raw) ? trim($raw) : '';
    if ($value === '') {
        return null;
    }
    return preg_match('/^-?\d+$/', $value) ? (int) $value : -1;
}

function switchberryPostEnum(string $name, array $allowed, string $default): string
{
    $raw = $_POST[$name] ?? $default;
    $value = is_string($raw) ? strtoupper(trim($raw)) : $default;
    return in_array($value, $allowed, true) ? $value : $default;
}

function switchberryPostNullableEnum(string $name, array $allowed): ?string
{
    $raw = $_POST[$name] ?? '';
    $value = is_string($raw) ? strtoupper(trim($raw)) : '';
    return in_array($value, $allowed, true) ? $value : null;
}

function switchberryAddResultMessage(StatusMessage $messages, array $result): void
{
    if (!empty($result['ok'])) {
        $messages->addMessage((string) ($result['message'] ?? 'Switchberry action completed'), 'success');
        foreach (($result['warnings'] ?? []) as $warning) {
            $messages->addMessage((string) $warning, 'warning');
        }
        return;
    }
    $messages->addMessage((string) ($result['error'] ?? 'Switchberry action failed'), 'danger');
}
