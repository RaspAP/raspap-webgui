#!/bin/bash
#
# RaspAP OS Subitem Generator for RPi Imager 2.x
# Author: @billz <billzimmerman@gmail.com>
# Author URI: https://github.com/billz
# Project URI: https://github.com/RaspAP/
# License: GNU General Public License v3.0
# License URI: https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE

# Exit on error
set -o errexit

# Exit on error inside functions
set -o errtrace

# Set defaults
NAME="RaspAP"
WEBSITE="https://raspap.com/"
ICON="https://raspap.com/assets/images/raspAP-logo.svg"
DESCRIPTION="The easiest, full-featured wireless router for Debian-based devices."
REPO="RaspAP/raspap-webgui"

# Fetch latest release from GitHub (adapted from RaspAP installer)
_get_release() {
    local response
    local host="api.github.com"

    echo "Fetching latest release from GitHub..." >&2
    response=$(curl -s "https://$host/repos/$REPO/releases/latest")

    if echo "$response" | grep -q 'API rate limit exceeded'; then
        echo "Error: GitHub API rate limit exceeded. Try again later or use a GitHub token." >&2
        exit 1
    fi

    VERSION=$(echo "$response" | grep -o '"tag_name": *"[^"]*"' | sed 's/"tag_name": *"\(.*\)"/\1/')

    if [ -z "$VERSION" ]; then
        echo "Error: Failed to fetch latest release. Check network connectivity." >&2
        exit 1
    fi

    local published_at=$(echo "$response" | grep -o '"published_at": *"[^"]*"' | sed 's/"published_at": *"\(.*\)"/\1/')
    RELEASE_DATE=$(echo "$published_at" | cut -d'T' -f1)

    echo "Found release: $VERSION (published: $RELEASE_DATE)" >&2
}

# Fetch latest release info (if not provided via environment variables)
if [ -z "$VERSION" ] || [ -z "$RELEASE_DATE" ]; then
    _get_release
else
    echo "Using VERSION=$VERSION and RELEASE_DATE=$RELEASE_DATE from environment" >&2
fi

BASE_URL="https://github.com/RaspAP/raspap-webgui/releases/download/${VERSION}"

_get_size() {
    if [ -f "$1" ]; then
        if [[ "$OSTYPE" == "darwin"* ]]; then
            stat -f %z "$1" 2>/dev/null
        else
            stat -c %s "$1" 2>/dev/null
        fi
    else
        echo "0"
    fi
}

_get_sha256() {
    if [ -f "$1" ]; then
        if command -v sha256sum &> /dev/null; then
            sha256sum "$1" | awk '{print $1}'
        else
            shasum -a 256 "$1" | awk '{print $1}'
        fi
    else
        echo ""
    fi
}

# Process armhf (32-bit)
ARMHF_ZIP="raspap-trixie-armhf-lite-${VERSION}.img.zip"

if [ -f "$ARMHF_ZIP" ]; then
    ARMHF_IMG=$(ls *-raspap-trixie-armhf-lite-${VERSION}.img 2>/dev/null | head -1)

    if [ -z "$ARMHF_IMG" ]; then
        echo "Extracting $ARMHF_ZIP..."
        unzip -o "$ARMHF_ZIP"
        ARMHF_IMG=$(ls *-raspap-trixie-armhf-lite-${VERSION}.img 2>/dev/null | head -1)
    fi
else
    ARMHF_IMG=""
fi

ARMHF_EXTRACT_SIZE=$(_get_size "$ARMHF_IMG")
ARMHF_EXTRACT_SHA=$(_get_sha256 "$ARMHF_IMG")
ARMHF_DOWNLOAD_SIZE=$(_get_size "$ARMHF_ZIP")
ARMHF_DOWNLOAD_SHA=$(_get_sha256 "$ARMHF_ZIP")

# Process arm64 (64-bit)
ARM64_ZIP="raspap-trixie-arm64-lite-${VERSION}.img.zip"

if [ -f "$ARM64_ZIP" ]; then
    ARM64_IMG=$(ls *-raspap-trixie-arm64-lite-${VERSION}.img 2>/dev/null | head -1)

    if [ -z "$ARM64_IMG" ]; then
        echo "Extracting $ARM64_ZIP..."
        unzip -o "$ARM64_ZIP"
        ARM64_IMG=$(ls *-raspap-trixie-arm64-lite-${VERSION}.img 2>/dev/null | head -1)
    fi
else
    ARM64_IMG=""
fi

ARM64_EXTRACT_SIZE=$(_get_size "$ARM64_IMG")
ARM64_EXTRACT_SHA=$(_get_sha256 "$ARM64_IMG")
ARM64_DOWNLOAD_SIZE=$(_get_size "$ARM64_ZIP")
ARM64_DOWNLOAD_SHA=$(_get_sha256 "$ARM64_ZIP")

# Generate JSON
cat > os-sublist-raspap.json << EOF
{
  "name": "${NAME}",
  "description": "${DESCRIPTION}",
  "icon": "${ICON}",
  "random": false,
  "subitems": [
    {
      "name": "${NAME} 32-bit (armhf)",
      "description": "${DESCRIPTION}",
      "icon": "${ICON}",
      "url": "${BASE_URL}/${ARMHF_ZIP}",
      "extract_size": ${ARMHF_EXTRACT_SIZE},
      "extract_sha256": "${ARMHF_EXTRACT_SHA}",
      "image_download_size": ${ARMHF_DOWNLOAD_SIZE},
      "image_download_sha256": "${ARMHF_DOWNLOAD_SHA}",
      "release_date": "${RELEASE_DATE}",
      "init_format": "systemd",
      "devices": [
        "pi5-32bit",
        "pi4-32bit",
        "pi3-32bit",
        "pi2-32bit"
      ],
      "capabilities": []
    },
    {
      "name": "${NAME} 64-bit (arm64)",
      "description": "${DESCRIPTION}",
      "icon": "${ICON}",
      "url": "${BASE_URL}/${ARM64_ZIP}",
      "extract_size": ${ARM64_EXTRACT_SIZE},
      "extract_sha256": "${ARM64_EXTRACT_SHA}",
      "image_download_size": ${ARM64_DOWNLOAD_SIZE},
      "image_download_sha256": "${ARM64_DOWNLOAD_SHA}",
      "release_date": "${RELEASE_DATE}",
      "init_format": "systemd",
      "devices": [
        "pi5-64bit",
        "pi4-64bit",
        "pi3-64bit"
      ],
      "capabilities": []
    }
  ]
}
EOF

echo "Generated os-sublist-raspap.json"
cat os-sublist-raspap.json

