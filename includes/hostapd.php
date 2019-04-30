<?php

include_once('includes/status_messages.php');

/**
*
*
*/
function DisplayHostAPDConfig()
{
    $status = new StatusMessages();
    $arrHostapdConf = parse_ini_file('/etc/raspap/hostapd.ini');
    $arrConfig = array();
    $arr80211Standard = array('a','b','g','n');
    $arrSecurity = array(1 => 'WPA', 2 => 'WPA2', 3 => 'WPA+WPA2', 'none' => _("None"));
    $arrEncType = array('TKIP' => 'TKIP', 'CCMP' => 'CCMP', 'TKIP CCMP' => 'TKIP+CCMP');
    exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);

    if (isset($_POST['SaveHostAPDSettings'])) {
        if (CSRFValidate()) {
            SaveHostAPDConfig($arrSecurity, $arrEncType, $arr80211Standard, $interfaces, $status);
        } else {
            error_log('CSRF violation');
        }
    } elseif (isset($_POST['StartHotspot'])) {
        if (CSRFValidate()) {
            $status->addMessage('Attempting to start hotspot', 'info');
            if ($arrHostapdConf['WifiAPEnable'] == 1) {
                exec('sudo /etc/raspap/hostapd/servicestart.sh --interface uap0 --seconds 3', $return);
            } else {
                exec('sudo /etc/raspap/hostapd/servicestart.sh --seconds 5', $return);
            }
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } else {
            error_log('CSRF violation');
        }
    } elseif (isset($_POST['StopHotspot'])) {
        if (CSRFValidate()) {
            $status->addMessage('Attempting to stop hotspot', 'info');
            exec('sudo /etc/init.d/hostapd stop', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } else {
            error_log('CSRF violation');
        }
    }

    exec('cat '. RASPI_HOSTAPD_CONFIG, $hostapdconfig);
    exec('pidof hostapd | wc -l', $hostapdstatus);

    if ($hostapdstatus[0] == 0) {
        $status->addMessage('HostAPD is not running', 'warning');
    } else {
        $status->addMessage('HostAPD is running', 'success');
    }

    foreach ($hostapdconfig as $hostapdconfigline) {
        if (strlen($hostapdconfigline) === 0) {
            continue;
        }

        if ($hostapdconfigline[0] != "#") {
            $arrLine = explode("=", $hostapdconfigline) ;
            $arrConfig[$arrLine[0]]=$arrLine[1];
        }
    };

?>
  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-primary">
        <div class="panel-heading"><i class="fa fa-dot-circle-o fa-fw"></i> <?php echo _("Configure hotspot"); ?></div>
        <!-- /.panel-heading -->
        <div class="panel-body">
      <p><?php $status->showMessages(); ?></p>
          <form role="form" action="?page=hostapd_conf" method="POST">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
              <li class="active"><a href="#basic" data-toggle="tab"><?php echo _("Basic"); ?></a></li>
              <li><a href="#security" data-toggle="tab"><?php echo _("Security"); ?></a></li>
              <li><a href="#advanced" data-toggle="tab"><?php echo _("Advanced"); ?></a></li>
              <li><a href="#logoutput" data-toggle="tab"><?php echo _("Logfile"); ?></a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <div class="tab-pane fade in active" id="basic">

                <h4><?php echo _("Basic settings") ;?></h4>
                <?php CSRFToken() ?>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="cbxinterface"><?php echo _("Interface") ;?></label>
                    <?php
                      SelectorOptions('interface', $interfaces, $arrConfig['interface'], 'cbxinterface');
                    ?>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="txtssid"><?php echo _("SSID"); ?></label>
                    <input type="text" id="txtssid" class="form-control" name="ssid" value="<?php echo htmlspecialchars($arrConfig['ssid'], ENT_QUOTES); ?>" />
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="cbxhwmode"><?php echo _("Wireless Mode") ;?></label>
                    <?php
                    $selectedHwMode = $arrConfig['hw_mode'];
                    if (isset($arrConfig['ieee80211n'])) {
                        if (strval($arrConfig['ieee80211n']) === '1') {
                            $selectedHwMode = 'n';
                        }
                    }

                    SelectorOptions('hw_mode', $arr80211Standard, $selectedHwMode, 'cbxhwmode'); ?>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="cbxchannel"><?php echo _("Channel"); ?></label>
                    <?php
                    $selectablechannels = range(1, 13);
                    $countries_2_4Ghz_max11ch = array('AG', 'BS', 'BB', 'BZ', 'CR', 'CU', 'DM', 'DO', 'SV', 'GD', 'GT',
                                 'HT', 'HN', 'JM', 'MX', 'NI', 'PA', 'KN', 'LC', 'VC', 'TT',
                                 'US', 'CA', 'UZ', 'CO');
                    $countries_2_4Ghz_max14ch = array('JA');
                    if (in_array($arrConfig['country_code'], $countries_max11channels)) {
                        // In North America till channel 11 is the maximum allowed wi-fi 2.4Ghz channel.
                        // Except for the US that allows channel 12 & 13 in low power mode with additional restrictions.
                        // Canada that allows channel 12 in low power mode. Because it's unsure if low powered mode
                        // can be supported the channels are not selectable for those countries.
                        // source: https://en.wikipedia.org/wiki/List_of_WLAN_channels#Interference_concerns
                        // Also Uzbekistan and Colombia allow to select till channel 11 as maximum channel on the 2.4Ghz wi-fi band.
                        $selectablechannels = range(1, 11);
                    } elseif (in_array($arrConfig['country_code'], $countries_2_4Ghz_max14ch)) {
                        if ($arrConfig['hw_mode'] === 'b') {
                            $selectablechannels = range(1, 14);
                        }
                    }
                    SelectorOptions('channel', $selectablechannels, intval($arrConfig['channel']), 'cbxchannel'); ?>
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="security">
                <h4><?php echo _("Security settings"); ?></h4>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="cbxwpa"><?php echo _("Security type"); ?></label>
                    <?php SelectorOptions('wpa', $arrSecurity, $arrConfig['wpa'], 'cbxwpa'); ?>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="cbxwpapairwise"><?php echo _("Encryption Type"); ?></label>
                    <?php SelectorOptions('wpa_pairwise', $arrEncType, $arrConfig['wpa_pairwise'], 'cbxwpapairwise'); ?>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="txtwpapassphrase"><?php echo _("PSK"); ?></label>
                    <input type="text" class="form-control" id="txtwpapassphrase" name="wpa_passphrase" value="<?php echo htmlspecialchars($arrConfig['wpa_passphrase'], ENT_QUOTES); ?>" />
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="logoutput">
          <h4><?php echo _("Logfile output"); ?></h4>
                  <div class="row">
                    <div class="form-group col-md-8">
                        <?php
                        if ($arrHostapdConf['LogEnable'] == 1) {
                            $log = file_get_contents('/tmp/hostapd.log');
                            echo '<br /><textarea class="logoutput">'.htmlspecialchars($log, ENT_QUOTES).'</textarea>';
                        } else {
                            echo "<br />Logfile output not enabled";
                        }
                        ?>
                   </div>
                </div>
              </div>
              <div class="tab-pane fade" id="advanced">
        <h4><?php echo _("Advanced settings"); ?></h4>
                <div class="row">
                  <div class="col-md-4">
            <div class="checkbox">
<?php
$checkedWifiAPEnabled = '';
if ($arrHostapdConf['WifiAPEnable'] == 1) {
    $checkedWifiAPEnabled = ' checked="checked"';
}
?>
              <input id="chxwificlientap" name="wifiAPEnable" type="checkbox" class="form-check-input" data-toggle="toggle" data-on="Enabled" data-off="Disabled" data-width="100" value="1"<?php echo $checkedWifiAPEnabled; ?> />
              <label class="form-check-label" for="chxwificlientap"><?php echo _("WiFi client AP mode"); ?></label>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
            <div class="checkbox">
<?php
$checkedLogEnabled = '';
if ($arrHostapdConf['LogEnable'] == 1) {
    $checkedLogEnabled = ' checked="checked"';
}
?>
              <input id="chxlogenable" name="logEnable" type="checkbox" class="form-check-input" data-toggle="toggle" data-on="Enabled" data-off="Disabled" data-width="100" value="1"<?php echo $checkedLogEnabled; ?> />
              <label class="form-check-label" for="chxlogenable"><?php echo _("Logfile output"); ?></label>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
            <div class="checkbox">
<?php
$checkedHiddenSSID = '';
if ($arrConfig['ignore_broadcast_ssid'] == 1 || $arrConfig['ignore_broadcast_ssid'] == 2) {
    $checkedHiddenSSID = ' checked="checked"';
}
?>
              <input id="chxhiddenssid" name="hiddenSSID" type="checkbox" class="form-check-input" data-toggle="toggle" data-on="Enabled" data-off="Disabled" data-width="100" value="1"<?php echo $checkedHiddenSSID; ?> />
              <label class="form-check-label" for="chxhiddenssid"><?php echo _("Hide SSID in broadcast"); ?></label>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                  <label for="cbxcountries"><?php echo _("Country Code"); ?></label>
                  <input type="hidden" id="selected_country" value="<?php echo htmlspecialchars($arrConfig['country_code'], ENT_QUOTES); ?>">
                  <select  class="form-control" id="cbxcountries" name="country_code">
                    <option value="AF">Afghanistan</option>
                    <option value="AX">Åland Islands</option>
                    <option value="AL">Albania</option>
                    <option value="DZ">Algeria</option>
                    <option value="AS">American Samoa</option>
                    <option value="AD">Andorra</option>
                    <option value="AO">Angola</option>
                    <option value="AI">Anguilla</option>
                    <option value="AQ">Antarctica</option>
                    <option value="AG">Antigua and Barbuda</option>
                    <option value="AR">Argentina</option>
                    <option value="AM">Armenia</option>
                    <option value="AW">Aruba</option>
                    <option value="AU">Australia</option>
                    <option value="AT">Austria</option>
                    <option value="AZ">Azerbaijan</option>
                    <option value="BS">Bahamas</option>
                    <option value="BH">Bahrain</option>
                    <option value="BD">Bangladesh</option>
                    <option value="BB">Barbados</option>
                    <option value="BY">Belarus</option>
                    <option value="BE">Belgium</option>
                    <option value="BZ">Belize</option>
                    <option value="BJ">Benin</option>
                    <option value="BM">Bermuda</option>
                    <option value="BT">Bhutan</option>
                    <option value="BO">Bolivia, Plurinational State of</option>
                    <option value="BQ">Bonaire, Sint Eustatius and Saba</option>
                    <option value="BA">Bosnia and Herzegovina</option>
                    <option value="BW">Botswana</option>
                    <option value="BV">Bouvet Island</option>
                    <option value="BR">Brazil</option>
                    <option value="IO">British Indian Ocean Territory</option>
                    <option value="BN">Brunei Darussalam</option>
                    <option value="BG">Bulgaria</option>
                    <option value="BF">Burkina Faso</option>
                    <option value="BI">Burundi</option>
                    <option value="KH">Cambodia</option>
                    <option value="CM">Cameroon</option>
                    <option value="CA">Canada</option>
                    <option value="CV">Cape Verde</option>
                    <option value="KY">Cayman Islands</option>
                    <option value="CF">Central African Republic</option>
                    <option value="TD">Chad</option>
                    <option value="CL">Chile</option>
                    <option value="CN">China</option>
                    <option value="CX">Christmas Island</option>
                    <option value="CC">Cocos (Keeling) Islands</option>
                    <option value="CO">Colombia</option>
                    <option value="KM">Comoros</option>
                    <option value="CG">Congo</option>
                    <option value="CD">Congo, the Democratic Republic of the</option>
                    <option value="CK">Cook Islands</option>
                    <option value="CR">Costa Rica</option>
                    <option value="CI">Côte d'Ivoire</option>
                    <option value="HR">Croatia</option>
                    <option value="CU">Cuba</option>
                    <option value="CW">Curaçao</option>
                    <option value="CY">Cyprus</option>
                    <option value="CZ">Czech Republic</option>
                    <option value="DK">Denmark</option>
                    <option value="DJ">Djibouti</option>
                    <option value="DM">Dominica</option>
                    <option value="DO">Dominican Republic</option>
                    <option value="EC">Ecuador</option>
                    <option value="EG">Egypt</option>
                    <option value="SV">El Salvador</option>
                    <option value="GQ">Equatorial Guinea</option>
                    <option value="ER">Eritrea</option>
                    <option value="EE">Estonia</option>
                    <option value="ET">Ethiopia</option>
                    <option value="FK">Falkland Islands (Malvinas)</option>
                    <option value="FO">Faroe Islands</option>
                    <option value="FJ">Fiji</option>
                    <option value="FI">Finland</option>
                    <option value="FR">France</option>
                    <option value="GF">French Guiana</option>
                    <option value="PF">French Polynesia</option>
                    <option value="TF">French Southern Territories</option>
                    <option value="GA">Gabon</option>
                    <option value="GM">Gambia</option>
                    <option value="GE">Georgia</option>
                    <option value="DE">Germany</option>
                    <option value="GH">Ghana</option>
                    <option value="GI">Gibraltar</option>
                    <option value="GR">Greece</option>
                    <option value="GL">Greenland</option>
                    <option value="GD">Grenada</option>
                    <option value="GP">Guadeloupe</option>
                    <option value="GU">Guam</option>
                    <option value="GT">Guatemala</option>
                    <option value="GG">Guernsey</option>
                    <option value="GN">Guinea</option>
                    <option value="GW">Guinea-Bissau</option>
                    <option value="GY">Guyana</option>
                    <option value="HT">Haiti</option>
                    <option value="HM">Heard Island and McDonald Islands</option>
                    <option value="VA">Holy See (Vatican City State)</option>
                    <option value="HN">Honduras</option>
                    <option value="HK">Hong Kong</option>
                    <option value="HU">Hungary</option>
                    <option value="IS">Iceland</option>
                    <option value="IN">India</option>
                    <option value="ID">Indonesia</option>
                    <option value="IR">Iran, Islamic Republic of</option>
                    <option value="IQ">Iraq</option>
                    <option value="IE">Ireland</option>
                    <option value="IM">Isle of Man</option>
                    <option value="IL">Israel</option>
                    <option value="IT">Italy</option>
                    <option value="JM">Jamaica</option>
                    <option value="JP">Japan</option>
                    <option value="JE">Jersey</option>
                    <option value="JO">Jordan</option>
                    <option value="KZ">Kazakhstan</option>
                    <option value="KE">Kenya</option>
                    <option value="KI">Kiribati</option>
                    <option value="KP">Korea, Democratic People's Republic of</option>
                    <option value="KR">Korea, Republic of</option>
                    <option value="KW">Kuwait</option>
                    <option value="KG">Kyrgyzstan</option>
                    <option value="LA">Lao People's Democratic Republic</option>
                    <option value="LV">Latvia</option>
                    <option value="LB">Lebanon</option>
                    <option value="LS">Lesotho</option>
                    <option value="LR">Liberia</option>
                    <option value="LY">Libya</option>
                    <option value="LI">Liechtenstein</option>
                    <option value="LT">Lithuania</option>
                    <option value="LU">Luxembourg</option>
                    <option value="MO">Macao</option>
                    <option value="MK">Macedonia, the former Yugoslav Republic of</option>
                    <option value="MG">Madagascar</option>
                    <option value="MW">Malawi</option>
                    <option value="MY">Malaysia</option>
                    <option value="MV">Maldives</option>
                    <option value="ML">Mali</option>
                    <option value="MT">Malta</option>
                    <option value="MH">Marshall Islands</option>
                    <option value="MQ">Martinique</option>
                    <option value="MR">Mauritania</option>
                    <option value="MU">Mauritius</option>
                    <option value="YT">Mayotte</option>
                    <option value="MX">Mexico</option>
                    <option value="FM">Micronesia, Federated States of</option>
                    <option value="MD">Moldova, Republic of</option>
                    <option value="MC">Monaco</option>
                    <option value="MN">Mongolia</option>
                    <option value="ME">Montenegro</option>
                    <option value="MS">Montserrat</option>
                    <option value="MA">Morocco</option>
                    <option value="MZ">Mozambique</option>
                    <option value="MM">Myanmar</option>
                    <option value="NA">Namibia</option>
                    <option value="NR">Nauru</option>
                    <option value="NP">Nepal</option>
                    <option value="NL">Netherlands</option>
                    <option value="NC">New Caledonia</option>
                    <option value="NZ">New Zealand</option>
                    <option value="NI">Nicaragua</option>
                    <option value="NE">Niger</option>
                    <option value="NG">Nigeria</option>
                    <option value="NU">Niue</option>
                    <option value="NF">Norfolk Island</option>
                    <option value="MP">Northern Mariana Islands</option>
                    <option value="NO">Norway</option>
                    <option value="OM">Oman</option>
                    <option value="PK">Pakistan</option>
                    <option value="PW">Palau</option>
                    <option value="PS">Palestinian Territory, Occupied</option>
                    <option value="PA">Panama</option>
                    <option value="PG">Papua New Guinea</option>
                    <option value="PY">Paraguay</option>
                    <option value="PE">Peru</option>
                    <option value="PH">Philippines</option>
                    <option value="PN">Pitcairn</option>
                    <option value="PL">Poland</option>
                    <option value="PT">Portugal</option>
                    <option value="PR">Puerto Rico</option>
                    <option value="QA">Qatar</option>
                    <option value="RE">Réunion</option>
                    <option value="RO">Romania</option>
                    <option value="RU">Russian Federation</option>
                    <option value="RW">Rwanda</option>
                    <option value="BL">Saint Barthélemy</option>
                    <option value="SH">Saint Helena, Ascension and Tristan da Cunha</option>
                    <option value="KN">Saint Kitts and Nevis</option>
                    <option value="LC">Saint Lucia</option>
                    <option value="MF">Saint Martin (French part)</option>
                    <option value="PM">Saint Pierre and Miquelon</option>
                    <option value="VC">Saint Vincent and the Grenadines</option>
                    <option value="WS">Samoa</option>
                    <option value="SM">San Marino</option>
                    <option value="ST">Sao Tome and Principe</option>
                    <option value="SA">Saudi Arabia</option>
                    <option value="SN">Senegal</option>
                    <option value="RS">Serbia</option>
                    <option value="SC">Seychelles</option>
                    <option value="SL">Sierra Leone</option>
                    <option value="SG">Singapore</option>
                    <option value="SX">Sint Maarten (Dutch part)</option>
                    <option value="SK">Slovakia</option>
                    <option value="SI">Slovenia</option>
                    <option value="SB">Solomon Islands</option>
                    <option value="SO">Somalia</option>
                    <option value="ZA">South Africa</option>
                    <option value="GS">South Georgia and the South Sandwich Islands</option>
                    <option value="SS">South Sudan</option>
                    <option value="ES">Spain</option>
                    <option value="LK">Sri Lanka</option>
                    <option value="SD">Sudan</option>
                    <option value="SR">Suriname</option>
                    <option value="SJ">Svalbard and Jan Mayen</option>
                    <option value="SZ">Swaziland</option>
                    <option value="SE">Sweden</option>
                    <option value="CH">Switzerland</option>
                    <option value="SY">Syrian Arab Republic</option>
                    <option value="TW">Taiwan, Province of China</option>
                    <option value="TJ">Tajikistan</option>
                    <option value="TZ">Tanzania, United Republic of</option>
                    <option value="TH">Thailand</option>
                    <option value="TL">Timor-Leste</option>
                    <option value="TG">Togo</option>
                    <option value="TK">Tokelau</option>
                    <option value="TO">Tonga</option>
                    <option value="TT">Trinidad and Tobago</option>
                    <option value="TN">Tunisia</option>
                    <option value="TR">Turkey</option>
                    <option value="TM">Turkmenistan</option>
                    <option value="TC">Turks and Caicos Islands</option>
                    <option value="TV">Tuvalu</option>
                    <option value="UG">Uganda</option>
                    <option value="UA">Ukraine</option>
                    <option value="AE">United Arab Emirates</option>
                    <option value="GB">United Kingdom</option>
                    <option value="US">United States</option>
                    <option value="UM">United States Minor Outlying Islands</option>
                    <option value="UY">Uruguay</option>
                    <option value="UZ">Uzbekistan</option>
                    <option value="VU">Vanuatu</option>
                    <option value="VE">Venezuela, Bolivarian Republic of</option>
                    <option value="VN">Viet Nam</option>
                    <option value="VG">Virgin Islands, British</option>
                    <option value="VI">Virgin Islands, U.S.</option>
                    <option value="WF">Wallis and Futuna</option>
                    <option value="EH">Western Sahara</option>
                    <option value="YE">Yemen</option>
                    <option value="ZM">Zambia</option>
                    <option value="ZW">Zimbabwe</option>
                  </select>
<script type="text/javascript">
var country = document.getElementById("selected_country").value;
var countries = document.getElementById("cbxcountries");
var ops = countries.getElementsByTagName("option");
for (var i = 0; i < ops.length; ++i) {
    if(ops[i].value == country){
        ops[i].selected=true;
        break;
    }
}

</script>
                </div>
              </div><!-- /.panel-body -->
            </div><!-- /.panel-primary -->
            <input type="submit" class="btn btn-outline btn-primary" name="SaveHostAPDSettings" value="<?php echo _("Save settings"); ?>" />
            <?php
            if ($hostapdstatus[0] == 0) {
                echo '<input type="submit" class="btn btn-success" name="StartHotspot" value="' . _("Start hotspot") . '"/>' , PHP_EOL;
            } else {
                echo '<input type="submit" class="btn btn-warning" name="StopHotspot" value="' . _("Stop hotspot") . '"/>' , PHP_EOL;
            };
?>
          </form>
        </div></div><!-- /.panel-primary -->
      <div class="panel-footer"> <?php echo _("Information provided by hostapd"); ?></div>
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
<?php
}

