:
#
# usage: $0 country
#
if [ -z "$1" ] ; then
    echo Usage: $0 country-you-want-to-be-teleported-to
    echo
    echo "  or, to list what's countries are currently out there -"
    echo
    echo Usage: $0 list
    echo
    exit 1
fi
baseDir=/home/pi/openVPN
mkdir -p "$baseDir/conf"
#
# shangri-la... but http?  Really? :)
#
URL="http://www.vpngate.net/api/iphone/"

# this is where the list of openvpn servers lives
VPN_SERVERS="$baseDir/vpnlist"

EXPIRE="6000"  # server cache list will expire after 1 hour
CURRENT_TIME=$(date +%s)
STALE_WHEN=$(echo $(expr $CURRENT_TIME - $EXPIRE))

# assume hard -n- crusty
STALE="YES"

# if it's zero length, nuke it
if [ ! -s "$VPN_SERVERS" ] ; then
    rm -f "$VPN_SERVERS"
fi

# check cache file... does it exist, and, if it does, how fresh?
if [ -f "$VPN_SERVERS" ] ; then
    echo cache found, checking age...
    AGE=$(/usr/bin/stat -c "%Y" "$VPN_SERVERS")

    if [ $STALE_WHEN -gt $AGE ] ; then
        STALE="YES"
        echo cache is old, old, old....
    else
        STALE="NO"
        echo cache is still minty fresh
    fi
fi

if [ "$STALE" = "YES" ]; then
	echo 'getting fresh server list'
	tmp_dl=$(mktemp)
	curl -s "$URL" -o "$tmp_dl"
	if [ $? != 0 ]; then
		echo Failed getting VPN server list from $URL, use existing...
	else
		rm -f $VPN_SERVERS
		mv $tmp_dl $VPN_SERVERS
		find $baseDir -mtime +10 -type f -delete
	fi
fi

#
# special case
#
if [ "$country" == "list" ]; then
    awk -F, 'NR > 2 {print $7, $6}' "$VPN_SERVERS" | sort -u
    exit 0
fi
for var in "$@"
do
country=$var
	echo "looking for country $country in server list"

	# field 7 is country, last field is config

	#HostName,IP,Score,Ping,Speed,CountryLong,CountryShort,NumVpnSessions,Uptime,TotalUsers,TotalTraffic,LogType,Operator,Message,OpenVPN_ConfigData_Base64

	awk -F, '"'"$country"'" == $7 {print $0}' "$VPN_SERVERS" >"/tmp/ovpn.$country.lst"
	while IFS= read -r line
		do
			[ -z "$line" ] && continue
			decode=$(echo $line | awk -F, '{print $NF}')
			ip=$(echo $line | awk -F, '{print $2}')
			logType=$(echo $line | awk -F, '{print $12}')
			proto=$(echo $decode | base64 -d | grep 'proto' | grep -v '^#' | tr '\n' '\0' | tr '\r' '\0' | awk '{print $2}')
			fname="$country-$logType-$proto-$ip.ovpn"
			#echo $fname
			echo $decode | base64 -d > "$baseDir/conf/$fname"
	#echo $var
	done < "/tmp/ovpn.$country.lst"
done

chown www-data:www-data $baseDir/conf/*
zip -j /var/www/html/download/openvpn.zip $baseDir/conf/*
chown www-data:www-data /var/www/html/download/openvpn.zip
