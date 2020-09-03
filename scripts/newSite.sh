#!/bin/bash

copyFiles() {
	rsync="rsync -acO --exclude=.git $version $site"
	
	#echo "Rsync command: $rsync"
	
	$($rsync)
	$(chown hhkapp:webdev $site -R)
	$(chmod 775 $site -R)
	$(chmod 777 $site/conf -R)
}

pwd=""
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
if [ -d "$site/conf" ]
then
	echo "The site already exists...exiting"
	exit
fi

copyFiles
