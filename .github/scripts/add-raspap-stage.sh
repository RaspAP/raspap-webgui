#!/bin/bash
set -e

mkdir -p stage-raspap/package-raspap

PRERUN_COMMANDS=""
INSTALLER_COMMANDS=""

if [ -n "$INSIDERS_USER" ] && [ -n "$INSIDERS_TOKEN" ]; then
  echo ">>> Configuring for private Insiders build."

  PRERUN_COMMANDS=$(
    cat <<-EOF
echo "${INSIDERS_TOKEN}" > "\${ROOTFS_DIR}/etc/insiders_token"
chmod 600 "\${ROOTFS_DIR}/etc/insiders_token"
EOF
  )

  INSTALLER_COMMANDS=$(
    cat <<-EOF
INSIDERS_TOKEN_VALUE=\$(cat /etc/insiders_token)
curl -sL https://install.raspap.com | bash -s -- \\
  --yes --insiders --openvpn 1 --restapi 1 --adblock 1 --wireguard 1 --tcp-bbr 1 --check 0 \\
  --name "${INSIDERS_USER}" --token "\$INSIDERS_TOKEN_VALUE"
rm -f /etc/insiders_token
EOF
  )
else
  echo ">>> Configuring for public build."

  INSTALLER_COMMANDS=$(
    cat <<-EOF
curl -sL https://install.raspap.com | bash -s -- --yes --openvpn 1 --restapi 1 --adblock 1 --wireguard 1 --tcp-bbr 1 --check 0
EOF
  )
fi

cat >stage-raspap/prerun.sh <<-EOF
#!/bin/bash -e
if [ ! -d "\${ROOTFS_DIR}" ]; then
  copy_previous
fi
${PRERUN_COMMANDS}
EOF

cat >stage-raspap/package-raspap/00-run-chroot.sh <<-EOF
#!/bin/bash -e
apt-get update -y && apt-get install -y curl dhcpcd5 iptables procps

${INSTALLER_COMMANDS}

# Set Wi-Fi country to prevent RF kill
raspi-config nonint do_wifi_country "US"

# Fetch RaspAP version and set MOTD
RASPAP_VERSION=\$(curl -sL https://install.raspap.com | bash -s -- --version)
echo "\$RASPAP_VERSION" | tee /etc/motd
EOF

chmod +x stage-raspap/prerun.sh
chmod +x stage-raspap/package-raspap/00-run-chroot.sh

echo "Build configuration complete."
