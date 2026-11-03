#!/bin/bash
#
# RaspAP VPN file helper
#
# Description: Installs, reads and removes the WireGuard and OpenVPN client
# files of the web interface. www-data runs this script with sudo. The new
# content comes on stdin, so there is no temporary file in /tmp. The files
# are owned by root with mode 600, because wg-quick and OpenVPN run as root.
# The script refuses the options that make them run a program, load code,
# or write or send a file as root.
#   wireguard install <name>          stdin to /etc/wireguard/<name>.conf
#   wireguard read <file>             <name>.conf or wg-(server|peer)-(public|private).key
#   wireguard list                    the file names in /etc/wireguard
#   wireguard active                  the config that wg0.conf links to
#   wireguard activate <name>         wg0.conf links to <name>.conf
#   wireguard remove <name>           /etc/wireguard/<name>.conf
#   wireguard genkey wg-server|wg-peer       a new key pair, prints the public key
#   wireguard remove-keys wg-server|wg-peer
#   openvpn install <name> client|login|keypass
#                                     stdin to /etc/openvpn/client/<name>_<kind>.conf,
#                                     then <kind>.conf links to it
#   openvpn read client|login         the active client config or login
#   openvpn activate <name>           client.conf, login.conf and keypass.conf link to <name>
#   openvpn remove <name>             the client, login and keypass files of <name>
# Installed as /usr/local/sbin/raspap-vpn-file.sh.
# License: GNU General Public License v3.0
#
# You are not obligated to bundle the LICENSE file with your RaspAP projects as long
# as you leave these references intact in the header comments of your source files.

set -o nounset
set -o pipefail

readonly max_size=262144
readonly wg_dir="/etc/wireguard"
readonly ovpn_dir="/etc/openvpn/client"

die() {
    echo "raspap-vpn-file: $2" >&2
    exit "$1"
}

usage() {
    die 2 "usage: $0 wireguard install|read|list|active|activate|remove|genkey|remove-keys ..., or openvpn install|read|activate|remove ..."
}

# The name of a WireGuard config, as the web interface makes it
valid_wg_name() {
    [[ "$1" =~ ^[A-Za-z0-9_-]{1,100}$ ]]
}

# The name of an OpenVPN config is the name of the uploaded file
valid_ovpn_name() {
    local re='^[A-Za-z0-9_()][A-Za-z0-9 ._()-]{0,99}$'
    [[ "$1" =~ $re ]]
}

valid_wg_entity() {
    [ "$1" = "wg-server" ] || [ "$1" = "wg-peer" ]
}

