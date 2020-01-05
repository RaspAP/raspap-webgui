echo 1 > /proc/sys/net/ipv4/ip_forward #RASPAP
iptables -t nat -A POSTROUTING -j MASQUERADE #RASPAP 
iptables -t nat -A POSTROUTING -s 192.168.50.0/24 ! -d 192.168.50.0/24 -j MASQUERADE #RASPAP
date >>/var/log/myLog.log
echo $0 >>/var/log/myLog.log
