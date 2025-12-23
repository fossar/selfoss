import React, { useMemo } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import map from 'ramda/src/map';
import selfoss from './selfoss-base';
import * as icons from './icons';
import { Configuration } from './model/Configuration';
import { Translate } from './helpers/i18n';

export type Sharer = {
    label: string;
    icon: string | React.JSX.Element;
    action: (params: { url: string; title: string }) => void;
    available?: boolean;
};

export type EnabledSharer = {
    key: string;
    label: string;
    icon: string | React.JSX.Element;
    action: (params: { url: string; title: string }) => void;
};

function materializeSharerIcon(sharer: Sharer): Sharer {
    const { icon } = sharer;
    return {
        ...sharer,
        // We want to allow people to use <svg> or <img> in user.js
        icon:
            typeof icon === 'string' && icon.includes('<') ? (
                <span dangerouslySetInnerHTML={{ __html: icon }} />
            ) : (
                icon
            ),
    };
}

export function useSharers(args: {
    configuration: Configuration;
    showError: (message: string) => void;
    _: Translate;
}): Array<EnabledSharer> {
    const { configuration, showError, _ } = args;

    return useMemo((): Array<EnabledSharer> => {
        const availableSharers: { [key: string]: Sharer } = {
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
                                showError(_('error_share_native_abort'));
                            } else {
                                showError(_('error_share_native'));
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
    }, [configuration, showError, _]);
}
