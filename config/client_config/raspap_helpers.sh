#!/bin/bash
# 
# Helper functions to extract informations from RaspAP config/settings
#
# zbchristian 2021
#
# get the values of a RaspAP config variable
# call: _getRaspapConfig RASPAP_MOBILEDATA_CONFIG

raspap_webroot="/var/www/html"

function _getWebRoot() {
    local path
    path=$(cat /etc/lighttpd/lighttpd.conf | sed -rn "s/server.document-root \s*= \"([^ \s]*)\"/\1/p")
    if [ ! -z "$path" ]; then raspap_webroot="$path"; fi
    if [ -z "$path" ]; then return 1; else return 0; fi
}

# expand an RaspAP config variable utilizing PHP
function _getRaspapConfig() {
    local conf var
    raspap_config=""
    var="$1"
    if [ ! -z "$var" ]; then 
        if ! _getWebRoot; then return 1; fi
        conf="$raspap_webroot/includes/config.php"
        if [ -f "$conf" ]; then
            conf=$(php -r 'include "'$conf'"; echo '$var';' 2> /dev/null)
            if [ ! -z "$conf" ] && [ -d ${conf%/*} ]; then raspap_config="$conf"; fi
        fi
    fi
    if [ -z "$raspap_config" ]; then return 1; else return 0; fi
}

# Username and password for mobile data devices is stored in a file (RASPAP_MOBILEDATA_CONFIG)  
function _getAuthRouter() {
    local mfile mdata pin user pw
    if ! _getRaspapConfig "RASPI_MOBILEDATA_CONFIG"; then return 1; fi
    mfile="$raspap_config" 
    if [ -f $mfile ]; then      
        mdata=$(cat "$mfile")
        pin=$(echo "$mdata" | sed -rn 's/pin = ([^ \s]*)/\1/ip')
        if [ ! -z "$pin" ]; then raspap_pin="$pin"; fi
        user=$(echo "$mdata" | sed -rn 's/router_user = ([^ \s]*)/\1/ip')
        if [ ! -z "$user" ]; then raspap_user="$user"; fi
        pw=$(echo "$mdata" | sed -rn 's/router_pw = ([^ \s]*)/\1/ip')
        if [ ! -z "$pw" ]; then raspap_password="$pw"; fi
        return 0
    fi
    return 1
}
