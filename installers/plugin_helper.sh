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

  "deb")
    [ $# -lt 1 ] && { echo "Usage: $0 deb <deb_file>"; exit 1; }
    deb_file="$1"

    if [ ! -f "$deb_file" ]; then
        echo "Error: File not found: $deb_file"
        exit 1
    fi

    echo "Installing .deb package: $deb_file"
    dpkg -i "$deb_file"

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
    cp "$source" "$destination"
    chown -R $raspap_user:$raspap_user "$destination"

    echo "OK"
    ;;

  "permissions")
    [ $# -lt 4 ] && { echo "Usage: $0 permissions <filepath> <user> <group> <mode>"; exit 1; }

    filepath="$1"
    user="$2"
    group="$3"
    mode="$4"

    if [ ! -e "$filepath" ]; then
        echo "File not found: $filepath" >&2
        exit 1
    fi

    chown "$user:$group" "$filepath" || exit 1
    chmod "$mode" "$filepath" || exit 1

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

  "keys")
    [ $# -ne 4 ] && { echo "Usage: $0 keys <key_url> <keyring> <repo> <sources>"; exit 1; }

    key_url="$1"
    keyring="$2"
    repo="$3"
    list_file="$4"

    # add repository GPG key if it doesn't already exist
    if [ ! -f "$keyring" ]; then
        echo "Downloading GPG key from $key_url..."
        curl -fsSL "$key_url" | sudo tee "$keyring" > /dev/null || { echo "Error: Failed to download GPG key."; exit 1; }
    else
        echo "Repository GPG key already exists at $keyring"
    fi

    # add repository list if not present
    if [ ! -f "$list_file" ]; then
        echo "Adding repository $repo to sources list"
        curl -fsSL "$repo" | sudo tee "$list_file" > /dev/null || { echo "Error: Failed to add repository to sources list."; exit 1; }
        update_required=1
    else
        echo "Repository already exists in sources list"
    fi

    # update apt package list if required
    if [ "$update_required" == "1" ]; then
        sudo apt-get update || { echo "Error: Failed to update apt"; exit 1; }
    fi

    echo "OK"
    ;;

  *)
    echo "Invalid action: $action"
    echo "Usage: $0 <action> [parameters...]"
    echo "Actions:"
    echo "  sudoers <file>                              Install a sudoers file"
    echo "  packages <packages>                         Install aptitude package(s)"
    echo "  user <name> <password>                      Add user non-interactively"
    echo "  config <source <destination>                Applies a config file"
    echo "  javascript <source> <destination>           Applies a JavaScript file"
    echo "  plugin <source> <destination>               Copies a plugin directory"
    echo "  keys <key_url> <keyring> <repo> <sources>   Installs a GPG key for a third-party repo"
    exit 1
    ;;
esac
