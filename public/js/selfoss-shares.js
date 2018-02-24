selfoss.shares = {
    initialized: false,
    sharers: {},
    names: {},
    enabledShares: '',

    init: function(enabledShares) {
        this.enabledShares = enabledShares;
        this.initialized = true;

        this.register('delicious', 'd', function(url, title) {
            window.open('https://delicious.com/save?url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title));
        });
        this.register('googleplus', 'g', function(url) {
            window.open('https://plus.google.com/share?url=' + encodeURIComponent(url));
        });
        this.register('twitter', 't', function(url, title) {
            window.open('https://twitter.com/intent/tweet?source=webclient&text=' + encodeURIComponent(title) + ' ' + encodeURIComponent(url));
        });
        this.register('facebook', 'f', function(url, title) {
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title));
        });
        this.register('pocket', 'p', function(url, title) {
            window.open('https://getpocket.com/save?url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title));
        });
        this.register('wallabag', 'w', function(url) {
            if ($('#config').data('wallabag_version') == 2) {
                window.open($('#config').data('wallabag') + '/bookmarklet?url=' + encodeURIComponent(url));
            } else {
                window.open($('#config').data('wallabag') + '/?action=add&url=' + btoa(url));
            }
        });
        this.register('wordpress', 's', function(url, title) {
            window.open($('#config').data('wordpress') + '/wp-admin/press-this.php?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title));
        });
        this.register('mail', 'e', function(url, title) {
            document.location.href = 'mailto:?body=' + encodeURIComponent(url) + '&subject=' + encodeURIComponent(title);
        });
    },

    register: function(name, id, sharer) {
        if (!this.initialized) {
            return false;
        }
        this.sharers[name] = sharer;
        this.names[id] = name;
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
    }
};
