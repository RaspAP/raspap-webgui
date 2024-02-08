import subprocess

def webgui():
    return subprocess.run("sudo /etc/raspap/lighttpd/configport.sh --restart", shell=True, capture_output=True, text=True).stdout.strip()

def adblock():
    return subprocess.run("sudo /bin/systemctl restart dnsmasq.service", shell=True, capture_output=True, text=True).stdout.strip()