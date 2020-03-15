#!/bin/bash
WP_BASEURL="--- REAL URL OF YOUR WEBSITE---"
echo '--- DEPLOYMENT TO AZURE STORAGE STARTED ---'

#get also 404 page
wget -O '/var/www/staticexport/$web/404.html' --no-check-certificate --content-on-error https://localhost/404
sed "s/localhost/${WP_BASEURL}/g" -i '/var/www/staticexport/$web/404.html'

#sync Azure storage
/usr/bin/azcopy sync '/var/www/staticexport/$web/.' '--AZURE STORAGE URL WTH SAS TOKEN---' --delete-destination=true

echo "Deleting static export folder..."
rm -v -r '/var/www/staticexport/$web'
echo "--- DEPLOYMENT FINISHED ---"
