@ECHO %0 %*
@pushd
@cd /D "%~dp0"
@cd
upnp2http -U
@pause
@popd
