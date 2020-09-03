#!/bin/bash

version="/var/www/manage/storage/app/hhk/$1/"
site="/var/www/html/hhk/$2"

echo "Version: $version"
echo "Site: $site"

# check if version exists
if [ ! -d $version ]
then
	echo "The version does not exist...exiting"
	exit
fi

# check if site exists
if [ ! -d $site ]
then
	echo "The site does not exist...exiting"
	exit
fi

rsync="rsync -acO --delete --exclude=.git --exclude=install --exclude=conf"

if [ ! -d "${site}/house" ]; then
	rsync+= " --exclude=house"
fi

if [ ! -d "${site}/volunteer" ]; then
	rsync+= " --exclude=volunteer"
fi

rsync+=" $version $site"

#echo "Rsync command: $rsync"

$($rsync)
$(chown hhkapp:webdev $site -R)
$(chmod 775 $site -R)
