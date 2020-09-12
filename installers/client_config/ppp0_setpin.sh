#!/bin/bash
# in case /dev/ttyUSB0 does not exist, wait for it at most 30 seconds
let i=1
while ! test -c /dev/ttyUSB0; do
  let i+=1
  if [ $i -gt 2 ]; then
    logger -s -t setpin "/dev/ttyUSB0 does not exist"
    exit 3
  fi
  logger -s -t setpin "waiting 3 seconds for /dev/ttyUSB0"
  sleep 3
done
# check for pin and set it if necessary
wvdial pinstatus 2>&1 | grep -q '^+CPIN: READY'
if [ $? -eq 0 ]; then
  logger -s -t setpin "SIM card is ready to use :-)"
else
  logger -s -t setpin "setting PIN"
  wvdial pin 2>/dev/null
fi
exit 0
