<?

function GetDistString($input,$string,$offset,$separator) {
	$string = substr($input,strpos($input,$string)+$offset,strpos(substr($input,strpos($input,$string)+$offset),$separator));
	return $string;
}

function ParseConfig($arrConfig) {
	$config = array();
	foreach($arrConfig as $line) {
		if($line[0] != "#") {
			$arrLine = explode("=",$line);
			$config[$arrLine[0]] = $arrLine[1];
		}
	}
	return $config;
}

function ConvertToChannel($freq) {
	$base = 2412;
	$channel = 1;
	for($x = 0; $x < 13; $x++) {
		if($freq != $base) {
			$base = $base + 5;
			$channel++;
		} else {
			return $channel;
		}
	}
	return "Invalid Channel";
}

function ConvertToSecurity($security) {
	switch($security) {
		case "[WPA2-PSK-CCMP][ESS]":
			return "WPA2-PSK (AES)";
		break;
		case "[WPA2-PSK-TKIP][ESS]":
			return "WPA2-PSK (TKIP)";
		break;
		case "[WPA-PSK-TKIP+CCMP][WPS][ESS]":
			return "WPA-PSK (TKIP/AES) with WPS";
		break;
		case "[WPA-PSK-TKIP+CCMP][WPA2-PSK-TKIP+CCMP][ESS]":
			return "WPA/WPA2-PSK (TKIP/AES)";
		break;
		case "[WPA-PSK-TKIP][ESS]":
			return "WPA-PSK (TKIP)";
		break;
		case "[WEP][ESS]":
			return "WEP";
		break;
	}
}

/*
1*	2412	Yes	Yes	YesD
2	2417	Yes	Yes	YesD
3	2422	Yes	Yes	YesD
4	2427	Yes	Yes	YesD
5*	2432	Yes	Yes	Yes
6	2437	Yes	Yes	Yes
7	2442	Yes	Yes	Yes
8	2447	Yes	Yes	Yes
9*	2452	Yes	Yes	Yes
10	2457	Yes	Yes	Yes
11	2462	Yes	Yes	Yes
12	2467	NoB	Yes	Yes
13*	2472	NoB	Yes	Yes
*/

?>

