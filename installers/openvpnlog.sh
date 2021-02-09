#!/bin/bash
touch /tmp/openvpn.log
grep -m 50 openvpn /var/log/syslog | sudo tee /tmp/openvpn.log
