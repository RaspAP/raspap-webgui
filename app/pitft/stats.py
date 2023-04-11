# -*- coding: utf-8 -*-
#
# Author: @billz
# Author URI: https://github.com/billz
# Description: RaspAP stats display for the Adafruit Mini PiTFT,
#   a 135x240 Color TFT add-on for the Raspberry Pi.
#   Based on Adafruit's rgb_display_ministats.py
# See: https://github.com/adafruit/Adafruit_CircuitPython_RGB_Display
# License: MIT License

import time
import subprocess
import digitalio
import board
from PIL import Image, ImageDraw, ImageFont
import adafruit_rgb_display.st7789 as st7789

# Configuration for CS and DC pins
cs_pin = digitalio.DigitalInOut(board.CE0)
dc_pin = digitalio.DigitalInOut(board.D25)
reset_pin = None

# Config for display baudrate (default max is 24mhz)
BAUDRATE = 64000000

# Setup SPI bus using hardware SPI
spi = board.SPI()

# Create the ST7789 display
disp = st7789.ST7789(spi, cs=cs_pin, dc=dc_pin, rst=reset_pin, baudrate=BAUDRATE,
                     width=135, height=240, x_offset=53, y_offset=40)

# Create blank image with mode 'RGB'
height = disp.width   # swap height/width to rotate it to landscape
width = disp.height
image = Image.new('RGB', (width, height))
rotation = 90

# Get a drawing object and clear the image
draw = ImageDraw.Draw(image)
draw.rectangle((0, 0, width, height), outline=0, fill=(0, 0, 0))
disp.image(image,rotation)

# Define some constants
padding = -2
top = padding
bottom = height-padding
# Move left to right keeping track of the current x position
x = 0

# Load DejaVu TTF Font
# Install with: sudo apt-get install ttf-dejavu
font = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 24)

# Turn on the backlight
backlight = digitalio.DigitalInOut(board.D22)
backlight.switch_to_output()
backlight.value = True

while True:
    # Draw a black filled box to clear the image
    draw.rectangle((0, 0, width, height), outline=0, fill=0)

    # Collect basic system stats
    cmd = "hostname -I | cut -d\' \' -f1"
    IP = "IP: "+subprocess.check_output(cmd, shell=True).decode("utf-8")

    cmd = "pidof hostapd | wc -l | awk '{printf \"Hotspot: %s\", $1 == 1 ? \"Active\" : \"Down\"}'"
    Hostapd = subprocess.check_output(cmd, shell=True).decode("utf-8")

    cmd = "vnstat -i wlan0 | grep tx: | awk '{printf \"Data Tx: %d %s\", $5,$6}'"
    DataTx = subprocess.check_output(cmd, shell=True).decode("utf-8")

    cmd = "top -bn1 | grep load | awk '{printf \"CPU Load: %.2f\", $(NF-2)}'"
    CPU = subprocess.check_output(cmd, shell=True).decode("utf-8")

    cmd = "free -m | awk 'NR==2{printf \"Mem: %sMB %.2f%%\", $3,$3*100/$2 }'"
    MemUsage = subprocess.check_output(cmd, shell=True).decode("utf-8")

    cmd = "cat /sys/class/thermal/thermal_zone0/temp |  awk \'{printf \"CPU Temp: %.1f C\", $(NF-0) / 1000}\'" # pylint: disable=line-too-long
    Temp = subprocess.check_output(cmd, shell=True).decode("utf-8")

    # Write five lines of stats
    y = top
    draw.text((x, y), IP, font=font, fill="#ffaaaa")
    y += font.getsize(IP)[1]
    draw.text((x, y), Hostapd, font=font, fill="#d46a6a")
    y += font.getsize(Hostapd)[1]
    draw.text((x, y), DataTx, font=font, fill="#aa3939")
    y += font.getsize(DataTx)[1]
    draw.text((x, y), MemUsage, font=font, fill="#801515")
    y += font.getsize(MemUsage)[1]
    draw.text((x, y), Temp, font=font, fill="#550000")

    # Display image
    disp.image(image, rotation)
    time.sleep(.1)

