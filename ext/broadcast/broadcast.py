#!/usr/bin/python

import socket
import struct
import re
import urllib
from optparse import OptionParser

parser = OptionParser()
parser.add_option("--host", dest = "host", default = "SwissCenter", help = "Set the host identifier")
parser.add_option("--ip", dest="addr", default = "192.168.0.3:8080", help = "Set the ip address")
parser.add_option("--debug", dest="debug", action="store_true", default = False, help = "Enable debugging information")
parser.add_option("--match", dest="match", default = "USN: uuid:myiBoxUPnP::upnp:rootdevice", help = "Set the packet match string")
(options, args) = parser.parse_args()

# Setup debug logging
def disabled(func):
  def empty(*args, **kargs):
    pass
  return empty
def enabled(func):
  return func

state = (disabled,enabled)[int(options.debug)]
@state
def debugPrint(s):
  print s

# UPnP group / port
group = "239.255.255.250";
port = 1900;

debugPrint("H=%s I=%s M=\"%s\"" % (options.host,options.addr,options.match))

# Create multicast socket
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
sock.bind(('',port))
mcast = struct.pack("4sl", socket.inet_aton(group), socket.INADDR_ANY)
sock.setsockopt(socket.IPPROTO_IP, socket.IP_ADD_MEMBERSHIP, mcast)

while True:

  # Read packet
  (data, source) = sock.recvfrom(1024)

  debugPrint("Data from (%s:%d):\n%s" % (source[0], source[1], data))

  if re.match("^NOTIFY",data):
    # Found a SYABAS myIHome Device
    if re.search(options.match, data):
      # Extract URL
      url = re.search("LOCATION: (.*)\r", data)

      try:
        target = "%s?POSTSyabasiBoxURLpeername=%s&peeraddr=%s&" % (url.group(1), options.host, options.addr)
        request = urllib.urlopen(target).read()
        debugPrint(request)
      except:
        pass
