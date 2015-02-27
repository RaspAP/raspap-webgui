function WiFiDown() {
        var down = confirm("Take down wlan0 ?");
        if(down) {
        } else {
                alert("Action cancelled");
        }
}

function UpdateNetworks() {
	var existing = document.getElementById("networkbox").getElementsByTagName('div').length;
	document.getElementById("Networks").value = existing;
}

function AddNetwork() {
//	existing = document.getElementById("networkbox").getElementsByTagName('div').length;
//	existing++;
	Networks++
	var Networks = document.getElementById('Networks').value;
        document.getElementById('networkbox').innerHTML += '<div id="Networkbox'+Networks+'" class="Networkboxes"><div class="row"><div class="col-lg-12"><h4>Network '+Networks+'</h4> \
		<div class="row"><div class="form-group col-md-4"><label for="code">SSID</label><input type="text" class="form-control" name="ssid'+Networks+'" onkeyup="CheckSSID(this)"></div></div> \
		<div class="row"><div class="form-group col-md-4"><label for="code">PSK</label><input type="password" class="form-control" name="psk'+Networks+'" onkeyup="CheckPSK(this)"></div></div> \
		<div class="row"><div class="form-group col-md-4"><input type="button" class="btn btn-outline btn-primary" value="Cancel" onClick="DeleteNetwork('+Networks+')" /></div></div>';
	Networks++;
	document.getElementById('Networks').value=Networks;

}

function AddScanned(network) {

	existing = document.getElementById("networkbox").getElementsByTagName('div').length;
    var Networks = document.getElementById('Networks').value;
	//if(existing != 0) {
	    Networks++;
	//}

    document.getElementById('Networks').value=Networks;
    document.getElementById('networkbox').innerHTML += '<div id="Networkbox'+Networks+'" class="Networkboxes"><div class="col-lg-12"><h4>Network '+Networks+'</h4> \
	<div class="row"><div class="form-group col-md-4"><label for="code">SSID</</label><input type="text" class="form-control" name="ssid'+Networks+'" id="ssid'+Networks+'" onkeyup="CheckSSID(this)"></div></div> \
	<div class="row"><div class="form-group col-md-4"><label for="code">PSK</label><input type="password" class="form-control" name="psk'+Networks+'" onkeyup="CheckPSK(this)"></div></div> \
	<div class="row"><div class="form-group col-md-4"><input type="button" class="btn btn-outline btn-primary" value="Cancel" onClick="DeleteNetwork('+Networks+')" /></div></div>';
	document.getElementById('ssid'+Networks).value = network;
	if(existing == 0) {
		Networks++
		document.getElementById('Networks').value = Networks;
	}
}

function CheckSSID(ssid) {
	if(ssid.value.length>31) {
		ssid.style.background='#FFD0D0';
		document.getElementById('Save').disabled = true;
	} else {
		ssid.style.background='#D0FFD0'
		document.getElementById('Save').disabled = false;
	}
}

function CheckPSK(psk) {
	if(psk.value.length < 8) { 
		psk.style.background='#FFD0D0';
		document.getElementById('Save').disabled = true;
	} else {
		psk.style.background='#D0FFD0';
		document.getElementById('Save').disabled = false;
	}
}

function DeleteNetwork(network) {
	element = document.getElementById('Networkbox'+network);
	element.parentNode.removeChild(element);
        var Networks = document.getElementById('Networks').value;
	Networks--
	document.getElementById('Networks').value = Networks;
}
