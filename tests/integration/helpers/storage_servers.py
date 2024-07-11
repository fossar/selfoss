import argparse
import abc
import signal
import subprocess
import sys
import tempfile
import time
from abc import ABC
from pathlib import Path
from threading import Event


class Storage(ABC):
    pass


class MySQL(Storage):
    def __init__(self):
        # Set up data directories.
        self.temp_dir = tempfile.TemporaryDirectory()
        temp_dir = Path(self.temp_dir.name)
        self.db_dir = temp_dir / "data"
        self.db_dir.mkdir()
        self.db_dir.chmod(0o750)

        self.socket_path = temp_dir / "mysqld.sock"
        self.user = "selfoss"
        self.password = "password"
        self.database = "selfoss"

    def start(self):
        subprocess.check_call(
            [
                "mysql_install_db",
                # Prevent defaulting to --user=mysql.
                "--no-defaults",
                f"--datadir={self.db_dir}",
            ]
        )

        # Start the server
        subprocess.check_call(
            [
                "mysqld_safe",
                # Prevent trying to use /var/log for logs.
                "--no-defaults",
                f"--datadir={self.db_dir}",
                f"--socket={self.socket_path}",
                "--skip-networking",
                "--no-auto-restart",
            ]
        )

        # Create user and database.
        # Waiting does not seem to work.
        time.sleep(2)
        subprocess.check_call(
            [
                "mysql",
                "--wait",
                f"--socket={self.socket_path}",
                f"--execute=CREATE USER '{self.user}'@'localhost' IDENTIFIED BY '{self.password}';",
            ]
        )
        subprocess.check_call(
            [
                "mysql",
                "--wait",
                f"--socket={self.socket_path}",
                f"--execute=CREATE DATABASE {self.database};",
            ]
        )
        subprocess.check_call(
            [
                "mysql",
                "--wait",
                f"--socket={self.socket_path}",
                f"--execute=GRANT ALL PRIVILEGES ON *.* TO '{self.user}'@'localhost';",
            ]
        )

    def stop(self):
        subprocess.check_call(
            ["mysqladmin", f"--socket={self.socket_path}", "shutdown"]
        )
        self.temp_dir.cleanup()

    def get_config(self):
        return {
            "db_type": "mysql",
            "db_socket": self.socket_path,
            "db_username": self.user,
            "db_password": self.password,
            "db_database": self.database,
        }


class PostgreSQL(Storage):
    def __init__(self):
        # Set up data directories.
        self.temp_dir = tempfile.TemporaryDirectory()
        temp_dir = Path(self.temp_dir.name)
        self.db_dir = temp_dir / "data"
        self.db_dir.mkdir()
        self.db_dir.chmod(0o750)

        self.socket_dir_path = temp_dir
        self.user = "selfoss"
        self.database = "selfoss"

    def start(self):
        subprocess.check_call(["initdb", self.db_dir])

        # Start the server
        subprocess.check_call(
            [
                "pg_ctl",
                "start",
                f"--pgdata={self.db_dir}",
                # Intentionally passing options as a string
                f"--options=-k {self.socket_dir_path} -c listen_addresses=",
            ]
        )

        # Create users
        # Using a “template1” database since it is guaranteed to be present.
        subprocess.check_call(
            [
                "psql",
                f"--host={self.socket_dir_path}",
                "--dbname=template1",
                "--tuples-only",
                "--no-align",
                f'--command=CREATE USER "{self.user}"',
            ]
        )
        subprocess.check_call(
            [
                "psql",
                f"--host={self.socket_dir_path}",
                "--dbname=template1",
                "--tuples-only",
                "--no-align",
                f'--command=CREATE DATABASE "{self.database}" WITH OWNER = "{self.user}"',
            ]
        )

    def stop(self):
        subprocess.check_call(["pg_ctl", "stop", f"--pgdata={self.db_dir}"])
        self.temp_dir.cleanup()

    def get_config(self):
        return {
            "db_type": "pgsql",
            "db_host": self.socket_dir_path,
            "db_username": self.user,
            "db_database": self.database,
        }


class SQLite(Storage):
    def __init__(self):
        # Set up data directory.
        self.temp_dir = tempfile.TemporaryDirectory()
        temp_dir = Path(self.temp_dir.name)
        self.file = temp_dir / "selfoss.db"

    def start(self):
        pass

    def stop(self):
        self.temp_dir.cleanup()

    def get_config(self):
        return {
            "db_type": "sqlite",
            "db_file": self.file,
        }


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="Runs a storage server for purposes of testing",
    )
    parser.add_argument(
        "backend",
        nargs="?",
        default="sqlite",
        help="Database backend to start",
    )

    args = parser.parse_args()
    storage_backend = args.backend

    if storage_backend == "mysql":
        storage_server = MySQL()
    elif storage_backend == "postgresql":
        storage_server = PostgreSQL()
    elif storage_backend == "sqlite":
        storage_server = SQLite()
    else:
        raise Exception(f"Unknown storage backend type: {storage_backend}")

    termination_event = Event()

    def exit_gracefully(*args):
        storage_server.stop()
        termination_event.set()

    signal.signal(signal.SIGINT, exit_gracefully)
    signal.signal(signal.SIGTERM, exit_gracefully)

    storage_server.start()

    print(storage_server.get_config())

    termination_event.wait()
