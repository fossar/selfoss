import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import selfoss from './selfoss-base';
import * as clipboard from 'clipboard-polyfill';
import * as icons from './icons';

selfoss.shares = {
    initialized: false,
    sharers: {},
    names: {},
    enabledShares: [],

    /**
     * Initialize enabled sharers.
     * @param !string sharers enabled on the server
     */
    init(enabledShares) {
        this.enabledShares = Array.from(enabledShares);
        this.initialized = true;

        if ('share' in navigator) {
            selfoss.shares.register('share', selfoss.app._('share_native_label'), 'a', <FontAwesomeIcon icon={icons.share} />, ({url, title}) => {
                navigator.share({
                    title,
                    url
                }).catch((e) => {
                    if (e.name === 'AbortError') {
                        selfoss.app.showError(selfoss.app._('error_share_native_abort'));
                    } else {
                        selfoss.app.showError(selfoss.app._('error_share_native'));
                    }
                });
            });
        }

        this.register('diaspora', selfoss.app._('share_diaspora_label'), 'd', <FontAwesomeIcon icon={icons.diaspora} />, ({url, title}) => {
            window.open('https://share.diasporafoundation.org/?url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title), undefined, 'noreferrer');
        });
        this.register('twitter', selfoss.app._('share_twitter_label'), 't', <FontAwesomeIcon icon={icons.twitter} />, ({url, title}) => {
            window.open('https://twitter.com/intent/tweet?source=webclient&text=' + encodeURIComponent(title) + ' ' + encodeURIComponent(url), undefined, 'noreferrer');
        });
        this.register('facebook', selfoss.app._('share_facebook_label'), 'f', <FontAwesomeIcon icon={icons.facebook} />, ({url, title}) => {
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title), undefined, 'noreferrer');
        });
        this.register('pocket', selfoss.app._('share_pocket_label'), 'p', <FontAwesomeIcon icon={icons.pocket} />, ({url, title}) => {
            window.open('https://getpocket.com/save?url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title), undefined, 'noreferrer');
        });

        if (selfoss.config.wallabag !== null) {
            this.register('wallabag', selfoss.app._('share_wallabag_label'), 'w', <FontAwesomeIcon icon={icons.wallabag} />, ({url}) => {
                if (selfoss.config.wallabag.version === 2) {
                    window.open(selfoss.config.wallabag.url + '/bookmarklet?url=' + encodeURIComponent(url), undefined, 'noreferrer');
                } else {
                    window.open(selfoss.config.wallabag.url + '/?action=add&url=' + btoa(url), undefined, 'noreferrer');
                }
            });
        }

        if (selfoss.config.wordpress !== null) {
            this.register('wordpress', selfoss.app._('share_wordpress_label'), 's', <FontAwesomeIcon icon={icons.wordpress} />, ({url, title}) => {
                window.open(selfoss.config.wordpress + '/wp-admin/press-this.php?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title), undefined, 'noreferrer');
            });
        }

        this.register('mail', selfoss.app._('share_mail_label'), 'e', <FontAwesomeIcon icon={icons.email} />, ({url, title}) => {
            document.location.href = 'mailto:?body=' + encodeURIComponent(url) + '&subject=' + encodeURIComponent(title);
        });

        this.register('copy', selfoss.app._('share_copy_label'), 'c', <FontAwesomeIcon icon={icons.copy} />, ({url}) => {
            clipboard.writeText(url).then(() => {
                selfoss.app.showMessage(selfoss.app._('info_url_copied'));
            });
        });
    },

    register(name, label, id, icon, sharer) {
        if (!this.initialized) {
            return false;
        }
        this.sharers[name] = {
            name,
            label,
            id,
            icon,
            callback: sharer
        };
        this.names[id] = name;
        return true;
    },

    getAll() {
        return this.enabledShares.filter(id => id in this.names).map(id => this.sharers[this.names[id]]);
    },

    share(name, {id, url, title}) {
        this.sharers[name].callback({id, url, title});
    }
};
