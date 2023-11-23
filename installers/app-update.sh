#!/bin/bash
#
# RaspAP Application Update
# Safely updates an existing RaspAP installation
# Author: @billz <billzimmerman@gmail.com>
# Author URI: https://github.com/billz
# Project URI: https://github.com/RaspAP/
# License: GNU General Public License v3.0
# License URI: https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
#
# Reads arguments passed by the RaspAP application and securely executes the 
# local raspbian.sh install loader.

# Exit on error
set -o errexit
# Exit on error inside functions
set -o errtrace
# Exit on pipeline error
set -eo pipefail

function _main() {
    # set defaults
    _parse_params "$@"
    _load_installer
}

function _parse_params() {
    # set defaults
    insiders=0
    acctoken=""
    username=""
    path=""

    while :; do
        case "${1-}" in
            -i|--insiders)
            insiders=1
            ;;
            -t|--token)
            acctoken="$2"
            shift
            ;;
            -n|--name)
            username="$2"
            shift
            ;;
            -p|--path)
            path="$2"
            shift
            ;;
            -*|--*)
            echo "Unknown option: $1"
            exit 1
            ;;
            *)
            break
            ;;
        esac
        shift
    done
}

function _load_installer() {
    args=()
    if [ "$insiders" -eq 1 ]; then
        args+=("--insiders")
    fi
    if [ -n "$path" ]; then
        args+=("--path ${path}")
    fi
    if [ -n "$username" ]; then
        args+=("--name ${username}")
    fi
    if [ -n "$acctoken" ]; then
        args+=("--token ${acctoken}")
    fi
    filtered=()
    for arg in "${args[@]}"; do
        if [ -n "$arg" ]; then
            filtered+=("$arg")
        fi
    done

    echo "Loading installer..."
    echo "${path}/installers/raspbian.sh --update --yes ${filtered[*]}"
    $path/installers/raspbian.sh --update --yes ${filtered[*]} || { echo "Failed to execute raspbian.sh - last error: $?"; }
}

_main "$@"

