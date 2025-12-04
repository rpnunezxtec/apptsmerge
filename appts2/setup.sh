#!/bin/bash

APPNAME="authentx"
OWNER=root
GROUP=root
OWNER_W=apache
GROUP_W=apache

chown -R ${OWNER}:${GROUP} *.php

# apptsitesync
if [ -d apptsitesync ]; then
	echo apptsitesync ..
	chmod -R 700 apptsitesync/supervise apptsitesync/bin
	chmod 600 apptsitesync/bin/*.php
	mkdir -pv /authentxlogs/appts2/apptsitesync_${APPNAME}
	ln -s /authentx/app/https/${APPNAME}/appts2/apptsitesync/bin/appsitesyncctrl /usr/local/bin/apptsitesyncctrl_${APPNAME}
	echo done
fi

# dbpurge
if [ -d dbpurge ]; then
	echo dbpurge ..
	chmod -R 700 dbpurge/supervise dbpurge/bin
	chmod 600 dbpurge/bin/*.php
	mkdir -pv /authentxlogs/appts2/dbpurge_${APPNAME}
	ln -s /authentx/app/https/${APPNAME}/appts2/dbpurge/bin/apptdbpurgectrl /usr/local/bin/apptdbpurgectrl_${APPNAME}
	echo done
fi

# reminder
if [ -d reminder ]; then
	echo reminder ..
	chmod -R 700 reminder/supervise reminder/bin
	chmod 600 reminder/bin/*.php
	mkdir -pv /authentxlogs/appts2/reminder_${APPNAME}
	ln -s /authentx/app/https/${APPNAME}/appts2/reminder/bin/apptreminderctrl /usr/local/bin/apptreminderctrl_${APPNAME}
	echo done
fi

# replication
if [ -d replication ]; then
	echo replication ..
	chmod 755 replication/config
	chmod 644 replication/config/*.php
	
	chmod -R 700 replication/consumer/supervise replication/consumer/bin
	chmod 600 replication/consumer/bin/*.php
	mkdir -pv /authentxlogs/appts2/replication_${APPNAME}
	ln -s /authentx/app/https/${APPNAME}/appts2/replication/consumer/bin/replctrl_appts /usr/local/bin/apptreplctrl_${APPNAME}

	chmod -R 700 replication/logmanager/supervise replication/logmanager/bin
	chmod 600 replication/logmanager/bin/*.php
	mkdir -pv /authentxlogs/appts2/replogmgr_${APPNAME}
	ln -s /authentx/app/https/${APPNAME}/appts2/replication/logmanager/bin/replappts_logmgrctrl /usr/local/bin/apptlogmgrctl_${APPNAME}

	chmod 755 replication/provider
	chmod 644 replication/provider/*.php
	mkdir -pv replication/provider/cache
	chown ${OWNER_W}:${GROUP_W} replication/provider/cache
	if [ ! -f replication/provider/.htaccess ]; then
		echo
		echo "   *** MISSING: provider .htaccess ... creating ***"
		cat <<EOF >> replication/provider/.htaccess
<IfModule mod_rewrite.c>
   RewriteEngine On
   RewriteRule ^$ index.php [QSA,L]
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
</IfModule>
<ifModule mod_php.c>
   php_flag display_errors Off
</IfModule>
EOF
	fi
	chown 644 replication/provider/.htaccess
	echo done
fi

# appts2 site
	echo appts2 site ..
	chmod 644 *.php
	chmod 644 siteconfig/*.php
	chmod 644 *.gdf
	chmod 755 siteconfig useruploads
	chown ${OWNER_W}:${GROUP_W} useruploads
	echo done

# Final Notes
echo "Services requiring linking into service:"
echo "  ldap apptsitesync"
echo "  appts dbpurge"
echo "  replication consumer"
echo "  replication log manager"
echo "Consumer xticket, client certs and configuration also required."
