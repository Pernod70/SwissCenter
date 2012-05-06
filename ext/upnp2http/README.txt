upnp2http version 1.4
=====================
 
The goal of upnp2http is to publish, through upnp, the addresses of HTTP servers where the media center should connect. This way there is no need to create special share.
 
Of course, the HTTP server has to exist and properly work. upnp2http is NOT an HTTP server - only a upnp name and address publisher.

Installation
============
 
All configurations should preferably go in the 'upnp2http.conf' file but you can also use command line arguments that will overwrite the value specified in the configuration file:
 
 c:\upnp2http -a <address> -p <port> -n <name>
 
ex: c:\upnp2http -a 192.168.1.200 -p 8001 -n "Movie Jukebox"
 
 upnp2http can also be installed as an NT/XP/Vista service with the -I option (case sensitive).
 When installed as a service only the configuration arguments from the 'upnp2http.conf' file will take effect.
 Use the -U option to install the service
 Use the -h option to list the command line option

To run as a service you basically need upnp2http to be allowed to receive incoming connection through the firewall and install the service by running 'upnp2http -U' in administrator mode.


Step by step for Windows service
================================
 
1. Configure upnp2http.conf with the proper server information. Use any text editor to modify this file.
2. If you are using a firewall, execute the 1-START.bat file to get the firewall allow/deny option dialog box. Allow upnp2http to listen for incoming connection. Ctrl-C to exit.
3. Run 2-INSTALL-SERVICE(Run as Administrator).bat in administrator mode.



For comment:
nono893@gmail.com
