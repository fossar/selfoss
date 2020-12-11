import requests


class SelfossApi:
    def __init__(self, base_uri: str):
        self.base_uri = base_uri
        # We still use cookies for authentication so let’s persist them across requests.
        self.session = requests.Session()

    def login(self, username, password):
        r = self.session.post(
            f'{self.base_uri}/login',
            data={
                'username': username,
                'password': password,
            },
        )
        r.raise_for_status()

        return r.json()

    def logout(self):
        r = self.session.get(
            f'{self.base_uri}/logout',
        )
        r.raise_for_status()

        return r.json()

    def get_items(self):
        r = self.session.get(
            f'{self.base_uri}/items',
        )
        r.raise_for_status()

        return r.json()

    def add_source(self, spout: str, **params):
        r = self.session.post(
            f'{self.base_uri}/source',
            data={
                **params,
                'spout': spout,
            },
        )
        r.raise_for_status()

        return r.json()

    def refresh_all(self):
        r = self.session.get(
            f'{self.base_uri}/update',
        )
        r.raise_for_status()

        return r.text
