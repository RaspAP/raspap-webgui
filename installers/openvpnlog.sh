#!/bin/bash
touch /tmp/openvpn.log
grep -m 100 openvpn /var/log/syslog | sudo tee /tmp/openvpn.log
