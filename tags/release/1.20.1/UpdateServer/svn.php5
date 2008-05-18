<?
set_time_limit(86400);

// Get required files
require_once('scripts/common.php5');
require_once('scripts/mysql.php5');
require_once('scripts/html_form.php5');  
require_once('scripts/config_release.php5');

// Where are we downloading to?
$temp_path = '/home/swisscenter/www/update/svn';

// Download files
exec('rm -rf '.$temp_path.'/*');
exec('rm svn.zip');
echo '<p>Downloading latest revision from Subversion...';
download_files( 'http://tools.assembla.com/svn/swiss/trunk/swisscenter/', $temp_path );  

// Compressing the files
echo '<br>Compressing the files...';
exec('zip -j svn.zip svn/*');
exec('rm ../www/downloads/DevRelease.zip');
exec('mv svn.zip ../www/downloads/DevRelease.zip');

echo '<br>Complete. <p>Zipfile available at <a href="http://swisscenter.co.uk/downloads/DevRelease.zip">http://swisscenter.co.uk/downloads/DevRelease.zip</a>.';
?>