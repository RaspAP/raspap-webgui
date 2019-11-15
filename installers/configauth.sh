#!/bin/bash
# Updates openvpn client.conf with auth credentials

echo "Enabling auth-user-pass in OpenVPN client.conf..."
line='auth-user-pass'
file='/tmp/ovpnclient.ovpn'

if grep -q "$line" $file; then
    echo "Updating line: $line"
    sudo sed -i "s/$line/$line login.conf/g" $file
else
    echo "Adding line: $line"
    sudo sed -i "$ a $line login.conf" $file
fi

