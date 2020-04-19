#!/bin/bash
WP_BASEURL="--- REAL URL OF YOUR WEBSITE---"
SERVER_DIR="/var/www/staticexport/"
PREVIEW_URL="--- PREVIEW URL OF YOUR WP WEBSITE---"

echo "--- DEPLOYMENT TO AZURE STORAGE STARTED ---"
echo "-- GATHER 404 file";
#get also 404 page
wget -O ${SERVER_DIR}"\$web/404.html" --no-check-certificate --content-on-error https://localhost/404
sed "s/localhost/${WP_BASEURL}/g" -i ${SERVER_DIR}"\$web/404.html"
#be sure that all URLs  has been rewritten
sed "s/${PREVIEW_URL}/${WP_BASEURL}/g" -i ${SERVER_DIR}"\$web/404.html"

echo "-- SYNC ALL STATIC FILES"
#sync Azure storage
/usr/bin/azcopy sync ${SERVER_DIR}"\$web/." '--AZURE STORAGE URL WTH SAS TOKEN---' --delete-destination=true


echo "-- SYNC ALL RSS FILES";
RSS_files="";
pushd $SERVER_DIR"\$web"

for f in $( find .  -path "*/feed/index.html" -type f -name "*.html"  -printf "%P\n" );
do
  RSS_files=${RSS_files}${f}';'
done

#creating RSS feeds 
#SAS URI will look like https://something.blob.core.windows.net/$web/../?sv=2019-02-02&ss=b......
/usr/bin/azcopy copy '.'  '--AZURE STORAGE URL WTH SAS TOKEN VERSION WITH .. IN URL---' --content-type "application/rss+xml" --include-path $


echo "Deleting static export folder..."
rm -v -r $SERVER_DIR"\$web"
echo "--- DEPLOYMENT FINISHED ---"
