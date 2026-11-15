#!/bin/bash
#
# RaspAP web interface access
# License: GNU General Public License v3.0
# License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE
#
# Allows access to the web interface only from a list of client networks, so
# that it is not reachable from the WAN. lighttpd answers requests from other
# addresses with "403 Forbidden". Loopback addresses always have access. The
# rules are kept in 51-raspap-access.conf of lighttpd. If lighttpd rejects the
# new configuration, the previous one stays. lighttpd uses the new settings
# after a restart. The web interface validates the networks as well; this
# script makes sure that only addresses get into the lighttpd configuration.
# Installed as /usr/local/sbin/raspap-webaccess.sh.
#
# Usage: raspap-webaccess.sh allow <network> [<network> ...]
#        raspap-webaccess.sh clear
#        A network is an IPv4 or IPv6 address with an optional prefix length.

set -o nounset

readonly lighttpd_conf="/etc/lighttpd/lighttpd.conf"
readonly access_conf="/etc/lighttpd/conf-available/51-raspap-access.conf"
readonly access_link="/etc/lighttpd/conf-enabled/51-raspap-access.conf"
readonly loopback=("127.0.0.0/8" "::1")
readonly max_networks=16
# copy of the previous configuration while a change is checked
backup=""

usage() {
    echo "Usage: $0 allow <network> [<network> ...] | clear" >&2
    exit 2
}

# Returns 0 for an IPv4 or IPv6 address with an optional prefix length
valid_network() {
    local addr="${1%%/*}" max octet
    if [[ "$addr" =~ ^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$ ]]; then
        for octet in "${BASH_REMATCH[@]:1}"; do
            (( 10#$octet <= 255 )) || return 1
        done
        max=32
    elif [[ "$addr" =~ ^[0-9A-Fa-f:.]*:[0-9A-Fa-f:.]*$ ]]; then
        max=128
    else
        return 1
    fi
    if [[ "$1" == */* ]]; then
        [[ "${1#*/}" =~ ^[0-9]{1,3}$ ]] && (( 10#${1#*/} <= max )) || return 1
    fi
    return 0
}

# Writes the lighttpd rules: requests from outside of all networks are denied
write_conf() {
    local network indent=""
    echo "# Managed by RaspAP. Changes on System > Advanced replace this file."
    echo "# Denies access to the web interface from outside of the allowed networks."
    for network in "$@"; do
        echo "$indent\$HTTP[\"remoteip\"] != \"$network\" {"
        indent="$indent    "
    done
    echo "${indent}url.access-deny = ( \"\" )"
    for network in "$@"; do
        indent="${indent%    }"
        echo "$indent}"
    done
}

# Puts back the previous configuration after a failed change
restore() {
    if [ -n "$backup" ]; then
        mv -f "$backup" "$access_conf"
    else
        rm -f "$access_link" "$access_conf"
    fi
}

allow() {
    local network tmp
    if [ "$#" -eq 0 ] || [ "$#" -gt "$max_networks" ]; then
        echo "Enter 1 to $max_networks networks" >&2
        exit 1
    fi
    for network in "$@"; do
        if ! valid_network "$network"; then
            echo "Invalid network: $network" >&2
            exit 1
        fi
    done

    tmp=$(mktemp "$access_conf.XXXXXX") || exit 1
    if ! write_conf "${loopback[@]}" "$@" > "$tmp" || ! chmod 644 "$tmp"; then
        rm -f "$tmp"
        exit 1
    fi
    if [ -f "$access_conf" ]; then
        if ! backup=$(mktemp "$access_conf.XXXXXX") || ! cp -p "$access_conf" "$backup"; then
            rm -f "$tmp" "$backup"
            exit 1
        fi
    fi
    if ! mv -f "$tmp" "$access_conf" || ! ln -sfn "$access_conf" "$access_link"; then
        rm -f "$tmp"
        restore
        echo "Unable to write $access_conf" >&2
        exit 1
    fi
    if ! lighttpd -tt -f "$lighttpd_conf" > /dev/null 2>&1; then
        restore
        echo "lighttpd rejected the new configuration. The previous settings are kept." >&2
        exit 1
    fi
    [ -z "$backup" ] || rm -f "$backup"
    echo "Allowing access to the web interface only from $*"
    echo "Restart lighttpd for new settings to take effect"
}

clear_networks() {
    rm -f "$access_link" "$access_conf" || exit 1
    echo "Allowing access to the web interface from all networks"
    echo "Restart lighttpd for new settings to take effect"
}

case "${1:-}" in
    allow)
        shift
        allow "$@"
        ;;
    clear)
        [ "$#" -eq 1 ] || usage
        clear_networks
        ;;
    *)
        usage
        ;;
esac
