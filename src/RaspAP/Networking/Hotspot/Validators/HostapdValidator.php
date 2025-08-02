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
            'bridge'           => ($post['bridgedEnable'] ?? false) ? 'br0' : null
        ];
    }

}

