
Database Patching
=================

As more Swisscenter users are now using the Subversion source control repository to download the latest *unstable* release of Swisscenter, the patching mechanism has been changed to ensure that database changes are applied on the first use of Swisscenter following the subversion update.

In previous versions, database updates were only applied as part of either a complete database rebuild or when the user performed an online update of the software. Developers had to apply the database patches themselves manually - which wasn't really possible for users of Simese as there was no command line client present in the distribution for accessing the MySQL database.

Creating Database Patches
=========================

All database patches should be placed in the <swisscenter>/database directory with a filename of "patch_<nnnn>.sql" where <nnnn> is a 4 digit, zero-padded incrementing number. Patches will be applied in numerical order and SwissCenter will keep a record of the last patch number to be applied to the database to ensure that a patch is never applied more than once.

Applying Patches
================

Patches are automatically applied as follows:

* If there are outstanding patches to be applied when the configuration utility is accessed, then they will be applied.
* If there are outstanding patches to be applied when the SwissCenter main menu is requested, then they will be applied.
* If user creates (or recreates) the database by clicking on the option in the configuration utility, then ALL patches will be applied.

Please note: With the new patching scheme, database patch numbers have no relation to the Swisscenter version number.