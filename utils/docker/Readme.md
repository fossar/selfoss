## Dockerizing selfoss

There are two Dockerfiles bundled in the repository - one for development, one for production deployment.

To build the production container use:
```
docker-compose build --no-cache --pull
```

Then run it with:
```
docker-compose up
```

Selfoss web interface will be available at http://localhost:8390

Selfoss config is mounted in a separate volume, so your custom settings should survive reboot.

To run the development container first copy .env.dist into .env and make sure the UID and GID matches your own user and group ID, otherwise the dev scripts will create files with wrong access rights.
```
cat .env.dist | sed 's/UID=1000/UID='$(id -u)'/' | sed 's/GID=1000/GID='$(id -g)'/' > .env
```
Then build and run the dev container:
```
docker-compose -f docker-compose.dev.yml build --no-cache --pull
docker-compose -f docker-compose.dev.yml run --rm -u node app npm run postinstall
docker-compose -f docker-compose.dev.yml up
```
Dev Selfoss web interface will be available at http://localhost:8391, and you can run all the dev scripts like this: `docker-compose exec -u node npm run check` or simply jump into bash inside the container: `docker-compose exec -u node bash`. That's it, you can start developing!
