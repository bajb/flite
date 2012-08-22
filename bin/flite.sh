#!/bin/bash
#
# Author: Brooke Bryan
#
# Description:  PHP-Flite Cache

# Source function library.
. /etc/rc.d/init.d/functions

clear
tput setaf 6
echo "########  ##     ## ########     ######## ##       #### ######## ######## "
echo "##     ## ##     ## ##     ##    ##       ##        ##     ##    ##       "
echo "##     ## ##     ## ##     ##    ##       ##        ##     ##    ##       "
echo "########  ######### ########     ######   ##        ##     ##    ######   "
echo "##        ##     ## ##           ##       ##        ##     ##    ##       "
echo "##        ##     ## ##           ##       ##        ##     ##    ##       "
echo "##        ##     ## ##           ##       ######## ####    ##    ######## "
echo "";
echo "";
tput sgr0

echo "Rebuilding Keyspaces for Flite"
rm $FLITE_V1_DIR/cache/keyspace_* -f
php $FLITE_V1_DIR/cron/cache/hourly.php
tput cuu1
tput el
action "Flite Keyspace Rebuild Complete" /bin/true

echo "Rebuilding Keyspaces for PHP-Flite"
rm $FLITE_DIR/cache/keyspace_* -f
php $FLITE_DIR/cron/cache/keyspace.php > /dev/null
tput cuu1
tput el
action "PHP-Flite Keyspace Rebuild Complete" /bin/true

echo "Rebuilding PHP-Flite Lib/Config Cache Files"
rm $FLITE_DIR/cache/config.php -f
php $FLITE_DIR/cron/cache/config.php
rm $FLITE_DIR/cache/core.lib.php -f
php $FLITE_DIR/cron/cache/corelib.php
tput cuu1
tput el
action "Rebuild Complete" /bin/true

echo "";
echo "";