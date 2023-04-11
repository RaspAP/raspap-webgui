#!/bin/bash
touch /tmp/openvpn.log
journalctl |grep -m 200 openvpn | sudo tee /tmp/openvpn.log
