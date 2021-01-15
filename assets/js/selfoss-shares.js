import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import selfoss from './selfoss-base';
import * as clipboard from 'clipboard-polyfill';

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
            selfoss.shares.register('share', selfoss.ui._('share_native_label'), 'a', <FontAwesomeIcon icon={['fas', 'share-alt']} />, ({url, title}) => {
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

        this.register('diaspora', selfoss.ui._('share_diaspora_label'), 'd', <FontAwesomeIcon icon={['fab', 'diaspora']} />, ({url, title}) => {
            window.open('https://share.diasporafoundation.org/?url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title));
        });
        this.register('twitter', selfoss.ui._('share_twitter_label'), 't', <FontAwesomeIcon icon={['fab', 'twitter']} />, ({url, title}) => {
            window.open('https://twitter.com/intent/tweet?source=webclient&text=' + encodeURIComponent(title) + ' ' + encodeURIComponent(url));
        });
        this.register('facebook', selfoss.ui._('share_facebook_label'), 'f', <FontAwesomeIcon icon={['fab', 'facebook-square']} />, ({url, title}) => {
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title));
        });
        this.register('pocket', selfoss.ui._('share_pocket_label'), 'p', <FontAwesomeIcon icon={['fab', 'get-pocket']} />, ({url, title}) => {
            window.open('https://getpocket.com/save?url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title));
        });

        if (selfoss.config.wallabag !== null) {
            this.register('wallabag', selfoss.ui._('share_wallabag_label'), 'w', <FontAwesomeIcon icon={['fac', 'wallabag']} />, ({url}) => {
                if (selfoss.config.wallabag.version === 2) {
                    window.open(selfoss.config.wallabag.url + '/bookmarklet?url=' + encodeURIComponent(url));
                } else {
                    window.open(selfoss.config.wallabag.url + '/?action=add&url=' + btoa(url));
                }
            });
        }

        if (selfoss.config.wordpress !== null) {
            this.register('wordpress', selfoss.ui._('share_wordpress_label'), 's', <FontAwesomeIcon icon={['fab', 'wordpress-simple']} />, ({url, title}) => {
                window.open(selfoss.config.wordpress + '/wp-admin/press-this.php?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title));
            });
        }

        this.register('mail', selfoss.ui._('share_mail_label'), 'e', <FontAwesomeIcon icon={['fas', 'envelope']} />, ({url, title}) => {
            document.location.href = 'mailto:?body=' + encodeURIComponent(url) + '&subject=' + encodeURIComponent(title);
        });

        this.register('copy', selfoss.ui._('share_copy_label'), 'c', <FontAwesomeIcon icon={['fas', 'copy']} />, ({url}) => {
            clipboard.writeText(url).then(() => {
                selfoss.ui.showMessage(selfoss.ui._('info_url_copied'));
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
            icon: icon,
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
