+++
title = "Configuring"
weight = 30
+++

selfoss can be configured using a configuration file or environment variables. All [configuration options](@/docs/administration/options.md) are optional.

## Using a file {#file}

This is the easiest way. Just create a `config.ini` file in the top-level selfoss directory and include any options you want to override.

The file uses [PHP’s variant](https://www.php.net/manual/en/function.parse-ini-file.php#refsect1-function.parse-ini-file-examples) of the [INI format](https://en.wikipedia.org/wiki/INI_file): each option is set on a separate line consisting of an option name, followed by `=` and an option value. Lines starting with `;` or empty lines will be ignored. If an option value contains any special characters such as `?{}|&~!()^"`, e.g. for a database password, it will need to be quoted like `db_password="life0fD4ng3r!"`.

For convenience, the release archive includes `config-example.ini` file containing the default configuration exported in INI format. To customize the settings, you can:

1. Rename `config-example.ini` to `config.ini`.
2. Edit `config.ini` and delete any lines you do not wish to override.

### Sample `config.ini` file which provides password protection

```ini
username=secretagent
password=$2y$10$xLurmBB0HJ60.sar1Z38r.ajtkruUIay7rwFRCvcaDl.1EU4epUH6
```

### Sample `config.ini` file with a MySQL database connection

```ini
db_type=mysql
db_host=localhost
db_database=selfoss
db_username=secretagent
db_password=life0fD4ng3r
```


## Using environment variables {#env}

For environments such as Docker containers or <abbr title="Software-as-a-service">SaaS</abbr> inspired by [Twelve-Factor app](https://12factor.net/), where a persistent file system might not be available, you can also configure selfoss using environment variables.

The variables should start with a `SELFOSS_` prefix and be in all-caps. For example, if you want to set `auto_mark_as_read` option, use `SELFOSS_AUTO_MARK_AS_READ` variable.
