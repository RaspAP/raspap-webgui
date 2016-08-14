function CheckPSK(psk, id) {
  if(psk.value.length < 8 || psk.value.length > 63) { 
    psk.style.background='#FFD0D0';
    document.getElementById(id).disabled = true;
  } else {
    psk.style.background='#D0FFD0';
    document.getElementById(id).disabled = false;
  }
}
