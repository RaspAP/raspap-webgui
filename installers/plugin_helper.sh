#!/bin/bash
#
# PluginInstaller helper for RaspAP 
# @author billz
# license: GNU General Public License v3.0

# Exit on error
set -o errexit

readonly raspap_user="www-data"

[ $# -lt 1 ] && { echo "Usage: $0 <action> [parameters...]"; exit 1; }

action="$1"  # action to perform
shift 1      

case "$action" in

  "sudoers")
    [ $# -ne 1 ] && { echo "Usage: $0 sudoers <file>"; exit 1; }
    file="$1"
    plugin_name=$(basename "$file")
    dest="/etc/sudoers.d/${plugin_name}"

    mv "$file" "$dest" || { echo "Error: Failed to move $file to $dest."; exit 1; }

    chown root:root "$dest" || { echo "Error: Failed to set ownership for $dest."; exit 1; }
    chmod 0440 "$dest" || { echo "Error: Failed to set permissions for $dest."; exit 1; }

    echo "OK"
    ;;

  "packages")
    [ $# -lt 1 ] && { echo "Usage: $0 packages <apt_packages...>"; exit 1; }

    echo "Installing APT packages..."
    for package in "$@"; do
      echo "Installing package: $package"
      apt-get install -y "$package" || { echo "Error: Failed to install $package."; exit 1; }
    done
    echo "OK"
    ;;

  "user")
    [ $# -lt 2 ] && { echo "Usage: $0 user <username> <password>."; exit 1; }

    username=$1
    password=$2

    if id "$username" &>/dev/null; then # user already exists
        echo "OK"
        exit 0
    fi
    # create the user without shell access
    useradd -r -s /bin/false "$username"

    # set password non-interactively
    echo "$username:$password" | chpasswd

    echo "OK"
    ;;

  "config")
    [ $# -lt 2 ] && { echo "Usage: $0 config <source> <destination>"; exit 1; }

    source=$1
    destination=$2

    if [ ! -f "$source" ]; then
        echo "Source file $source does not exist."
        exit 1
    fi

    mkdir -p "$(dirname "$destination")"
    chown -R "$raspap_user:$raspap_user" "$(dirname "$destination")"
    cp "$source" "$destination"
    chown -R $raspap_user:$raspap_user "$destination"

    echo "OK"
    ;;

  "javascript")
    [ $# -lt 2 ] && { echo "Usage: $0 javascript <source> <destination>"; exit 1; }

    source=$1
    destination=$2

    if [ ! -f "$source" ]; then
        echo "Source file $source does not exist."
        exit 1
    fi

    if [ ! -d "$destination" ]; then
        mkdir -p "$destination"
    fi

    cp "$source" "$destination"
    chown -R $raspap_user:$raspap_user "$destination"

    echo "OK"
    ;;


  "plugin")
    [ $# -lt 2 ] && { echo "Usage: $0 plugin <source> <destination>"; exit 1; }

    source=$1
    destination=$2

    if [ ! -d "$source" ]; then
        echo "Source directory $source does not exist."
        exit 1
    fi

    plugin_dir=$(dirname "$destination")
    if [ ! -d "$plugin_dir" ]; then
        mkdir -p "$plugin_dir"
    fi

    cp -R "$source" "$destination"
    chown -R $raspap_user:$raspap_user "$plugin_dir"

    echo "OK"
    ;;

  *)
    echo "Invalid action: $action"
    echo "Usage: $0 <action> [parameters...]"
    echo "Actions:"
    echo "  sudoers <file>                      Install a sudoers file"
    echo "  packages <packages>                 Install aptitude package(s)"
    echo "  user <name> <password>              Add user non-interactively"
    echo "  config <source <destination>        Applies a config file"
    echo "  javascript <source> <destination>   Applies a JavaScript file"
    echo "  plugin <source <destination>        Copies a plugin directory"
    exit 1
    ;;
esac
