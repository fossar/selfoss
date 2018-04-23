import selfoss from './selfoss-base';
import * as clipboard from 'clipboard-polyfill';

selfoss.shares = {
    initialized: false,
    sharers: {},
    names: {},
    icons: {},
    enabledShares: '',

    init: function(enabledShares) {
        this.enabledShares = enabledShares;
        this.initialized = true;

        if ('share' in navigator) {
            selfoss.shares.register('share', 'a', 'fas fa-share-alt', (url, title) => {
                navigator.share({
                    title,
                    url
                }).catch((e) => {
                    if (e.name === 'AbortError') {
                        selfoss.ui.showError(selfoss.ui._('error_share_native_abort'));
                    } else {
                        selfoss.ui.showError(selfoss.ui._('error_share_native'));
                    }
                });
            });
        }

        this.register('diaspora', 'd', 'fab fa-diaspora', function(url, title) {
            window.open('https://share.diasporafoundation.org/?url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title));
        });
        this.register('twitter', 't', 'fab fa-twitter', function(url, title) {
            window.open('https://twitter.com/intent/tweet?source=webclient&text=' + encodeURIComponent(title) + ' ' + encodeURIComponent(url));
        });
        this.register('facebook', 'f', 'fab fa-facebook-square', function(url, title) {
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title));
        });
        this.register('pocket', 'p', 'fab fa-get-pocket', function(url, title) {
            window.open('https://getpocket.com/save?url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title));
        });

        if (selfoss.config.wallabag !== null) {
            this.register('wallabag', 'w', 'fac fa-wallabag', function(url) {
                if (selfoss.config.wallabag.version === 2) {
                    window.open(selfoss.config.wallabag.url + '/bookmarklet?url=' + encodeURIComponent(url));
                } else {
                    window.open(selfoss.config.wallabag.url + '/?action=add&url=' + btoa(url));
                }
            });
        }

        if (selfoss.config.wordpress !== null) {
            this.register('wordpress', 's', 'fab fa-wordpress-simple', function(url, title) {
                window.open(selfoss.config.wordpress + '/wp-admin/press-this.php?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title));
            });
        }

        this.register('mail', 'e', 'fas fa-envelope', function(url, title) {
            document.location.href = 'mailto:?body=' + encodeURIComponent(url) + '&subject=' + encodeURIComponent(title);
        });

        this.register('copy', 'c', 'fas fa-copy', (url) => {
            clipboard.writeText(url).then(() => {
                selfoss.ui.showMessage(selfoss.ui._('info_url_copied'));
            });
        });
    },

    register: function(name, id, icon, sharer) {
        if (!this.initialized) {
            return false;
        }
        this.sharers[name] = sharer;
        this.names[id] = name;
        this.icons[name] = this.fontawesomeIcon(icon);
        return true;
    },

    getAll: function() {
        var allNames = [];
        if (this.enabledShares != null) {
            for (var i = 0; i < this.enabledShares.length; i++) {
                var enabledShare = this.enabledShares[i];
                if (enabledShare in this.names) {
                    allNames.push(this.names[enabledShare]);
                }
            }
        }
        return allNames;
    },

    share: function(name, url, title) {
        this.sharers[name](url, title);
    },

    buildLinks: function(shares, linkBuilder) {
        var links = '';
        if (shares != null) {
            for (var i = 0; i < shares.length; i++) {
                var name = shares[i];
                links += linkBuilder(name);
            }
        }
        return links;
    },

    fontawesomeIcon: function(service) {
        return '<i class="' + service + '"></i>';
    }
};
