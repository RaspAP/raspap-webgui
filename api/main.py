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


tags_metadata = [
]
app = FastAPI(
    title="API for RaspAP",
    openapi_tags=tags_metadata,
    version="0.0.1",
    license_info={
    "name": "Apache 2.0",
    "url": "https://www.apache.org/licenses/LICENSE-2.0.html",
    }
)

@app.get("/system", tags=["system"])
async def get_system(api_key: APIKey = Depends(auth.get_api_key)):
    return{
'hostname': system.hostname(),
'uptime': system.uptime(),
'systime': system.systime(),
'usedMemory': system.usedMemory(),
'usedDisk': system.usedDisk(),
'processorCount': system.processorCount(),
'LoadAvg1Min': system.LoadAvg1Min(),
'systemLoadPercentage': system.systemLoadPercentage(),
'systemTemperature': system.systemTemperature(),
'hostapdStatus': system.hostapdStatus(),
'operatingSystem': system.operatingSystem(),
'kernelVersion': system.kernelVersion(),
'rpiRevision': system.rpiRevision(),
'raspapVersion': system.raspapVersion()
}

@app.get("/ap", tags=["accesspoint/hotspot"])
async def get_ap(api_key: APIKey = Depends(auth.get_api_key)):
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
'frequency_band': ap.frequency_band(),
'wpa_passphrase': ap.wpa_passphrase(),
'interface': ap.interface(),
'wpa': ap.wpa(),
'wpa_pairwise': ap.wpa_pairwise(),
'country_code': ap.country_code(),
'ignore_broadcast_ssid': ap.ignore_broadcast_ssid()
}

@app.get("/clients", tags=["Clients"]) 
async def get_clients(api_key: APIKey = Depends(auth.get_api_key)):
    return{
'active_clients_amount': client.get_active_clients_amount(),
'active_wireless_clients_amount': client.get_active_wireless_clients_amount(),
'active_ethernet_clients_amount': client.get_active_ethernet_clients_amount(),
'active_clients': json.loads(client.get_active_clients())
}

@app.get("/clients/{wireless_interface}", tags=["Clients"]) 
async def get_clients(wireless_interface, api_key: APIKey = Depends(auth.get_api_key)):
    return{
'active_clients_amount': client.get_active_clients_amount_by_interface(wireless_interface),
'active_clients': json.loads(client.get_active_clients_by_interface(wireless_interface))
}

@app.get("/dhcp", tags=["DHCP"])
async def get_dhcp(api_key: APIKey = Depends(auth.get_api_key)):
    return{
'range_start': dhcp.range_start(),
'range_end': dhcp.range_end(),
'range_subnet_mask': dhcp.range_subnet_mask(),
'range_lease_time': dhcp.range_lease_time(),
'range_gateway': dhcp.range_gateway(),
'range_nameservers': dhcp.range_nameservers()
}

@app.get("/dns/domains", tags=["DNS"])
async def get_domains(api_key: APIKey = Depends(auth.get_api_key)):
    return{
'domains': json.loads(dns.adblockdomains())
}

@app.get("/dns/hostnames", tags=["DNS"])
async def get_hostnames(api_key: APIKey = Depends(auth.get_api_key)):
    return{
'hostnames': json.loads(dns.adblockhostnames())
}

@app.get("/dns/upstream", tags=["DNS"]) 
async def get_upstream(api_key: APIKey = Depends(auth.get_api_key)):
    return{
'upstream_nameserver': dns.upstream_nameserver()
}

@app.get("/dns/logs", tags=["DNS"]) 
async def get_dnsmasq_logs(api_key: APIKey = Depends(auth.get_api_key)):
    return(dns.dnsmasq_logs())


@app.get("/ddns", tags=["DDNS"]) 
async def get_ddns(api_key: APIKey = Depends(auth.get_api_key)):
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
async def get_firewall(api_key: APIKey = Depends(auth.get_api_key)):
    return json.loads(firewall.firewall_rules())

@app.get("/networking", tags=["Networking"]) 
async def get_networking(api_key: APIKey = Depends(auth.get_api_key)):
    return{
'interfaces': json.loads(networking.interfaces()),
'throughput': json.loads(networking.throughput())
}

@app.get("/openvpn", tags=["OpenVPN"]) 
async def get_openvpn(api_key: APIKey = Depends(auth.get_api_key)):
    return{
'client_configs': openvpn.client_configs(),
'client_config_names': openvpn.client_config_names(),
'client_config_active': openvpn.client_config_active(),
'client_login_names': openvpn.client_login_names(),
'client_login_active': openvpn.client_login_active()
}

@app.get("/openvpn/{config}", tags=["OpenVPN"]) 
async def client_config_list(config, api_key: APIKey = Depends(auth.get_api_key)):
    return{
'client_config': openvpn.client_config_list(config)
}

@app.get("/wireguard", tags=["WireGuard"]) 
async def get_wireguard(api_key: APIKey = Depends(auth.get_api_key)):
    return{
'client_configs': wireguard.configs(),
'client_config_names': wireguard.client_config_names(),
'client_config_active': wireguard.client_config_active()
}