# wg-quick runs PreUp, PostUp, PreDown and PostDown as root. Only the keys
# that wg-quick and wg setconf know are allowed, and only the hook commands
# that the web interface adds. Each line is parsed as wg-quick does it: "#"
# starts a comment, the key is the text before the first "=", without case.
check_wireguard() {
    awk '
        function trim(s) { sub(/^[[:space:]]+/, "", s); sub(/[[:space:]]+$/, "", s); return s }
        BEGIN {
            n = split("[interface] [peer] address dns mtu table saveconfig privatekey listenport fwmark publickey presharedkey allowedips endpoint persistentkeepalive", list, " ")
            for (i = 1; i <= n; i++) allowed[list[i]] = 1
            fwd = "^/etc/raspap/networking/wg-forward\\.sh "
            ks = "^/etc/raspap/networking/wg-killswitch\\.sh "
        }
        {
            line = $0; sub(/#.*/, "", line)
            key = line; value = ""
            if (index(line, "=")) { sub(/=.*/, "", key); value = line; sub(/^[^=]*=/, "", value) }
            key = tolower(trim(key)); value = trim(value)
            if (key == "" || (key in allowed)) next
            if (key == "postup" && value ~ (fwd "up %i( (ap|[A-Za-z0-9_.-]+))?$")) next
            if (key == "postup" && value ~ (ks "up %i( --(allow-ntp|allow-dns|allow-portal|portal-auto))*$")) next
            if (key == "postdown" && value ~ (fwd "down %i$")) next
            if (key == "predown" && value ~ (ks "down %i$")) next
            print key; exit 1
        }' "$1"
}

# The same options as sanitizeOpenVPNConfig() in includes/openvpn.php. The
# check removes quotes and backslashes before it splits a line, so a quoted
# option name is found too. A line that the web interface adds to the upload
# (auth-user-pass login.conf) is allowed.
check_openvpn() {
    awk '
        BEGIN {
            n = split("up down route-up route-pre-down ipchange tls-verify auth-user-pass-verify client-connect client-disconnect client-crresponse learn-address iproute script-security plugin engine providers pkcs11-providers config cd chroot tmp-dir log log-append status writepid replay-persist tls-export-cert ifconfig-pool-persist management management-client-user management-client-group", list, " ")
            for (i = 1; i <= n; i++) denied[list[i]] = 1
        }
        /^[[:space:]]*([#;]|$)/ { next }
        {
            raw = $0
            line = raw; gsub(/["\047\\]/, "", line); sub(/^[[:space:]]+/, "", line)
            n = split(tolower(line), t, /[[:space:]]+/)
            if (t[n] == "") n--
            first = 1
            while (n - first + 1 > 2 && t[first] ~ /^(--)?setenv$/ && t[first + 1] ~ /^(--)?opt$/) first += 2
            name = t[first]; sub(/^--/, "", name)
            args = n - first
            if (name == "") next
            if ((name in denied) || name ~ /^management-/) { print name; exit 1 }
            if (name == "auth-user-pass" && args > 0 && raw !~ /^[[:space:]]*auth-user-pass[[:space:]]+login\.conf[[:space:]]*$/) { print name; exit 1 }
            if ((name == "http-proxy" || name == "socks-proxy") && raw ~ /["\047\\]/) { print name; exit 1 }
            if (name == "http-proxy" && args > 2 && t[first + 3] != "auto" && t[first + 3] != "auto-nct") { print name; exit 1 }
            if (name == "socks-proxy" && args > 2) { print name; exit 1 }
        }' "$1"
}

# Writes stdin to the target as root with mode 600. The temporary file is in
# the same directory and starts with ".".
install_file() {
    local target="$1" kind="$2" dir base tmp option
    dir="$(dirname "$target")"
    base="$(basename "$target")"
    [ -d "$dir" ] || die 1 "no directory $dir"
    tmp="$(mktemp "$dir/.$base.XXXXXX")" || die 1 "mktemp failed"
    trap 'rm -f "$tmp"' EXIT
    chmod 600 "$tmp" || die 1 "can not set the mode of $tmp"
    head -c $((max_size + 1)) > "$tmp" || die 1 "can not write $tmp"
    [ "$(stat -c %s "$tmp")" -le "$max_size" ] || die 2 "the file is too large"
    [ "$(tr -d '\000' < "$tmp" | wc -c)" = "$(stat -c %s "$tmp")" ] || die 2 "the file has a NUL byte"
    case "$kind" in
        wireguard|openvpn)
            if ! option="$(check_"$kind" "$tmp")"; then
                die 2 "option not allowed in the $kind configuration: $option"
            fi
            ;;
    esac
    chown root:root "$tmp" || die 1 "can not set the owner of $tmp"
    mv -f "$tmp" "$target" || die 1 "can not write $target"
    trap - EXIT
}

# Makes the link "$1" point to "$2", or replaces it in one step
set_link() {
    local link="$1" target="$2" tmp
    tmp="$(dirname "$link")/.$(basename "$link").$$"
    ln -sfn "$target" "$tmp" && mv -Tf "$tmp" "$link" || { rm -f "$tmp"; die 1 "can not link $link"; }
}

# A regular file in the directory, also through a link in the directory
inside() {
    local dir="$1" path="$2" real
    real="$(realpath -e -- "$path" 2>/dev/null)" || return 1
    [ "$(dirname "$real")" = "$dir" ] && [ -f "$real" ] && [ ! -L "$real" ]
}

wireguard() {
    local action="$1" name="${2:-}" target
    case "$action $#" in
        "install 2")
            valid_wg_name "$name" || usage
            target="$wg_dir/$name.conf"
            # as "cp" did before: an active wg0.conf link stays, its config changes
            if [ -L "$target" ] && inside "$wg_dir" "$target"; then
                target="$(realpath -e -- "$target")"
            fi
            install_file "$target" wireguard
            ;;
        "read 2")
            [[ "$name" =~ ^[A-Za-z0-9_-]{1,100}\.conf$ || "$name" =~ ^wg-(server|peer)-(public|private)\.key$ ]] || usage
            # a missing file gives no output, as for openvpn read
            [ -e "$wg_dir/$name" ] || exit 0
            inside "$wg_dir" "$wg_dir/$name" || die 1 "invalid file $name"
            cat -- "$wg_dir/$name"
            ;;
        "list 1")
            find "$wg_dir" -mindepth 1 -maxdepth 1 ! -name '.*' -printf '%f\n' | sort
            ;;
        "active 1")
            if [ -L "$wg_dir/wg0.conf" ]; then
                basename "$(readlink "$wg_dir/wg0.conf")"
            fi
            ;;
        "activate 2")
            valid_wg_name "$name" && [ "$name" != "wg0" ] || usage
            [ -f "$wg_dir/$name.conf" ] && [ ! -L "$wg_dir/$name.conf" ] || die 1 "no config $name"
            set_link "$wg_dir/wg0.conf" "$wg_dir/$name.conf"
            ;;
        "remove 2")
            valid_wg_name "$name" || usage
            rm -f -- "$wg_dir/$name.conf"
            ;;
        "genkey 2")
            valid_wg_entity "$name" || usage
            local priv="$wg_dir/$name-private.key" pub="$wg_dir/$name-public.key" key
            key="$(umask 077; wg genkey)" || die 1 "wg genkey failed"
            printf '%s\n' "$key" | install_file "$priv" key || exit 1
            printf '%s\n' "$key" | wg pubkey | install_file "$pub" key || exit 1
            cat -- "$pub"
            ;;
        "remove-keys 2")
            valid_wg_entity "$name" || usage
            rm -f -- "$wg_dir/$name-private.key" "$wg_dir/$name-public.key"
            ;;
        *)
            usage
            ;;
    esac
}

