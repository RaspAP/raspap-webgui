#!/bin/bash

echo "AT+CRESET" > /dev/ttyUSB3

sleep 10

sudo service pppd-dns restart

echo "AT+CGPS=1,1" > /dev/ttyUSB3
