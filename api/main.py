from fastapi import FastAPI, Depends
from fastapi.security.api_key import APIKey
import auth

import json

import modules.system as system
import modules.ap as ap
import modules.client as client
import modules.dns as dns
import modules.dhcp as dhcp
import modules.ddns as ddns
import modules.firewall as firewall
import modules.networking as networking
import modules.openvpn as openvpn
import modules.wireguard as wireguard
import modules.restart as restart


tags_metadata = [
    {
        "name": "system",
        "description": "All information regarding the underlying system."
    },
    {
        "name": "accesspoint/hostpost",
        "description": "Get and change all information regarding the hotspot"
    }
]
app = FastAPI(
    title="API for Raspap",
    openapi_tags=tags_metadata,
    version="0.0.1",
    license_info={
    "name": "Apache 2.0",
    "url": "https://www.apache.org/licenses/LICENSE-2.0.html",
    }
)

@app.get("/system", tags=["system"])
async def get_system():
    return{
'hostname': system.hostname(),
'uptime': system.uptime(),
'systime': system.systime(),
'usedMemory': system.usedMemory(),
'processorCount': system.processorCount(),
'LoadAvg1Min': system.LoadAvg1Min(),
'systemLoadPercentage': system.systemLoadPercentage(),
'systemTemperature': system.systemTemperature(),
'hostapdStatus': system.hostapdStatus(),
'operatingSystem': system.operatingSystem(),
'kernelVersion': system.kernelVersion(),
'rpiRevision': system.rpiRevision()
}

@app.get("/ap", tags=["accesspoint/hostpost"])
async def get_ap():
    return{
'driver': ap.driver(),
'ctrl_interface': ap.ctrl_interface(),
'ctrl_interface_group': ap.ctrl_interface_group(),
'auth_algs': ap.auth_algs(),
'wpa_key_mgmt': ap.wpa_key_mgmt(),
'beacon_int': ap.beacon_int(),
'ssid': ap.ssid(),
'channel': ap.channel(),
'hw_mode': ap.hw_mode(),
'ieee80211n': ap.ieee80211n(),
'wpa_passphrase': ap.wpa_passphrase(),
'interface': ap.interface(),
'wpa': ap.wpa(),
'wpa_pairwise': ap.wpa_pairwise(),
'country_code': ap.country_code(),
'ignore_broadcast_ssid': ap.ignore_broadcast_ssid()
}

@app.post("/ap", tags=["accesspoint/hostpost"])
async def post_ap(driver=None,
                  ctrl_interface=None,
                  ctrl_interface_group=None,
                  auth_algs=None,
                  wpa_key_mgmt=None,
                  beacon_int=None,
                  ssid=None,
                  channel=None,
                  hw_mode=None,
                  ieee80211n=None,
                  wpa_passphrase=None,
                  interface=None,
                  wpa=None,
                  wpa_pairwise=None,
                  country_code=None,
                  ignore_broadcast_ssid=None,
                  api_key: APIKey = Depends(auth.get_api_key)):
    if driver != None:
        ap.set_driver(driver)
    if ctrl_interface != None:
        ap.set_ctrl_interface(ctrl_interface)
    if ctrl_interface_group !=None:
        ap.set_ctrl_interface_group(ctrl_interface_group)
    if auth_algs != None:
        ap.set_auth_algs(auth_algs)
    if wpa_key_mgmt != None:
        ap.set_wpa_key_mgmt(wpa_key_mgmt)
    if beacon_int != None:
        ap.set_beacon_int(beacon_int)
    if ssid != None:
        ap.set_ssid(ssid)
    if channel != None:
        ap.set_channel(channel)
    if hw_mode != None:
        ap.set_hw_mode(hw_mode)
    if ieee80211n != None:
        ap.set_ieee80211n(ieee80211n)
    if wpa_passphrase != None:
        ap.set_wpa_passphrase(wpa_passphrase)
    if interface != None:
        ap.set_interface(interface)
    if wpa != None:
        ap.set_wpa(wpa)
    if wpa_pairwise != None:
        ap.set_wpa_pairwise(wpa_pairwise)
    if country_code != None:
        ap.set_country_code(country_code)
    if ignore_broadcast_ssid != None:
        ap.set_ignore_broadcast_ssid(ignore_broadcast_ssid)
    

@app.get("/clients/{wireless_interface}", tags=["Clients"])
async def get_clients(wireless_interface):
    return{
'active_clients_amount': client.get_active_clients_amount(wireless_interface),
'active_clients': json.loads(client.get_active_clients(wireless_interface))
}

@app.get("/dhcp", tags=["DHCP"])
async def get_dhcp():
    return{
'range_start': dhcp.range_start(),
'range_end': dhcp.range_end(),
'range_subnet_mask': dhcp.range_subnet_mask(),
'range_lease_time': dhcp.range_lease_time(),
'range_gateway': dhcp.range_gateway(),
'range_nameservers': dhcp.range_nameservers()
}

@app.get("/dns/domains", tags=["DNS"])
async def get_domains():
    return{
'domains': json.loads(dns.adblockdomains())
}
@app.get("/dns/hostnames", tags=["DNS"])
async def get_hostnames():
    return{
'hostnames': json.loads(dns.adblockhostnames())
}

@app.get("/dns/upstream", tags=["DNS"])
async def get_upstream():
    return{
'upstream_nameserver': dns.upstream_nameserver()
}

@app.get("/dns/logs", tags=["DNS"])
async def get_dnsmasq_logs():
    return(dns.dnsmasq_logs())


@app.get("/ddns", tags=["DDNS"])
async def get_ddns():
    return{
'use': ddns.use(),
'method': ddns.method(),
'protocol': ddns.protocol(),
'server': ddns.server(),
'login': ddns.login(),
'password': ddns.password(),
'domain': ddns.domain()
}

@app.get("/firewall", tags=["Firewall"])
async def get_firewall():
    return json.loads(firewall.firewall_rules())

@app.get("/networking", tags=["Networking"])
async def get_networking():
    return{
'interfaces': json.loads(networking.interfaces()),
'throughput': json.loads(networking.throughput())
}

@app.get("/openvpn", tags=["OpenVPN"])
async def get_openvpn():
    return{
'client_configs': openvpn.client_configs(),
'client_config_names': openvpn.client_config_names(),
'client_config_active': openvpn.client_config_active(),
'client_login_names': openvpn.client_login_names(),
'client_login_active': openvpn.client_login_active()
}

@app.get("/openvpn/{config}", tags=["OpenVPN"])
async def client_config_list(config):
    return{
'client_config': openvpn.client_config_list(config)
}

@app.get("/wireguard", tags=["WireGuard"])
async def get_wireguard():
    return{
'client_configs': wireguard.configs(),
'client_config_names': wireguard.client_config_names(),
'client_config_active': wireguard.client_config_active()
}

@app.get("/wireguard/{config}", tags=["WireGuard"])
async def client_config_list(config):
    return{
'client_config': wireguard.client_config_list(config)
}

@app.post("/restart/webgui")
async def restart_webgui():
    restart.webgui()

@app.post("/restart/adblock")
async def restart_adblock():
    restart.adblock()