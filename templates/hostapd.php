<div class="row">
<div class="col-lg-12">
  <div class="card">
    <div class="card-header">
      <div class="row">
        <div class="col">
          <i class="far fa-dot-circle mr-2"></i><?php echo _("Configure hotspot"); ?>
        </div>
        <div class="col">
          <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
            <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
            <span class="text service-status">hostapd <?php echo _($serviceStatus) ?></span>
          </button>
        </div>
      </div><!-- /.row -->
    </div><!-- /.card-header -->
    <div class="card-body">
    <?php $status->showMessages(); ?>
      <form role="form" action="?page=hostapd_conf" method="POST">
        <?php echo CSRFTokenFieldTag() ?>
        <!-- Nav tabs -->
        <ul class="nav nav-tabs">
          <li class="nav-item"><a class="nav-link active" id="basictab" href="#basic" aria-controls="basic" data-toggle="tab"><?php echo _("Basic"); ?></a></li>
          <li class="nav-item"><a class="nav-link" id="securitytab" href="#security" data-toggle="tab"><?php echo _("Security"); ?></a></li>
          <li class="nav-item"><a class="nav-link" id="advancedtab" href="#advanced" data-toggle="tab"><?php echo _("Advanced"); ?></a></li>
          <li class="nav-item"><a class="nav-link" id="logoutputtab" href="#logoutput" data-toggle="tab"><?php echo _("Logfile output"); ?></a></li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane active" id="basic">

            <h4 class="mt-3"><?php echo _("Basic settings") ;?></h4>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="cbxinterface"><?php echo _("Interface") ;?></label>
                <?php
                  SelectorOptions('interface', $interfaces, $arrConfig['interface'], 'cbxinterface');
                ?>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="txtssid"><?php echo _("SSID"); ?></label>
                <input type="text" id="txtssid" class="form-control" name="ssid" value="<?php echo htmlspecialchars($arrConfig['ssid'], ENT_QUOTES); ?>" />
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
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
              <div class="form-group col-md-6">
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
            <h4 class="mt-3"><?php echo _("Security settings"); ?></h4>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="cbxwpa"><?php echo _("Security type"); ?></label>
                <?php SelectorOptions('wpa', $arrSecurity, $arrConfig['wpa'], 'cbxwpa'); ?>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="cbxwpapairwise"><?php echo _("Encryption Type"); ?></label>
                <?php SelectorOptions('wpa_pairwise', $arrEncType, $arrConfig['wpa_pairwise'], 'cbxwpapairwise'); ?>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="txtwpapassphrase"><?php echo _("PSK"); ?></label>
                <input type="text" class="form-control" id="txtwpapassphrase" name="wpa_passphrase" value="<?php echo htmlspecialchars($arrConfig['wpa_passphrase'], ENT_QUOTES); ?>" />
              </div>
            </div>
          </div>
          <div class="tab-pane fade" id="logoutput">
          <h4 class="mt-3"><?php echo _("Logfile output"); ?></h4>
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
          <h4 class="mt-3"><?php echo _("Advanced settings"); ?></h4>
            <div class="row">
              <div class="col-md-6 mb-2">
                <div class="checkbox">
                  <?php
                  $checkedWifiAPEnabled = '';
                  if ($arrHostapdConf['WifiAPEnable'] == 1) {
                      $checkedWifiAPEnabled = ' checked="checked"';
                  }
                  ?>
		  <input id="chxwificlientap" name="wifiAPEnable" type="checkbox" data-onstyle="secondary" data-toggle="toggle" data-on="<?php echo _("Enabled"); ?>" data-off="<?php echo _("Disabled"); ?>" data-width="110" data-height="40" value="1"<?php echo $checkedWifiAPEnabled; ?> />
                  <label class="form-check-label ml-3" for="chxwificlientap"><?php echo _("WiFi client AP mode"); ?></label>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-2">
                <div class="checkbox">
                <?php
                $checkedLogEnabled = '';
                if ($arrHostapdConf['LogEnable'] == 1) {
                    $checkedLogEnabled = ' checked="checked"';
                }
                ?>
		<input id="chxlogenable" name="logEnable" type="checkbox" data-onstyle="secondary" data-toggle="toggle" data-on="<?php echo _("Enabled"); ?>" data-off="<?php echo _("Disabled"); ?>" data-width="110" data-height="40" value="1"<?php echo $checkedLogEnabled; ?> />
                <label class="form-check-label ml-3" for="chxlogenable"><?php echo _("Logfile output"); ?></label>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-2">
                <div class="checkbox">
                <?php
                $checkedHiddenSSID = '';
                if ($arrConfig['ignore_broadcast_ssid'] == 1 || $arrConfig['ignore_broadcast_ssid'] == 2) {
                    $checkedHiddenSSID = ' checked="checked"';
                }
                ?>
		  <input id="chxhiddenssid" name="hiddenSSID" type="checkbox" data-onstyle="secondary" data-toggle="toggle" data-on="<?php echo _("Enabled"); ?>" data-off="<?php echo _("Disabled"); ?>" data-width="110" data-height="40" value="1"<?php echo $checkedHiddenSSID; ?> />
                  <label class="form-check-label ml-3" for="chxhiddenssid"><?php echo _("Hide SSID in broadcast"); ?></label>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="max_num_sta"><?php echo _("Maximum number of clients") ?></label>
                <input type="text" id="max_num_sta" class="form-control" name="max_num_sta" placeholder="2007" value="<?php echo $arrConfig["max_num_sta"] ?>" aria-describedby="max_num_sta_help">
                <span id="max_num_sta_help" class="help-block"><?php echo _("Configures the max_num_sta option of hostapd. The default and maximum is 2007. If empty or 0, the default applies.") ?></span>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
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
          </div><!-- /.card-body -->
        </div><!-- /.card -->
        <?php if (!RASPI_MONITOR_ENABLED) : ?>
            <input type="submit" class="btn btn-outline btn-primary" name="SaveHostAPDSettings" value="<?php echo _("Save settings"); ?>" />
            <?php
            if ($hostapdstatus[0] == 0) {
                echo '<input type="submit" class="btn btn-success" name="StartHotspot" value="' . _("Start hotspot") . '"/>' , PHP_EOL;
            } else {
                echo '<input type="submit" class="btn btn-warning" name="StopHotspot" value="' . _("Stop hotspot") . '"/>' , PHP_EOL;
            };
        endif ?>
      </form>
    </div></div><!-- /.card -->
  <div class="card-footer"> <?php echo _("Information provided by hostapd"); ?></div>
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->
