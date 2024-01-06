import sys
import threading
from http.server import BaseHTTPRequestHandler, HTTPServer
from .feeds.fibonacci import numbers_feed


FIBONACCI_FEED_LENGTH = 20


class DataServer(BaseHTTPRequestHandler):
    """
    A web server returning various feeds for selfoss to fetch.
    """

    def do_GET(self):
        self.send_response(200)
        self.send_header("Content-type", "application/rss+xml")
        self.end_headers()
        # Currently, it will only send a feed listing first few fibonacci numbers.
        self.wfile.write(numbers_feed(FIBONACCI_FEED_LENGTH).encode("utf-8"))


class DataServerThread(threading.Thread):
    """
    A thread that starts and stops `DataServer`.
    """

    def __init__(self, host_name="localhost", port=8000):
        super().__init__()
        self.host_name = host_name
        self.port = port

    def run(self):
        with HTTPServer((self.host_name, self.port), DataServer) as self.web_server:
            print(
                f"selfoss server started http://{self.host_name}:{self.port}",
                file=sys.stderr,
            )

            self.web_server.serve_forever()

        print("selfoss server stopped.", file=sys.stderr)

    def stop(self):
        self.web_server.shutdown()
