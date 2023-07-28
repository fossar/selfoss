import { createContext } from 'react';

export type Configuration = {
    homepage: string;
    share: string;
    wallabag: { url: string; version: number } | null;
    wordpress: string | null;
    mastodon: string | null;
    autoMarkAsRead: boolean;
    autoCollapse: boolean;
    autoStreamMore: boolean;
    openInBackgroundTab: boolean;
    loadImagesOnMobile: boolean;
    itemsPerPage: number;
    unreadOrder: string;
    autoHideReadOnMobile: boolean;
    scrollToArticleHeader: boolean;
    showThumbnails: boolean;
    htmlTitle: string;
    allowPublicUpdate: boolean;
    publicMode: boolean;
    authEnabled: boolean;
    readingSpeed: number | null;
    language: string | null;
    userCss: number | null;
    userJs: number | null;
};

export const ConfigurationContext: React.Context<Configuration> =
    createContext(undefined);
