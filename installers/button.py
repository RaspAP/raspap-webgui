#!/usr/bin/env python3

from gpiozero import Button, LED
from signal import pause
import os, sys

# Define the location of the script to restore default configuration
defaultsscriptlocation = int(sys.argv[1]) if len(sys.argv) >= 1 else "/etc/raspap/hostapd/reset.sh"

buttonGPIO = 21         # Pushbutton is connected to GPIO 21 (pin 40)
LEDGPIO = 20            # LED is connected to GPIO 20 (pin 38)

restarttime = 1         # restart if held for greater than restarttime seconds
offtime = 6             # shut down if held for greater than offtime seconds
defaultstime = 15       # reset RaspAP to default config if held for greater than defaultstime seconds

resetready = False
shutdownready = False
defaultsready = False

def when_held():
    # find how long the button has been held
    ptime = b.pressed_time

    # blink rate will increase the longer we hold
    # Set flags for the action needed when the button is released
    if ptime > restarttime && ptime < offtime:
        led.blink(on_time=0.5/p, off_time=0.5/p)
        resetready      = True
        shutdownready   = False
        defaultsready   = False


    if ptime > offtime && ptime < defaultstime:
        led.blink(on_time=0.25/p, off_time=0.25/p)
        resetready      = False
        shutdownready   = True
        defaultsready   = False


    if ptime > defaultstime:
        led.blink(on_time=0.1/p, off_time=0.1/p)
        resetready      = False
        shutdownready   = False
        defaultsready   = True


def when_released():
    # turn the LEDs off if we release early
    led.off()

    if resetready == True:
        os.system("sudo restart")

    if shutdownready == True:
        os.system("sudo poweroff")

    if defaultsready == True:
        os.system("sudo " + defaultsscriptlocation)
        os.system("sudo restart")

    # clear flags if we release early
    resetready      = False
    shutdownready   = False
    defaultsready   = False


led = LED(LEDGPIO)

btn = Button(buttonGPIO, hold_time=restarttime, hold_repeat=True)
btn.when_held = when_held
btn.when_released = when_released
pause()