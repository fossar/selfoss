import React, { useMemo } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import map from 'ramda/src/map.js';
import selfoss from './selfoss-base';
import * as icons from './icons';

function materializeSharerIcon(sharer) {
    const { icon } = sharer;
    return {
        ...sharer,
        icon:
            typeof icon === 'string' && icon.includes('<') ? (
                <span dangerouslySetInnerHTML={{ __html: icon }} />
            ) : (
                icon
            ),
    };
}

export function useSharers({ configuration, _ }) {
    return useMemo(() => {
        const availableSharers = {
            a: {
                label: _('share_native_label'),
                icon: <FontAwesomeIcon icon={icons.share} />,
                action: ({ url, title }) => {
                    navigator
                        .share({
                            title,
                            url,
                        })
                        .catch((e) => {
                            if (e.name === 'AbortError') {
                                selfoss.app.showError(
                                    _('error_share_native_abort'),
                                );
                            } else {
                                selfoss.app.showError(_('error_share_native'));
                            }
                        });
                },
                available: 'share' in navigator,
            },

            d: {
                label: _('share_diaspora_label'),
                icon: <FontAwesomeIcon icon={icons.diaspora} />,
                action: ({ url, title }) => {
                    window.open(
                        'https://share.diasporafoundation.org/?url=' +
                            encodeURIComponent(url) +
                            '&title=' +
                            encodeURIComponent(title),
                        undefined,
                        'noreferrer',
                    );
                },
            },

            t: {
                label: _('share_twitter_label'),
                icon: <FontAwesomeIcon icon={icons.twitter} />,
                action: ({ url, title }) => {
                    window.open(
                        'https://twitter.com/intent/tweet?source=webclient&text=' +
                            encodeURIComponent(title) +
                            ' ' +
                            encodeURIComponent(url),
                        undefined,
                        'noreferrer',
                    );
                },
            },

            f: {
                label: _('share_facebook_label'),
                icon: <FontAwesomeIcon icon={icons.facebook} />,
                action: ({ url, title }) => {
                    window.open(
                        'https://www.facebook.com/sharer/sharer.php?u=' +
                            encodeURIComponent(url) +
                            '&t=' +
                            encodeURIComponent(title),
                        undefined,
                        'noreferrer',
                    );
                },
            },

            m: {
                label: _('share_mastodon_label'),
                icon: <FontAwesomeIcon icon={icons.mastodon} />,
                action: ({ url, title }) => {
                    window.open(
                        configuration.mastodon +
                            '/share?text=' +
                            encodeURIComponent('"' + title + '"\n' + url),
                        undefined,
                        'noreferrer',
                    );
                },
                available: configuration.mastodon !== null,
            },

            p: {
                label: _('share_pocket_label'),
                icon: <FontAwesomeIcon icon={icons.pocket} />,
                action: ({ url, title }) => {
                    window.open(
                        'https://getpocket.com/save?url=' +
                            encodeURIComponent(url) +
                            '&title=' +
                            encodeURIComponent(title),
                        undefined,
                        'noreferrer',
                    );
                },
            },

            w: {
                label: _('share_wallabag_label'),
                icon: <FontAwesomeIcon icon={icons.wallabag} />,
                action: ({ url }) => {
                    if (configuration.wallabag.version === 2) {
                        window.open(
                            configuration.wallabag.url +
                                '/bookmarklet?url=' +
                                encodeURIComponent(url),
                            undefined,
                            'noreferrer',
                        );
                    } else {
                        window.open(
                            configuration.wallabag.url +
                                '/?action=add&url=' +
                                btoa(url),
                            undefined,
                            'noreferrer',
                        );
                    }
                },
                available: configuration.wallabag !== null,
            },

            s: {
                label: _('share_wordpress_label'),
                icon: <FontAwesomeIcon icon={icons.wordpress} />,
                action: ({ url, title }) => {
                    window.open(
                        configuration.wordpress +
                            '/wp-admin/press-this.php?u=' +
                            encodeURIComponent(url) +
                            '&t=' +
                            encodeURIComponent(title),
                        undefined,
                        'noreferrer',
                    );
                },
                available: configuration.wordpress !== null,
            },

            e: {
                label: _('share_mail_label'),
                icon: <FontAwesomeIcon icon={icons.email} />,
                action: ({ url, title }) => {
                    document.location.href =
                        'mailto:?body=' +
                        encodeURIComponent(url) +
                        '&subject=' +
                        encodeURIComponent(title);
                },
            },

            c: {
                label: _('share_copy_label'),
                icon: <FontAwesomeIcon icon={icons.copy} />,
                action: ({ url }) => {
                    navigator.clipboard.writeText(url).then(() => {
                        selfoss.app.showMessage(_('info_url_copied'));
                    });
                },
            },

            ...map(materializeSharerIcon, selfoss.customSharers ?? {}),
        };

        const enabledSharers = [];
        for (const letter of configuration.share) {
            const sharer = availableSharers[letter];
            if (sharer !== undefined && (sharer.available ?? true)) {
                const { label, icon, action } = sharer;
                enabledSharers.push({
                    key: letter,
                    label,
                    icon,
                    action,
                });
            }
        }

        return enabledSharers;
    }, [configuration, _]);
}
