import subprocess

revisions = {
    '0002': 'Model B Revision 1.0',
    '0003': 'Model B Revision 1.0 + ECN0001',
    '0004': 'Model B Revision 2.0 (256 MB)',
    '0005': 'Model B Revision 2.0 (256 MB)',
    '0006': 'Model B Revision 2.0 (256 MB)',
    '0007': 'Model A',
    '0008': 'Model A',
    '0009': 'Model A',
    '000d': 'Model B Revision 2.0 (512 MB)',
    '000e': 'Model B Revision 2.0 (512 MB)',
    '000f': 'Model B Revision 2.0 (512 MB)',
    '0010': 'Model B+',
    '0013': 'Model B+',
    '0011': 'Compute Module',
    '0012': 'Model A+',
    'a01041': 'a01041',
    'a21041': 'a21041',
    '900092': 'PiZero 1.2',
    '900093': 'PiZero 1.3',
    '9000c1': 'PiZero W',
    'a02082': 'Pi 3 Model B',
    'a22082': 'Pi 3 Model B',
    'a32082': 'Pi 3 Model B',
    'a52082': 'Pi 3 Model B',
    'a020d3': 'Pi 3 Model B+',
    'a220a0': 'Compute Module 3',
    'a020a0': 'Compute Module 3',
    'a02100': 'Compute Module 3+',
    'a03111': 'Model 4B Revision 1.1 (1 GB)',
    'b03111': 'Model 4B Revision 1.1 (2 GB)',
    'c03111': 'Model 4B Revision 1.1 (4 GB)',
    'c03111': 'Model 4B Revision 1.1 (4 GB)',
    'a03140': 'Compute Module 4 (1 GB)',
    'b03140': 'Compute Module 4 (2 GB)',
    'c03140': 'Compute Module 4 (4 GB)',
    'd03140': 'Compute Module 4 (8 GB)',
    'c04170': 'Pi 5 (4 GB)',
    'd04170': 'Pi 5 (8 GB)'
}

def hostname():
    return subprocess.run("hostname", shell=True, capture_output=True, text=True).stdout.strip()

def uptime():
    return subprocess.run("uptime -p", shell=True, capture_output=True, text=True).stdout.strip()

def systime():
    return subprocess.run("date", shell=True, capture_output=True, text=True).stdout.strip()

def usedMemory():
    return round(float(subprocess.run("free -m | awk 'NR==2{total=$2 ; used=$3 } END { print used/total*100}'", shell=True, capture_output=True, text=True).stdout.strip()),2)

def usedDisk():
    return float(subprocess.run("df -h / | awk 'NR==2 {print $5}' | sed 's/%$//'", shell=True, capture_output=True, text=True).stdout.strip())

def processorCount():
    return int(subprocess.run("nproc --all", shell=True, capture_output=True, text=True).stdout.strip())

def LoadAvg1Min():
    return round(float(subprocess.run("awk '{print $1}' /proc/loadavg", shell=True, capture_output=True, text=True).stdout.strip()),2)

def systemLoadPercentage():
    return round((float(LoadAvg1Min())*100)/float(processorCount()),2)

def systemTemperature():
    try:
        output = subprocess.run("cat /sys/class/thermal/thermal_zone0/temp", shell=True, capture_output=True, text=True).stdout.strip()
        return round(float(output)/1000,2)
    except ValueError:
        return 0

def hostapdStatus():
    return int(subprocess.run("pidof hostapd | wc -l", shell=True, capture_output=True, text=True).stdout.strip())

def operatingSystem():
    return subprocess.run('''grep PRETTY_NAME /etc/os-release | cut -d= -f2- | sed 's/"//g' ''', shell=True, capture_output=True, text=True).stdout.strip()

def kernelVersion():
    return subprocess.run("uname -r", shell=True, capture_output=True, text=True).stdout.strip()

def rpiRevision():
    output = subprocess.run("grep Revision /proc/cpuinfo | awk '{print $3}'", shell=True, capture_output=True, text=True).stdout.strip()
    try:
        return revisions[output]
    except KeyError:
        return 'Unknown Device'

def raspapVersion():
    return subprocess.run("grep 'RASPI_VERSION' /var/www/html/includes/defaults.php | awk -F\"'\" '{print $4}'", shell=True, capture_output=True, text=True).stdout.strip()
