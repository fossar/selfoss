selfoss
http://selfoss.aditu.de
tobias.zeising@aditu.de
Version 1.0beta
License: GPLv3
Icon Source: http://blog.artcore-illustrations.de/aicons/


-------
english
-------

INSTALLATION

1. upload all files of this folder (IMPORTANT: also upload the invisible .htaccess files)
2. make the directories data/cache, data/icons, data/logs, data/thumbnails writeable
3. insert database access data in config.ini
4. create cronjob for updating feeds and point it to http://<selfoss url>/update via wget or curl.

----

UPDATE

1. backup your database and your "data" folder
2. (IMPORTANT: don't delete the "data" folder) delete all old files and folders excluding the folder "data"
3. upload all new files and folders excluding the data folder (IMPORTANT: also upload the invisible .htaccess files)
4. Clean your browser cache
5. insert darabase access data in config.ini (use your old database connection)



-------
deutsch
-------

INSTALLATION

1. lade alle Dateien dieses Ordners hoch (WICHTIG: auch die unsichtbaren .htaccess Dateien hochladen)
2. setze die Schreibrechte für die Verzeichnisse data/cache, data/icons, data/logs, data/thumbnails 
3. setze deine Datenbankzugriffsdaten in config.ini
4. erzeuge einen cronjob für das Aktualisieren der Feeds auf http://<selfoss url>/update mittels wget or curl.



----

UPDATE

1. die Datenbank sowie den "data" Ordner sichern
2. (WICHTIG: nicht den "data" Ordner löschen) alle alten Dateien und Ordnern (einschließlich "config") aber ohne dem Ordner "data" löschen
3. alle neuen Dateien und Ordner hochladen (ausgenommen dem "data" Ordner) (WICHTIG: auch die unsichtbaren .htaccess Dateien hochladen)
4. Leere den Cache des Browsers
5. Datenbankzugriff in der config.ini konfigurieren (die alte Datenbank für die neue Version verwenden)

