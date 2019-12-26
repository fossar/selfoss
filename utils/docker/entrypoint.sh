#!/bin/bash

# Make data directories owned by Apache
chown -R www-data:www-data \
    /var/www/html/data/cache \
    /var/www/html/data/favicons \
    /var/www/html/data/logs \
    /var/www/html/data/thumbnails \
    /var/www/html/data/sqlite

# Create a config file when one does not exist
if [[ ! -f config/config.ini ]]; then
    cp defaults.ini config/config.ini \
    && sed -i 's#^logger_destination=.*#logger_destination=file:php://stderr#' config/config.ini
fi

# Run updater process periodically
su www-data -s /bin/bash -c 'php /var/www/html/cliupdate.php' >/dev/null 2>&1
(while true; do su www-data -s /bin/bash -c 'php /var/www/html/cliupdate.php'; sleep 900; done;) &

# Start the server
apache2-foreground