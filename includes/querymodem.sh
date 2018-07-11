#!/bin/bash

MODEM="/dev/ttyUSB3"

function get_response
{
        local ECHO
        # cat will read the response, then die on timeout
        cat <&5 >$TMP &
        echo "$1" >&5
        # wait for cat to die
        wait $!

        exec 6<$TMP
        read ECHO <&6
        if [ "$ECHO" != "$1" ]
        then
                exec 6<&-
                return 1
        fi

        read ECHO <&6
        read RESPONSE <&6
        exec 6<&-
        return 0
}

TMP="./response"

# Clear out old response
: > $TMP

# Set modem with timeout of 5/10 a second
stty -F "$MODEM" -echo igncr -icanon onlcr ixon min 0 time 5

# Open modem on FD 5
exec 5<>"$MODEM"

# Signal Strenght
get_response "AT+CSQ" || echo "Bad response"
echo "Response was '${RESPONSE}'"       ;       cat $TMP

# Provider Name
get_response "AT+COPS?" || echo "Bad response"
echo "Response was '${RESPONSE}'"       ;       cat $TMP

# System Mode aka LTE etc
get_response "AT+CPSI?" || echo "Bad response"
echo "Response was '${RESPONSE}'"       ;       cat $TMP

echo

exec 5<&-
