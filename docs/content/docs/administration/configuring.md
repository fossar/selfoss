+++
title = "Configuring"
weight = 30
+++

All [configuration options](@/docs/administration/options.md) are optional. Any settings in `config.ini` will override the settings in `src/helpers/Configuration.php`. For convenience, the archive includes `config-example.ini` file containing the default configuration exported in INI format. To customize settings follow these instructions:

1. Rename `config-example.ini` to `config.ini`.
2. Edit `config.ini` and delete any lines you do not wish to override.

Sample `config.ini` file which provides password protection:

```ini
username=secretagent
password=$2y$10$xLurmBB0HJ60.sar1Z38r.ajtkruUIay7rwFRCvcaDl.1EU4epUH6
```

Sample `config.ini` file with a MySQL database connection:

```ini
db_type=mysql
db_host=localhost
db_database=selfoss
db_username=secretagent
db_password=life0fD4ng3r
```
