#!/usr/bin/perl

# Script to ensure that the Pinnacle Showcenter (and possibly all other Syabas iHome
# devices..) finds this swisscenter server. It works by detecting the upnp::rootdevice
# NOTIFY announcement for a SYABAS device and sending a URL request. I can't find any
# documentation on this so it's just been unpicked using ethereal.

# If you receive an error that "IO::Socket::Multicast" doesn't exist, then install it
# as follow (as the root user):
#   perl -MCPAN -e 'install IO:Interface'
#   perl -MCPAN -e 'install IO::Socket::Multicast'
#   perl -MCPAN -e 'install HTTP::Lite'

# Bring in a couple of modules
use IO::Socket::Multicast;
use HTTP::Lite;
use Getopt::Long;

# extract hostname and IP from the command line
my $host = "SwissCenter";
my $addr = "192.168.0.3:8080";
my $debug;

$result = GetOptions("host=s" => \$host, "ip=s" => \$addr, "debug" => \$debug);
print "H=$host I=$addr \n" if ($debug);

# the upnp multicast group:port
use constant GROUP => '239.255.255.250';
use constant PORT  => '1900';

# and create the multicast endpoint
my $sock = IO::Socket::Multicast->new(Proto=>'udp',LocalPort=>PORT,ReuseAddr=>1);
$sock->mcast_add(GROUP) || die "Couldn't set multicasp UPnP Group: $!\n";

# should really listen for a command to stop
while (1) {
    my $data;
    # read a multicast message
    $hispaddr = $sock->recv($data,1024);
    ($port, $hisiaddr) = sockaddr_in($hispaddr);
    $peer_addr = inet_ntoa($hisiaddr);
 
    #print "Recv from ".$peer_addr.":".$hisaddr.":".$port.":".$data;

    if ($data =~ /NOTIFY/) {
        # Found a SYSBAS myiHome Device
        if ($data =~ /USN: uuid:myiBoxUPnP::upnp:rootdevice/) {
            # Find out where it is
            $data =~ /LOCATION: (.*)/;
            $url=$1;
            sleep 1;
            $http = new HTTP::Lite;
            $req = $http->request($url."?POSTSyabasiBoxURLpeername=$host&peeraddr=$addr&")
              or die "Unable to get document: $!";
            print $http->body() if ($debug);
        }
    }
}
