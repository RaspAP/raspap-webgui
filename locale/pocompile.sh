#!/bin/bash

# Compiles portable object (.po) files into machine object (.mo) files
# Requires GNU gettext 
# Install with: apt-get install gettext 

arrLocales=($PWD/*/);

# compiles message catalogs to binary format
for f in "${arrLocales[@]}"; do
  echo -n `msgfmt -o ${f}LC_MESSAGES/messages.mo ${f}LC_MESSAGES/messages.po`
  echo "Compiled ${f}LC_MESSAGES/messages.po"
done

