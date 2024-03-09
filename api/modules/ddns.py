import subprocess

def use():
    return subprocess.run("cat /etc/ddclient.conf | grep use= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def method():
    #get the contents of the line below "use="
    return subprocess.run("awk '/^use=/ {getline; print}' /etc/ddclient.conf | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def protocol():
    return subprocess.run("cat /etc/ddclient.conf | grep protocol= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def server():
    return subprocess.run("cat /etc/ddclient.conf | grep server= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def login():
    return subprocess.run("cat /etc/ddclient.conf | grep login= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def password():
    return subprocess.run("cat /etc/ddclient.conf | grep password= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def domain():
    #get the contents of the line below "password="
    return subprocess.run("awk '/^password=/ {getline; print}' /etc/ddclient.conf", shell=True, capture_output=True, text=True).stdout.strip()