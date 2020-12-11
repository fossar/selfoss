import bcrypt
import os
import subprocess
import tempfile
import threading
from pathlib import Path


class SelfossServerThread(threading.Thread):
    '''
    A thread that starts and stops PHP’s built-in web server running selfoss.
    '''
    def __init__(self, selfoss_root: Path, username: str, password: str, host_name: str, port: int):
        super().__init__()
        self.selfoss_root = selfoss_root
        self.username = username
        self.password = password
        self.host_name = host_name
        self.port = port

    def run(self):
        with tempfile.TemporaryDirectory() as temp_dir:
            # Set up data directories.
            temp_dir = Path(temp_dir)
            data_dir = temp_dir / 'data'
            (data_dir / 'sqlite').mkdir(parents=True)
            (data_dir / 'thumbnails').mkdir(parents=True)
            (data_dir / 'favicons').mkdir(parents=True)

            # Configure selfoss using environment variables for convenience.
            test_env = {
                **os.environ,
                'SELFOSS_DATADIR': data_dir,
                'SELFOSS_LOGGER_DESTINATION': 'error_log',
                'SELFOSS_USERNAME': self.username,
                'SELFOSS_PASSWORD': bcrypt.hashpw(self.password.encode('utf-8'), bcrypt.gensalt()),
                'SELFOSS_DB_TYPE': 'sqlite',
                'SELFOSS_PUBLIC': '1',
                'SELFOSS_LOGGER_LEVEL': 'DEBUG',
            }

            current_dir = Path(__file__).parent.absolute()

            php_command = [
                'php',
                # We need to enable reading environment variables.
                '-d', 'variables_order=EGPCS',
                '-S', f'{self.host_name}:{self.port}',
                '-c', current_dir / 'php.ini',
                self.selfoss_root / 'run.php',
            ]

            # Create the subprocess.
            self.proc = subprocess.Popen(
                php_command,
                env=test_env,
                cwd=self.selfoss_root,
            )

            # Wait for it to finish.
            self.proc.communicate()

    def stop(self):
        self.proc.kill()
