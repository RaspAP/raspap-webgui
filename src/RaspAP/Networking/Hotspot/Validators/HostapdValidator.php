<?php

/**
 * Hostapd validator class for RaspAP
 *  
 * @description Validates hostapd configuration input
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking\Hotspot\Validators;

use RaspAP\Messages\StatusMessage;

class HostapdValidator
{
    // Valid channel widths for 802.11ax (HE)
    private const HE_VALID_CHWIDTHS = [0, 1, 2]; // 20/40, 80, 160 MHz

    // Valid channel widths for 802.11be (EHT)
    private const EHT_VALID_CHWIDTHS = [0, 1, 2, 3, 4]; // 20, 40, 80, 160, 320 MHz

    // 6 GHz channel range (US)
    private const CHANNEL_6GHZ_MIN = 1;
    private const CHANNEL_6GHZ_MAX = 233;

    /**
     * Validates full hostapd parameter set
     *
     * @param array $post            raw $_POST object
     * @param array $wpaArray        allowed WPA values
     * @param array $encTypes        allowed encryption types
     * @param array $modes           allowed hardware modes
     * @param array $interfaces      valid interface list
     * @param string $regDomain      regulatory domain
     * @param StatusMessage $status  Status message collector
     * @return array|false           validated configuration array or false on failure
     */
    public function validate(
        array $post,
        array $wpaArray,
        array $encTypes,
        array $modes,
        array $interfaces,
        string $regDomain,
        ?StatusMessage $status = null
    ) {
        $goodInput = true;

        // check WPA and encryption
        if (
            !array_key_exists($post['wpa'], $wpaArray) ||
            !array_key_exists($post['wpa_pairwise'], $encTypes) ||
            !array_key_exists($post['hw_mode'], $modes)
        ) {
            $err  = "Invalid WPA or encryption settings: "
                . "wpa='{$post['wpa']}', "
                . "wpa_pairwise='{$post['wpa_pairwise']}', "
                . "hw_mode='{$post['hw_mode']}'";
            error_log($err);
            return false;
        }

        // validate channel
        if (!filter_var($post['channel'], FILTER_VALIDATE_INT)) {
            $status->addMessage('Attempting to set channel to invalid number.', 'danger');
            $goodInput = false;
        }
        if ((int)$post['channel'] < 1 || (int)$post['channel'] > RASPI_5GHZ_CHANNEL_MAX) {
            $status->addMessage('Attempting to set channel outside of permitted range', 'danger');
            $goodInput = false;
        }

        // validate 802.11ax specific parameters
        if ($post['hw_mode'] === 'ax' && !$this->validateAxParams($post, $status)) {
            $goodInput = false;
        }

        // validate 802.11be specific parameters
        if ($post['hw_mode'] === 'be' && !$this->validateBeParams($post, $status)) {
            $goodInput = false;
        }

        // validate SSID
        if (empty($post['ssid']) || strlen($post['ssid']) > 32) {
            $status->addMessage('SSID must be between 1 and 32 characters', 'danger');
            $goodInput = false;
        }

        // validate WPA passphrase
        if ($post['wpa'] !== 'none') {
            if (strlen($post['wpa_passphrase']) < 8 || strlen($post['wpa_passphrase']) > 63) {
                $status->addMessage('WPA passphrase must be between 8 and 63 characters', 'danger');
                $goodInput = false;
            } elseif (!ctype_print($post['wpa_passphrase'])) {
                $status->addMessage('WPA passphrase must be comprised of printable ASCII characters', 'danger');
                $goodInput = false;
            }
        }

        // hidden SSID
        $ignoreBroadcastSSID = $post['hiddenSSID'] ?? '0';
        if (!ctype_digit($ignoreBroadcastSSID) || (int)$ignoreBroadcastSSID < 0 || (int)$ignoreBroadcastSSID >= 3) {
            $status->addMessage('Invalid hiddenSSID parameter.', 'danger');
            $goodInput = false;
        }

        // validate interface
        if (!in_array($post['interface'], $interfaces, true)) {
            $status->addMessage('Unknown interface '.htmlspecialchars($post['interface'], ENT_QUOTES), 'danger');
            $goodInput = false;
        }

        // country code
        $countryCode = $post['country_code'];
        if (strlen($countryCode) !== 0 && strlen($countryCode) !== 2) {
            $status->addMessage('Country code must be blank or two characters', 'danger');
            $goodInput = false;
        }

        // beacon Interval
        if (!empty($post['beaconintervalEnable'])) {
            if (!is_numeric($post['beacon_interval'])) {
                $status->addMessage('Beacon interval must be numeric', 'danger');
                $goodInput = false;
            } elseif ($post['beacon_interval'] < 15 || $post['beacon_interval'] > 65535) {
                $status->addMessage('Beacon interval must be between 15 and 65535', 'danger');
                $goodInput = false;
            }
        }

        // max number of clients
        $post['max_num_sta'] = (int) ($post['max_num_sta'] ?? 0);
        $post['max_num_sta'] = $post['max_num_sta'] > 2007 ? 2007 : $post['max_num_sta'];
        $post['max_num_sta'] = $post['max_num_sta'] < 1 ? null : $post['max_num_sta'];

        // validate bridged mode static IP configuration
        $bridgedEnable = !empty($post['bridgedEnable']);
        $bridgeStaticIp = trim($post['bridgeStaticIp'] ?? '');
        $bridgeNetmask = trim($post['bridgeNetmask'] ?? '');
        $bridgeGateway = trim($post['bridgeGateway'] ?? '');
        $bridgeDNS = trim($post['bridgeDNS'] ?? '');

        if ($bridgedEnable) {
            // validate static IP address
            if (!filter_var($bridgeStaticIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $status->addMessage('Bridge static IP address must be a valid IPv4 address', 'danger');
                $goodInput = false;
            }

            // validate netmask (CIDR notation)
            if (!empty($bridgeNetmask)) {
                if (!ctype_digit($bridgeNetmask) || (int)$bridgeNetmask < 1 || (int)$bridgeNetmask > 32) {
                    $status->addMessage('Bridge netmask must be a number between 1 and 32', 'danger');
                    $goodInput = false;
                }
            } else {
                $status->addMessage('Bridge netmask is required when using static IP', 'danger');
                $goodInput = false;
            }

            // validate gateway
            if (!empty($bridgeGateway)) {
                if (!filter_var($bridgeGateway, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $status->addMessage('Bridge gateway must be a valid IPv4 address', 'danger');
                    $goodInput = false;
                }
            } else {
                $status->addMessage('Bridge gateway is required when using static IP', 'danger');
                $goodInput = false;
            }

            // validate DNS server
            if (!empty($bridgeDNS)) {
                if (!filter_var($bridgeDNS, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $status->addMessage('Bridge DNS server must be a valid IPv4 address', 'danger');
                    $goodInput = false;
                }
            } else {
                $status->addMessage('Bridge DNS server is required when using static IP', 'danger');
                $goodInput = false;
            }

            // validate that static IP and gateway are in the same subnet
            if ($goodInput && !empty($bridgeStaticIp) && !empty($bridgeGateway) && !empty($bridgeNetmask)) {
                $ipLong = ip2long($bridgeStaticIp);
                $gatewayLong = ip2long($bridgeGateway);
                $mask = -1 << (32 - (int)$bridgeNetmask);

                if (($ipLong & $mask) !== ($gatewayLong & $mask)) {
                    $status->addMessage('Bridge static IP and gateway must be in the same subnet', 'danger');
                    $goodInput = false;
                }
            }
        }

        if (!$goodInput) {
            return false;
        }

        // return normalized config array
        return [
            'interface'        => $post['interface'],
            'ssid'             => $post['ssid'],
            'channel'          => (int)$post['channel'],
            'wpa'              => $post['wpa'],
            '80211w'           => $post['80211w'] ?? 0,
            'wpa_passphrase'   => $post['wpa_passphrase'],
            'wpa_pairwise'     => $post['wpa_pairwise'],
            'hw_mode'          => $post['hw_mode'],
            'country_code'     => $countryCode,
            'hiddenSSID'       => (int)$ignoreBroadcastSSID,
            'max_num_sta'      => $post['max_num_sta'],
            'beacon_interval'  => $post['beacon_interval'] ?? null,
            'disassoc_low_ack' => $post['disassoc_low_ackEnable'] ?? null,
            'bridge'           => ($post['bridgedEnable'] ?? false) ? 'br0' : null,
            'bridgeStaticIp'   => ($post['bridgeStaticIp']),
            'bridgeNetmask'    => ($post['bridgeNetmask']),
            'bridgeGateway'    => ($post['bridgeGateway']),
            'bridgeDNS'        => ($post['bridgeDNS']),
            'he_oper_chwidth'  => $post['he_oper_chwidth'] ?? null, // 802.11ax parameters
            'he_bss_color'     => $post['he_bss_color'] ?? null, // 802.11be parameters
            'eht_oper_chwidth' => $post['eht_oper_chwidth'] ?? null
        ];
    }

    /**
     * Validates 802.11ax (Wi-Fi 6) specific parameters
     *
     * @param array $post
     * @param StatusMessage $status
     * @return bool
     */
    private function validateAxParams(array $post, StatusMessage $status): bool
    {
        $valid = true;

        // Validate HE channel width
        if (isset($post['he_oper_chwidth'])) {
            $chwidth = (int)$post['he_oper_chwidth'];
            if (!in_array($chwidth, self::HE_VALID_CHWIDTHS, true)) {
                $status->addMessage('Invalid 802.11ax channel width. Must be 0 (20/40 MHz), 1 (80 MHz), or 2 (160 MHz)', 'danger');
                $valid = false;
            }
        }

        // Validate BSS color (1-63)
        if (isset($post['he_bss_color'])) {
            $bssColor = (int)$post['he_bss_color'];
            if ($bssColor < 1 || $bssColor > 63) {
                $status->addMessage('802.11ax BSS color must be between 1 and 63', 'danger');
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Validates 802.11be (Wi-Fi 7) specific parameters
     *
     * @param array $post
     * @param StatusMessage $status
     * @return bool
     */
    private function validateBeParams(array $post, StatusMessage $status): bool
    {
        $valid = true;
        $channel = (int)$post['channel'];

        // Validate EHT channel width
        if (isset($post['eht_oper_chwidth'])) {
            $chwidth = (int)$post['eht_oper_chwidth'];

            if (!in_array($chwidth, self::EHT_VALID_CHWIDTHS, true)) {
                $status->addMessage('Invalid 802.11be channel width. Must be 0-4 (20, 40, 80, 160, or 320 MHz)', 'danger');
                $valid = false;
            }

            // 320 MHz only valid on 6 GHz band
            if ($chwidth === 4) {
                if ($channel < self::CHANNEL_6GHZ_MIN || $channel > self::CHANNEL_6GHZ_MAX) {
                    $status->addMessage('802.11be 320 MHz channel width is only available on 6 GHz band (channels 1-233)', 'danger');
                    $valid = false;
                }
            }
        }

        // Validate BSS color (same as 802.11ax, inherited)
        if (isset($post['he_bss_color'])) {
            $bssColor = (int)$post['he_bss_color'];
            if ($bssColor < 1 || $bssColor > 63) {
                $status->addMessage('BSS color must be between 1 and 63', 'danger');
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Checks if channel is in 6GHz band
     *
     * @param int $channel
     * @return bool
     */
    private function is6GHzChannel(int $channel): bool
    {
        return $channel >= self::CHANNEL_6GHZ_MIN && $channel <= self::CHANNEL_6GHZ_MAX;
    }

}

