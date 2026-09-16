# Get the Mobile Country Codes (MCC) and Mobile Network Codes (MNC) table
# from www.mcc-mnc.com and output it in CSV format.
# Based on https://github.com/musalbas/mcc-mnc-table/blob/master/get-mcc-mnc-table-csv.py
# Updated to new webpage and changed html code by zbchristian

# Required package HTMLTableParser : pip install html-table-parser-python3 

import urllib.request
from html_table_parser.parser import HTMLTableParser

print("MCC,MCC (int),MNC,MNC (int),ISO,Country,Country Code,Network")

with urllib.request.urlopen('https://www.mcc-mnc.com/') as f:
    html = f.read().decode('utf-8')
    pTable = HTMLTableParser()
    pTable.feed(html)
    for l in pTable.tables[0]:
        csv_line = ''
        if l[0].isdigit():
            for n in range(0, 6):
                csv_line += l[n]
                if n == 0:
                    csv_line += ',' + str(int(l[n], 16))
                elif n == 1:
                    if len(l[n]) == 2:
                        mnc_int = int(l[n] + 'f', 16)
                    elif l[n] != 'n/a':
                        mnc_int = int(l[n], 16)
                    csv_line += ',' + str(mnc_int)
                if n != 5:
                    csv_line += ','
            print(csv_line)
