import { createContext } from 'react';

export const ConfigurationContext = createContext();

export type Configuration = {
	homepage: string,
	share: string,
	wallabag: { url: string, version: int } | null,
	wordpress: string | null,
	mastodon: string | null,
	autoMarkAsRead: boolean,
	autoCollapse: boolean,
	autoStreamMore: boolean,
	openInBackgroundTab: boolean,
	loadImagesOnMobile: boolean,
	itemsPerPage: int,
	unreadOrder: string,
	autoHideReadOnMobile: boolean,
	scrollToArticleHeader: boolean,
	showThumbnails: boolean,
	htmlTitle: string,
	allowPublicUpdate: boolean,
	publicMode: boolean,
	authEnabled: boolean,
	readingSpeed: int | null,
	language: string | null,
	userCss: int | null,
	userJs: int | null,
};