function SaveHostAPDConfig($wpa_array, $enc_types, $modes, $interfaces, $status)
{
    // It should not be possible to send bad data for these fields so clearly
    // someone is up to something if they fail. Fail silently.
    if (!(array_key_exists($_POST['wpa'], $wpa_array) &&
      array_key_exists($_POST['wpa_pairwise'], $enc_types) &&
      in_array($_POST['hw_mode'], $modes))) {
        error_log("Attempting to set hostapd config with wpa='".$_POST['wpa']."', wpa_pairwise='".$_POST['wpa_pairwise']."' and hw_mode='".$_POST['hw_mode']."'");  // FIXME: log injection
        return false;
    }

    if (!filter_var($_POST['channel'], FILTER_VALIDATE_INT)) {
        error_log("Attempting to set channel to invalid number.");
        return false;
    }

    if (intval($_POST['channel']) < 1 || intval($_POST['channel']) > 14) {
        error_log("Attempting to set channel to '".$_POST['channel']."'");
        return false;
    }

    $good_input = true;
  
    // Check for WiFi client AP mode checkbox
    $wifiAPEnable = 0;
    if ($arrHostapdConf['WifiAPEnable'] == 0) {
        if (isset($_POST['wifiAPEnable'])) {
            $wifiAPEnable = 1;
        }
    } else {
        if (isset($_POST['wifiAPEnable'])) {
            $wifiAPEnable = 1;
        }
    }

    // Check for Logfile output checkbox
    $logEnable = 0;
    if ($arrHostapdConf['LogEnable'] == 0) {
        if (isset($_POST['logEnable'])) {
            $logEnable = 1;
            exec('sudo /etc/raspap/hostapd/enablelog.sh');
        } else {
            exec('sudo /etc/raspap/hostapd/disablelog.sh');
        }
    } else {
        if (isset($_POST['logEnable'])) {
            $logEnable = 1;
            exec('sudo /etc/raspap/hostapd/enablelog.sh');
        } else {
            exec('sudo /etc/raspap/hostapd/disablelog.sh');
        }
    }
    $cfg = [];
    $cfg['LogEnable'] = $logEnable;
    $cfg['WifiAPEnable'] = $wifiAPEnable;
    $cfg['WifiManaged'] = RASPI_WIFI_CLIENT_INTERFACE;
    write_php_ini($cfg, '/etc/raspap/hostapd.ini');

    // Verify input
    if (empty($_POST['ssid']) || strlen($_POST['ssid']) > 32) {
        // Not sure of all the restrictions of SSID
        $status->addMessage('SSID must be between 1 and 32 characters', 'danger');
        $good_input = false;
    }

    if ($_POST['wpa'] !== 'none' &&
      (strlen($_POST['wpa_passphrase']) < 8 || strlen($_POST['wpa_passphrase']) > 63)) {
        $status->addMessage('WPA passphrase must be between 8 and 63 characters', 'danger');
        $good_input = false;
    }

    if (isset($_POST['hiddenSSID'])) {
        if (!is_int((int)$_POST['hiddenSSID'])) {
            $status->addMessage('Parameter hiddenSSID not a number.', 'danger');
            $good_input = false;
        } elseif ((int)$_POST['hiddenSSID'] < 0 || (int)$_POST['hiddenSSID'] >= 3) {
            $status->addMessage('Parameter hiddenSSID contains invalid configuratie value.', 'danger');
            $good_input = false;
        } else {
            $ignore_broadcast_ssid = $_POST['hiddenSSID'];
        }
    } else {
        $ignore_broadcast_ssid = '0';
    }

    if (! in_array($_POST['interface'], $interfaces)) {
        // The user is probably up to something here but it may also be a
        // genuine error.
        $status->addMessage('Unknown interface '.htmlspecialchars($_POST['interface'], ENT_QUOTES), 'danger');
        $good_input = false;
    }

    if (strlen($_POST['country_code']) !== 0 && strlen($_POST['country_code']) != 2) {
        $status->addMessage('Country code must be blank or two characters', 'danger');
        $good_input = false;
    }

    if ($good_input) {
        // Fixed values
        $config = 'driver=nl80211'.PHP_EOL;
        $config.= 'ctrl_interface='.RASPI_HOSTAPD_CTRL_INTERFACE.PHP_EOL;
        $config.= 'ctrl_interface_group=0'.PHP_EOL;
        $config.= 'auth_algs=1'.PHP_EOL;
        $config.= 'wpa_key_mgmt=WPA-PSK'.PHP_EOL;
        $config.= 'beacon_int=100'.PHP_EOL;
        $config.= 'ssid='.$_POST['ssid'].PHP_EOL;
        $config.= 'channel='.$_POST['channel'].PHP_EOL;
        if ($_POST['hw_mode'] === 'n') {
            $config.= 'hw_mode=g'.PHP_EOL;
            $config.= 'ieee80211n=1'.PHP_EOL;
            // Enable basic Quality of service
            $config.= 'wme_enabled=1'.PHP_EOL;
        } else {
            $config.= 'hw_mode='.$_POST['hw_mode'].PHP_EOL;
            $config.= 'ieee80211n=0'.PHP_EOL;
        }
        $config.= 'wpa_passphrase='.$_POST['wpa_passphrase'].PHP_EOL;
        if ($wifiAPEnable == 1) {
            $config.= 'interface=uap0'.PHP_EOL;
        } else {
            $config.= 'interface='.$_POST['interface'].PHP_EOL;
        }
        $config.= 'wpa='.$_POST['wpa'].PHP_EOL;
        $config.= 'wpa_pairwise='.$_POST['wpa_pairwise'].PHP_EOL;
        $config.= 'country_code='.$_POST['country_code'].PHP_EOL;
        $config.= 'ignore_broadcast_ssid='.$ignore_broadcast_ssid.PHP_EOL;

        exec('echo "'.$config.'" > /tmp/hostapddata', $temp);
        system("sudo cp /tmp/hostapddata " . RASPI_HOSTAPD_CONFIG, $return);

        if ($wifiAPEnable == 1) {
            // Enable uap0 configuration in dnsmasq for Wifi client AP mode
            $config = 'interface=lo,uap0               # Enable uap0 interface for wireless client AP mode'.PHP_EOL;
            $config.= 'bind-interfaces                 # Bind to the interfaces'.PHP_EOL;
            $config.= 'server=8.8.8.8                  # Forward DNS requests to Google DNS'.PHP_EOL;
            $config.= 'domain-needed                   # Don\'t forward short names'.PHP_EOL;
            $config.= 'bogus-priv                      # Never forward addresses in the non-routed address spaces'.PHP_EOL;
            $config.= 'dhcp-range=192.168.50.50,192.168.50.150,12h'.PHP_EOL;
        } else {
            // Fallback to default config
            $config = 'domain-needed'.PHP_EOL;
            $config.= 'interface='.$_POST['interface'].PHP_EOL;
            $config.= 'dhcp-range=10.3.141.50,10.3.141.255,255.255.255.0,12h'.PHP_EOL;
        }
        exec('echo "'.$config.'" > /tmp/dhcpddata', $temp);
        system('sudo cp /tmp/dhcpddata '.RASPI_DNSMASQ_CONFIG, $return);

        if ($wifiAPEnable == 1) {
            // Enable uap0 configuration in dhcpcd for Wifi client AP mode
             $config = PHP_EOL.'# RaspAP uap0 configuration'.PHP_EOL;
             $config.= 'interface uap0'.PHP_EOL;
             $config.= 'static ip_address=192.168.50.1/24'.PHP_EOL;
             $config.= 'nohook wpa_supplicant'.PHP_EOL;
        } else {
            // Default config
            $config = '# RaspAP wlan0 configuration'.PHP_EOL;
            $config.= 'hostname'.PHP_EOL;
            $config.= 'clientid'.PHP_EOL;
            $config.= 'persistent'.PHP_EOL;
            $config.= 'option rapid_commit'.PHP_EOL;
            $config.= 'option domain_name_servers, domain_name, domain_search, host_name'.PHP_EOL;
            $config.= 'option classless_static_routes'.PHP_EOL;
            $config.= 'option ntp_servers'.PHP_EOL;
            $config.= 'require dhcp_server_identifier'.PHP_EOL;
            $config.= 'slaac private'.PHP_EOL;
            $config.= 'nohook lookup-hostname'.PHP_EOL;
            $config.= 'interface '.RASPI_WIFI_CLIENT_INTERFACE.PHP_EOL;
            $config.= 'static ip_address=10.3.141.1/24'.PHP_EOL;
            $config.= 'static routers=10.3.141.1'.PHP_EOL;
            $config.= 'static domain_name_server=1.1.1.1 8.8.8.8'.PHP_EOL;
        }
        exec('echo "'.$config.'" > /tmp/dhcpddata', $temp);
        system('sudo cp /tmp/dhcpddata '.RASPI_DHCPCD_CONFIG, $return);


        if ($return == 0) {
            $status->addMessage('Wifi Hotspot settings saved', 'success');
        } else {
            $status->addMessage('Unable to save wifi hotspot settings', 'danger');
        }
    } else {
        $status->addMessage('Unable to save wifi hotspot settings', 'danger');
        return false;
    }

    return true;
}

