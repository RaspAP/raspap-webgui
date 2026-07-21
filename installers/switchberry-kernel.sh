#!/usr/bin/env bash
# Select an installed Switchberry PTP-enabled kernel without replacing it on
# future Raspberry Pi kernel package upgrades.

set -euo pipefail

readonly BOOT_DIR="/boot/firmware"
readonly BOOT_CONFIG="$BOOT_DIR/config.txt"
readonly DESTINATION="$BOOT_DIR/kernel8-switchberry.img"

if [[ ${EUID} -ne 0 ]]; then
    echo "switchberry-kernel.sh must run as root." >&2
    exit 1
fi

if [[ ! -f "$BOOT_CONFIG" ]]; then
    echo "Raspberry Pi boot configuration is missing at $BOOT_CONFIG." >&2
    exit 1
fi

kernel_release=$(
    find /lib/modules -mindepth 1 -maxdepth 1 -type d \
        -name '*-DSA-SwitchberryV6+' -printf '%f\n' | sort -V | tail -n 1
)
if [[ -z "$kernel_release" ]]; then
    echo "No installed *-DSA-SwitchberryV6+ kernel modules were found." >&2
    exit 1
fi

probe=$(mktemp /tmp/switchberry-kernel-probe.XXXXXX)
config_temp=$(mktemp /tmp/switchberry-boot-config.XXXXXX)
trap 'rm -f "$probe" "$config_temp"' EXIT

kernel_image_has_release() {
    local candidate=$1
    [[ -f "$candidate" ]] || return 1
    if gzip -t "$candidate" >/dev/null 2>&1; then
        gzip -cd "$candidate" > "$probe"
    else
        cp -- "$candidate" "$probe"
    fi
    grep -a -F -m 1 "Linux version $kernel_release " "$probe" >/dev/null
}

build_dir=$(readlink -f "/lib/modules/$kernel_release/build" 2>/dev/null || true)
candidates=(
    "$DESTINATION"
    "$build_dir/arch/arm64/boot/Image.gz"
    "$build_dir/arch/arm64/boot/Image"
    "/home/pi/kernel/linux/arch/arm64/boot/Image.gz"
    "/home/pi/kernel/linux/arch/arm64/boot/Image"
    "$BOOT_DIR/kernel8-backup.img"
)

source_image=""
for candidate in "${candidates[@]}"; do
    if kernel_image_has_release "$candidate"; then
        source_image=$candidate
        break
    fi
done
if [[ -z "$source_image" ]]; then
    echo "Kernel modules $kernel_release are installed, but no matching boot image was found." >&2
    exit 1
fi

if [[ "$source_image" != "$DESTINATION" ]]; then
    install -o root -g root -m 0644 "$source_image" "$DESTINATION"
fi
kernel_image_has_release "$DESTINATION" || {
    echo "Installed Switchberry kernel image failed release verification." >&2
    exit 1
}

awk '
    !/^[[:space:]]*kernel[[:space:]]*=/ { print }
    END { print "kernel=kernel8-switchberry.img" }
' "$BOOT_CONFIG" > "$config_temp"

if ! cmp -s "$BOOT_CONFIG" "$config_temp"; then
    backup_dir=/var/lib/raspap/switchberry-backups
    install -d -o root -g root -m 0750 "$backup_dir"
    cp -a "$BOOT_CONFIG" "$backup_dir/boot-config.kernel.$(date +%Y%m%dT%H%M%S).txt"
    install -o root -g root -m 0644 "$config_temp" "$BOOT_CONFIG"
fi

echo "Selected Switchberry kernel $kernel_release via kernel8-switchberry.img."
