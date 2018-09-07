#!/usr/bin/env python
# -*- coding: utf-8 -*-

import lxml.html
import csv
from xlsx2csv import Xlsx2csv
import requests
import datetime

mda_host = '#my_host#'

proxies = {
	'http': '#my_proxy#',
	'https': '#my_proxy#'
}

class stock_data(object):
    """Represents stock data manipulation"""
    def __init__(self, url, proxy='', store='file', local_file='file'):
        self.url = url
        self.proxy = proxy
        self.store = store
        self.local_file = local_file

    def find_link(self):
        """ Find link for latest file and returns it"""

        headers = {
           'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.97 Safari/537.36 Vivaldi/1.94.1008.40'
        }
        r = requests.get(self.url, headers)
        tree = lxml.html.fromstring(r.text)
        el = tree.find_class('docheaderlink').pop(0)

        try:
           val = el.get('href')
           return val
        except:
           print "Can't find href element exiting"
           exit(1)
    def download_stock_data(self, link):
        """ Downloads data from url """

        resp = requests.get(link, stream=True, proxies=self.proxy)
        data = resp.content
        with open(self.local_file + '.xlsx', 'wb') as handle:
            handle.write(data)
    
    def convert_to_csv(self):
        """ Convert from xlsx to csv """

        Xlsx2csv(self.local_file+".xlsx").convert(self.local_file+".csv", sheetid=1)

    def filter_today_values(self):
        """ Populate filtered .csv with data for current day"""
        
        # it's better to use pandas for filtering and converting, but security banned this lib
        
        today = datetime.date.today().strftime("%m-%d-%y")
        with open(self.local_file+'_filtered.csv', 'wb') as filter_f:
            filtered_file = csv.writer(filter_f, delimiter=';')
            with open(self.local_file+'.csv', 'r') as csv_file:
                csv_doc = csv.reader(csv_file, delimiter=',')
                for row in csv_doc:
                    if today in row:
                        filtered_file.writerow(row)

    def send_result_over_http(self, url):
        """ Sends result over http """
        
        with open(self.local_file+'_filtered.csv', 'rb') as f:
           r = requests.post(url, files={self.local_file+'_filtered.csv': f}, proxies=self.proxy)
        if(r.status_code == 200):
           print('All send');



def main():
    # init object
    stock_d = stock_data('http://www.namex.org/ru/auction/price')
    # Find link to file
    latest_data = stock_d.find_link()
    # download file
    stock_d.download_stock_data(latest_data)
    # Convert to csv
    stock_d.convert_to_csv()
    # filter today
    stock_d.filter_today_values()
    # Send result via post request
    stock_d.send_result_over_http(mda_host)




if __name__ == "__main__":
    main()





