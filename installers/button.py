#!/usr/bin/env python3

from gpiozero import Button, LED
from signal import pause
import os, sys

# Define the location of the script to restore default configuration
defaultsscriptlocation = "/etc/raspap/hostapd/reset.sh"
scriptlocation = sys.argv[1] if len(sys.argv) >= 2 else defaultsscriptlocation

buttonGPIO = 21         # Pushbutton is connected to GPIO 21 (pin 40)
ledGPIO = 20            # LED is connected to GPIO 20 (pin 38)

restarttime = 1         # restart if held for greater than restarttime seconds
offtime = 6             # shut down if held for greater than offtime seconds
defaultstime = 15       # reset RaspAP to default config if held for greater than defaultstime seconds

restartready = False     # Flag for reset time exceeded
shutdownready = False     # Flag for shutdown time exceeded
defaultsready = False     # Flag for defaults reset flag exceeded

def when_held():
    global restartready
    global shutdownready
    global defaultsready

    # find how long the button has been held
    held_time = button.held_time + restarttime

    # blink rate will increase the longer we hold
    # Set flags for the action needed when the button is released
    if held_time > restarttime and held_time < offtime and not restartready:
        led.blink(on_time=0.5, off_time=0.5)
        restartready = True
        shutdownready = False
        defaultsready = False
        print ("Restart time reached")


    if held_time > offtime and held_time < defaultstime and not shutdownready:
        led.blink(on_time=0.25, off_time=0.25)
        restartready = False
        shutdownready = True
        defaultsready = False
        print ("Shutdown time reached")


    if held_time > defaultstime and not defaultsready:
        led.blink(on_time=0.1, off_time=0.1)
        restartready = False
        shutdownready = False
        defaultsready = True
        print ("Restore defaults time reached")


def when_released():
    global restartready
    global shutdownready
    global defaultsready

    led.on()

    if restartready:
        print ("System restarting")
        os.system("sudo reboot")

    if shutdownready:
        print ("System powering down")
        os.system("sudo poweroff")

    if defaultsready:
        print ("System restoring RaspAP defaults")
        os.system("sudo bash " + scriptlocation)
        os.system("sudo reboot")

    # Clear flags if the button was released early
    print ("Button released before any action was needed")
    restartready = False
    shutdownready = False
    defaultsready = False


led = LED(ledGPIO)
led.on()

button = Button(buttonGPIO, hold_time=restarttime, hold_repeat=True)
button.when_held = when_held
button.when_released = when_released

print ("Waiting for a button press")

pause()