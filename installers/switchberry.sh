#!/usr/bin/env bash
# Install the RaspAP + Switchberry management UI without changing networking.

set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly SOURCE_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
readonly WEBROOT="${1:-/var/www/html}"
readonly RASPAP_DIR="/etc/raspap"
readonly SUDOERS_FILE="/etc/sudoers.d/090_raspap"

case "$WEBROOT" in
    /var/www/*|/srv/www/*) ;;
    *)
        echo "Refusing unsafe web root '$WEBROOT'; use a directory below /var/www or /srv/www." >&2
        exit 1
        ;;
esac

if [[ ! -f /etc/startup-dpll.json ]] || [[ ! -f /usr/local/sbin/apply_timing.py ]]; then
    echo "Switchberry software markers were not found; refusing hardware-specific installation." >&2
    exit 1
fi

if [[ ! -f "$SOURCE_ROOT/index.php" ]] || [[ ! -x "$SOURCE_ROOT/config/switchberry/raspap-switchberryctl" ]]; then
    echo "Run this script from a complete Switchberry-RaspAP source tree." >&2
    exit 1
fi

echo "Installing RaspAP web dependencies without changing NetworkManager, wlan0, eth0, hostapd, or dnsmasq..."
sudo apt-get update
sudo env DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    lighttpd php8.4-fpm iw rsync vnstat qrencode jq device-tree-compiler \
    ethtool linuxptp

echo "Installing RaspAP application at $WEBROOT..."
sudo install -d -o root -g root -m 0755 "$WEBROOT"
sudo rsync -a \
    --exclude='.git/' \
    --exclude='node_modules/' \
    --exclude='tmp/' \
    --exclude='__pycache__/' \
    --exclude='includes/config.php' \
    "$SOURCE_ROOT/" "$WEBROOT/"
if [[ ! -f "$WEBROOT/includes/config.php" ]]; then
    sudo install -o root -g root -m 0644 "$SOURCE_ROOT/config/config.php" "$WEBROOT/includes/config.php"
fi
sudo chown -R root:root "$WEBROOT"
sudo find "$WEBROOT" -type d -exec chmod 0755 {} +
sudo find "$WEBROOT" -type f -exec chmod u=rw,go=r {} +
sudo chmod 0755 "$WEBROOT/config/switchberry/raspap-switchberryctl" "$WEBROOT/installers/"*.sh

echo "Creating RaspAP runtime directories..."
sudo install -d -o www-data -g www-data -m 0750 \
    "$RASPAP_DIR" \
    "$RASPAP_DIR/backups" \
    "$RASPAP_DIR/networking"
sudo install -d -o root -g root -m 0750 \
    "$RASPAP_DIR/lighttpd" \
    "$RASPAP_DIR/plugins" \
    "$RASPAP_DIR/system"
sudo install -o www-data -g www-data -m 0640 \
    "$WEBROOT/config/defaults.json" "$RASPAP_DIR/networking/defaults.json"
sudo install -o root -g root -m 0750 \
    "$WEBROOT/installers/configport.sh" "$RASPAP_DIR/lighttpd/configport.sh"
sudo install -o root -g root -m 0750 \
    "$WEBROOT/installers/plugin_helper.sh" "$RASPAP_DIR/plugins/plugin_helper.sh"
sudo install -o root -g root -m 0750 \
    "$WEBROOT/installers/debuglog.sh" "$RASPAP_DIR/system/debuglog.sh"
sudo install -o root -g root -m 0750 \
    "$WEBROOT/installers/raspbian.sh" "$RASPAP_DIR/system/raspbian.sh"

echo "Installing the privileged Switchberry controller and audited sudo rules..."
sudo install -o root -g root -m 0755 \
    "$WEBROOT/config/switchberry/raspap-switchberryctl" \
    /usr/local/sbin/raspap-switchberryctl

echo "Installing the Switchberry V6 boundary-clock plane and service orchestration..."
sudo "$WEBROOT/installers/switchberry-kernel.sh"
overlay_temp=$(mktemp /tmp/switchberrybc-v6.XXXXXX.dtbo)
dtc -@ -I dts -O dtb \
    -o "$overlay_temp" \
    "$WEBROOT/config/switchberry/overlays/switchberrybc-v6-overlay.dts"
sudo install -o root -g root -m 0644 "$overlay_temp" /boot/firmware/overlays/switchberrybc-v6.dtbo
rm -f "$overlay_temp"
sudo install -o root -g root -m 0644 \
    "$WEBROOT/config/switchberry/systemd/ptp4l-switchberry-bc.service" \
    "$WEBROOT/config/switchberry/systemd/switchberry-bc-phc2sys.service" \
    /etc/systemd/system/
for dropin in "$WEBROOT"/config/switchberry/systemd/*.service.d; do
    sudo install -d -o root -g root -m 0755 "/etc/systemd/system/$(basename "$dropin")"
    sudo install -o root -g root -m 0644 "$dropin/raspap-switchberry.conf" \
        "/etc/systemd/system/$(basename "$dropin")/raspap-switchberry.conf"
done
sudo systemctl daemon-reload
sudo visudo -cf "$WEBROOT/installers/raspap.sudoers"
sudo install -o root -g root -m 0440 "$WEBROOT/installers/raspap.sudoers" "$SUDOERS_FILE"

echo "Configuring lighttpd and PHP-FPM..."
if command -v lighty-enable-mod >/dev/null 2>&1; then
    sudo lighty-enable-mod fastcgi-php-fpm || true
elif command -v lighttpd-enable-mod >/dev/null 2>&1; then
    sudo lighttpd-enable-mod fastcgi-php-fpm || true
else
    echo "Unable to find the lighttpd module enable command." >&2
    exit 1
fi
[[ -L /etc/lighttpd/conf-enabled/15-fastcgi-php-fpm.conf ]] || {
    echo "The lighttpd PHP-FPM module was not enabled." >&2
    exit 1
}

document_root=$(awk -F= '/^[[:space:]]*server\.document-root/ {gsub(/[ \"\t]/, "", $2); print $2; exit}' /etc/lighttpd/lighttpd.conf)
relative_root="${WEBROOT#${document_root}}"
relative_root="${relative_root%/}"
router_temp=$(mktemp /tmp/50-raspap-router.XXXXXX)
awk -v root="$relative_root" '{gsub("/REPLACE_ME", root); print}' \
    "$WEBROOT/config/50-raspap-router.conf" > "$router_temp"
sudo install -o root -g root -m 0644 "$router_temp" /etc/lighttpd/conf-available/50-raspap-router.conf
sudo ln -sfn /etc/lighttpd/conf-available/50-raspap-router.conf /etc/lighttpd/conf-enabled/50-raspap-router.conf
rm -f "$router_temp"

sudo systemctl enable --now php8.4-fpm.service lighttpd.service
sudo systemctl restart php8.4-fpm.service lighttpd.service

echo "Verifying that the existing management connection was not changed..."
if systemctl is-active --quiet NetworkManager.service; then
    echo "  NetworkManager remains active."
fi
if ip -4 -brief address show wlan0 2>/dev/null | grep -q 'UP'; then
    echo "  wlan0 remains up."
fi

echo "Switchberry-aware RaspAP installation complete."
echo "Hotspot packages and services were intentionally not installed or started."