openvpn() {
    local action="$1" name="${2:-}" kind
    case "$action $#" in
        "install 3")
            kind="$3"
            valid_ovpn_name "$name" || usage
            case "$kind" in
                client) install_file "$ovpn_dir/${name}_client.conf" openvpn ;;
                login|keypass) install_file "$ovpn_dir/${name}_$kind.conf" "$kind" ;;
                *) usage ;;
            esac
            set_link "$ovpn_dir/$kind.conf" "$ovpn_dir/${name}_$kind.conf"
            ;;
        "read 2")
            [ "$name" = "client" ] || [ "$name" = "login" ] || usage
            [ -e "$ovpn_dir/$name.conf" ] || exit 0
            inside "$ovpn_dir" "$ovpn_dir/$name.conf" || die 1 "invalid file $name.conf"
            cat -- "$ovpn_dir/$name.conf"
            ;;
        "activate 2")
            valid_ovpn_name "$name" || usage
            inside "$ovpn_dir" "$ovpn_dir/${name}_client.conf" || die 1 "no config $name"
            for kind in client login keypass; do
                if [ -f "$ovpn_dir/${name}_$kind.conf" ]; then
                    set_link "$ovpn_dir/$kind.conf" "$ovpn_dir/${name}_$kind.conf"
                else
                    rm -f -- "$ovpn_dir/$kind.conf"
                fi
            done
            ;;
        "remove 2")
            valid_ovpn_name "$name" || usage
            [ -f "$ovpn_dir/${name}_client.conf" ] || die 1 "no config $name"
            if [ "$(basename "$(readlink "$ovpn_dir/client.conf" 2>/dev/null)")" = "${name}_client.conf" ]; then
                die 1 "the config $name is active"
            fi
            rm -f -- "$ovpn_dir/${name}_client.conf" "$ovpn_dir/${name}_login.conf" "$ovpn_dir/${name}_keypass.conf"
            ;;
        *)
            usage
            ;;
    esac
}

[ "$(id -u)" = 0 ] || die 1 "must run as root"
[ "$#" -ge 2 ] || usage

case "$1" in
    wireguard) shift; wireguard "$@" ;;
    openvpn) shift; openvpn "$@" ;;
    *) usage ;;
esac
