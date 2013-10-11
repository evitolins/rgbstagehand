#!/bin/bash

origfile=/var/www/deploy/templates/virtualhost.apache
newfile=/etc/apache2/sites-available/$1
port=$1
path=/var/www/$1

# Error: Cancel if no arguments are found.
if [ $# -eq 0 ] ; then
    echo 'You need to enter the correct arguments (filename port path)'
    exit 0
fi

# Error: Cancel if virtual host file already exists
if [ -r $newfile ] ; then
    echo 'A virtual host file already exists ()'
        exit 0
fi

#Create directory from path, if it doesn't exist
mkdir -p "$path"

cp $origfile $newfile
sed -i -e "s|{{{PORT}}}|$port|g;s|{{{PATH}}}|$path|g" $newfile
echo "Created a Virtual Host File $newfile ($port => $path)"
cat $newfile

# Enable site and reboot Apache
sudo a2ensite $1
sudo /etc/init.d/apache2 restart
