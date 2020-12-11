import time
import unittest
from pathlib import Path
from .data_server import DataServerThread
from .selfoss_server import SelfossServerThread


class SelfossIntegration(unittest.TestCase):
    '''
    Base class for selfoss integration tests.
    It starts selfoss server and a server providing test feeds.
    '''
    def setUp(self):
        current_dir = Path(__file__).parent.absolute()

        self.data_host_name = 'localhost'
        self.data_port = 8080
        self.selfoss_host_name = 'localhost'
        self.selfoss_port = 8081
        self.selfoss_username = 'admin'
        self.selfoss_password = 'hunter2'

        self.selfoss_root = current_dir.parent.parent.parent

        self.selfoss_thread = SelfossServerThread(
            selfoss_root=self.selfoss_root,
            password=self.selfoss_password,
            username=self.selfoss_username,
            host_name=self.selfoss_host_name,
            port=self.selfoss_port,
        )
        self.selfoss_thread.start()

        self.data_server_thread = DataServerThread(
            host_name=self.data_host_name,
            port=self.data_port,
        )
        self.data_server_thread.start()

        # Wait for the servers to become properly initialized.
        time.sleep(2)

    def tearDown(self):
        self.selfoss_thread.stop()
        self.data_server_thread.stop()

