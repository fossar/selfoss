import requests
import unittest
from helpers.data_server import FIBONACCI_FEED_LENGTH
from helpers.integration import SelfossIntegration
from helpers.selfoss_api import SelfossApi


class BasicWorkflowTest(SelfossIntegration):
    def test_basic_workflow(self):
        selfoss_base_uri = f"http://{self.selfoss_host_name}:{self.selfoss_port}"
        selfoss_api = SelfossApi(selfoss_base_uri)

        items = selfoss_api.get_items()
        assert len(items) == 0, "New selfoss instance should have no items."

        fibonacci_feed_uri = (
            f"http://{self.data_host_name}:{self.data_port}/fibonacci.xml"
        )

        try:
            add_feed = selfoss_api.add_source(
                "spouts\\rss\\feed",
                url=fibonacci_feed_uri,
            )
            assert (
                False
            ), "Adding source is privileged operation and should fail without login."
        except requests.exceptions.HTTPError as e:
            assert (
                e.response.status_code == 403
            ), "Adding source should require authentication."

        login = selfoss_api.login(self.selfoss_username, self.selfoss_password)
        assert login[
            "success"
        ], f'Authentication should succeed but it failed with {login["error"]}.'

        add_feed = selfoss_api.add_source("spouts\\rss\\feed", url=fibonacci_feed_uri)
        assert add_feed["success"], "Adding source should succeed."
        assert (
            add_feed["title"] == "20 numbers"
        ), "Source should auto-detect feed title."

        refresh = selfoss_api.refresh_all()
        assert refresh == "finished", "Refreshing sources should succeed."

        items = selfoss_api.get_items()
        assert (
            len(items) == FIBONACCI_FEED_LENGTH
        ), "After updating sources, there should be all items from the sources."
        assert items[0]["unread"], "Items should start as unread"
        assert not items[0]["starred"], "Items should start as not starred"

        assert selfoss_api.mark_read(items[0]["id"]), "Unable to mark item as read"
        assert selfoss_api.mark_starred(items[0]["id"]), "Unable to starr item"
        items = selfoss_api.get_items()
        assert not items[0]["unread"], "First item should now be marked as read"
        assert items[0]["starred"], "First item should now be starred"

        items = selfoss_api.get_items(search="3")
        assert (
            len(items) == 5
        ), "Search should find five fibonacci sequence numbers containing the digit 3"


if __name__ == "__main__":
    unittest.main()
