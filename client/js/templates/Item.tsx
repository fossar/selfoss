import React, {
    Dispatch,
    MouseEvent,
    RefObject,
    SetStateAction,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import { Link, useNavigate, useLocation } from 'react-router';
import { usePreviousImmediate } from 'rooks';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import classNames from 'classnames';
import { FocusTrap, createFocusTrap } from 'focus-trap';
import selfoss from '../selfoss-base';
import { useAllowedToWrite } from '../helpers/authorizations';
import {
    useForceReload,
    makeEntriesLink,
    makeEntriesLinkLocation,
} from '../helpers/uri';
import * as icons from '../icons';
import { LocalizationContext } from '../helpers/i18n';
import { Direction } from '../helpers/navigation';
import { ConfigurationContext } from '../model/Configuration';
import { useSharers } from '../sharers';
import Lightbox from 'yet-another-react-lightbox';
import { TagColor } from '../requests/items';

type Item = {
    id: number;
    title: string;
    strippedTitle: string;
    link: string;
    source: number;
    tags: { [tag: string]: TagColor };
    author: string;
    sourcetitle: string;
    datetime: Date;
    unread: boolean;
    starred: boolean;
    content: string;
    wordCount: number;
    lengthWithoutTags: number;
    icon: string | null;
    thumbnail: string;
};

// TODO: do the search highlights client-side
function reHighlight(text: string) {
    return text.split(/<span class="found">(.+?)<\/span>/).map((n, i) =>
        i % 2 == 0 ? (
            n
        ) : (
            <span key={i} className="found">
                {n}
            </span>
        ),
    );
}

function setupLightbox({
    element,
    setSlides,
    setSelectedPhotoIndex,
}: {
    element: HTMLDivElement;
    setSlides: (slides: Array<{ src: string }>) => void;
    setSelectedPhotoIndex: (index: number) => void;
}) {
    const images = element.querySelectorAll<HTMLAnchorElement>(
        'a[href$=".jpg"], a[href$=".jpeg"], a[href$=".png"], a[href$=".gif"], a[href$=".jpg:large"], a[href$=".jpeg:large"], a[href$=".png:large"], a[href$=".gif:large"]',
    );

    setSlides(
        Array.from(images).map((link, index) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();

                setSelectedPhotoIndex(index);
            });

            return {
                src: link.getAttribute('href'),
            };
        }),
    );
}

function stopPropagation(event) {
    event.stopPropagation();
}

function forceLoadImages(content) {
    content.querySelectorAll('img').forEach((img) => {
        img.setAttribute('src', img.getAttribute('data-selfoss-src'));
    });
}

// React will prevent the default action when it bubbles to our callbacks.
// Let’s stop bubbling for links.
// https://medium.com/peloton-engineering/onclick-wat-51331718ba9
function fixLinkBubbling(content) {
    content.querySelectorAll('a').forEach((a) => {
        a.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    });
}

// Prevent passing referrer info when opening a link.
function sanitizeContent(content) {
    content.querySelectorAll('a').forEach((a) => {
        const oldRel = a.getAttribute('rel');
        a.setAttribute('rel', 'noreferrer' + (oldRel ? ' ' + oldRel : ''));

        // Ensure links inside item content are opened in new tab/window.
        a.setAttribute('target', '_blank');
    });
}

function handleKeyUp(event) {
    // emulate clicking when using keyboard
    if (event.keyCode === 13) {
        // ENTER key
        event.target.click();
        event.preventDefault();
        event.stopPropagation();
    }
}

function preventDefaultOnSmartphone(event) {
    // We want to use the whole title as action for opening/closing items
    // and smartphones have separate buttons for opening the article anyway.
    if (selfoss.isSmartphone()) {
        event.preventDefault();
    }
}

// Handle closing fullscreen on mobile
function closeFullScreen({ event, navigate, location, entryId }) {
    event.preventDefault();
    event.stopPropagation();
    selfoss.entriesPage.setEntryExpanded(entryId, false);
    navigate(makeEntriesLink(location, { id: null }), { replace: true });
}

// show/hide entry
function handleClick({ event, navigate, location, expanded, id, target }) {
    const expected = selfoss.isMobile() ? '.entry' : '.entry-title';
    if (target !== expected) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    if (expanded) {
        selfoss.entriesPage.setSelectedEntry(id);
        selfoss.entriesPage.deactivateEntry(id);
        navigate(makeEntriesLink(location, { id: null }), { replace: true });
    } else {
        selfoss.entriesPage.activateEntry(id);
        navigate(makeEntriesLink(location, { id }), { replace: true });
    }
}

// load images
function loadImages({
    event,
    setImagesLoaded,
    contentBlock,
}: {
    event: MouseEvent;
    setImagesLoaded: Dispatch<SetStateAction<boolean>>;
    contentBlock: RefObject<HTMLDivElement>;
}): void {
    event.preventDefault();
    event.stopPropagation();
    forceLoadImages(contentBlock.current);
    setImagesLoaded(true);
}

// next item on tablet and smartphone
function openNext(event: MouseEvent): void {
    event.preventDefault();
    event.stopPropagation();

    // TODO: Figure out why it does not work when run immediately.
    requestAnimationFrame(() => {
        selfoss.entriesPage?.nextPrev(Direction.NEXT, true);
    });
}

type ShareAction = ({
    id,
    url,
    title,
}: {
    id: number;
    url: string;
    title: string;
}) => void;

type ShareButtonProps = {
    label: string;
    icon: string | React.JSX.Element;
    item: Item;
    action: ShareAction;
    showLabel?: boolean;
};

function ShareButton(props: ShareButtonProps) {
    const { label, icon, item, action, showLabel = true } = props;

    const shareOnClick = useCallback(
        (event: MouseEvent) => {
            event.preventDefault();
            event.stopPropagation();

            action({
                id: item.id,
                url: item.link,
                // TODO: remove HTML
                title: item.title,
            });
        },
        [item, action],
    );

    return (
        <button
            type="button"
            className="entry-share"
            title={label}
            aria-label={label}
            onClick={shareOnClick}
        >
            {icon} {showLabel ? label : null}
        </button>
    );
}

type ItemTagProps = {
    tag: string;
    color: TagColor;
};

function ItemTag(props: ItemTagProps) {
    const { tag, color } = props;

    const style = useMemo(
        () => ({ color: color.foreColor, backgroundColor: color.backColor }),
        [color],
    );

    const location = useLocation();
    const link = useMemo(
        () =>
            makeEntriesLinkLocation(location, {
                category: `tag-${tag}`,
                id: null,
            }),
        [tag, location],
    );
    const forceReload = useForceReload();

    return (
        <Link
            className="entry-tags-tag"
            style={style}
            to={link}
            onClick={preventDefaultOnSmartphone}
            state={forceReload}
        >
            {tag}
        </Link>
    );
}

/**
 * Converts Date to a relative string.
 * When the date is too old, null is returned instead.
 * @return {?String} relative time reference
 */
function datetimeRelative(currentTime: Date, datetime: Date): string | null {
    const ageInseconds = (currentTime.getTime() - datetime.getTime()) / 1000;
    const ageInMinutes = ageInseconds / 60;
    const ageInHours = ageInMinutes / 60;
    const ageInDays = ageInHours / 24;

    if (ageInHours < 1) {
        return selfoss.app._('minutes', {
            '0': Math.round(ageInMinutes).toString(),
        });
    } else if (ageInDays < 1) {
        return selfoss.app._('hours', {
            '0': Math.round(ageInHours).toString(),
        });
    } else {
        return null;
    }
}

type ItemProps = {
    currentTime: Date;
    item: Item;
    selected: boolean;
    expanded: boolean;
    setNavExpanded: React.Dispatch<React.SetStateAction<boolean>>;
};

export default function Item(props: ItemProps) {
    const { currentTime, item, selected, expanded, setNavExpanded } = props;

    const { title, author, sourcetitle } = item;

    const configuration = useContext(ConfigurationContext);
    const shouldAutoLoadImages =
        !selfoss.isMobile() || configuration.loadImagesOnMobile;
    const [imagesLoaded, setImagesLoaded] = useState(shouldAutoLoadImages);
    const contentBlock = useRef<HTMLDivElement | null>(null);

    const location = useLocation();
    const navigate = useNavigate();

    const relDate = useMemo(
        () => datetimeRelative(currentTime, item.datetime),
        [currentTime, item.datetime],
    );

    const previouslyExpanded = usePreviousImmediate(expanded);

    const [slides, setSlides] = useState([]);
    const [selectedPhotoIndex, setSelectedPhotoIndex] = useState(null);

    const fullScreenTrap = useRef<FocusTrap | null>(null);
    // This should match scenarios where fullScreenTrap is set.
    const usingFocusTrap = expanded && selfoss.isSmartphone();

    useEffect(() => {
        // Handle entry becoming/ceasing to be expanded.
        const parent = document.querySelector<HTMLDivElement>(
            `.entry[data-entry-id="${item.id}"]`,
        );
        if (expanded) {
            const firstExpansion = contentBlock.current.childElementCount === 0;
            if (firstExpansion) {
                contentBlock.current.innerHTML = item.content;

                sanitizeContent(contentBlock.current);

                selfoss.extensionPoints.processItemContents(
                    contentBlock.current,
                );

                // load images not on mobile devices
                if (shouldAutoLoadImages) {
                    forceLoadImages(contentBlock.current);
                }
            }

            if (selfoss.isSmartphone()) {
                // save scroll position
                let scrollTop = window.scrollY;

                setNavExpanded((expanded) => {
                    // hide nav
                    if (expanded) {
                        scrollTop =
                            scrollTop -
                            document
                                .querySelector('#nav')
                                .getBoundingClientRect().height;
                        scrollTop = scrollTop < 0 ? 0 : scrollTop;
                        window.scrollTo({ top: scrollTop });

                        return false;
                    }

                    return expanded;
                });

                // show fullscreen
                document.body.classList.add('fullscreen-mode');

                const trap = createFocusTrap(parent);
                fullScreenTrap.current = trap;
                trap.activate();

                if (firstExpansion) {
                    fixLinkBubbling(contentBlock.current);
                }
            } else {
                if (firstExpansion) {
                    // setup fancyBox image viewer
                    setupLightbox({
                        element: contentBlock.current,
                        setSlides,
                        setSelectedPhotoIndex,
                    });
                }

                // scroll to article header
                if (configuration.scrollToArticleHeader) {
                    parent.scrollIntoView();
                }

                if (firstExpansion) {
                    // turn of column view if entry is too long
                    requestAnimationFrame(() => {
                        // Delayed into next frame so that the entry is expanded when the height is being determined.
                        if (
                            contentBlock.current.getBoundingClientRect()
                                .height > document.body.clientHeight
                        ) {
                            const entryContent = contentBlock.current
                                .parentNode as HTMLDivElement;
                            entryContent.classList.add(
                                'entry-content-nocolumns',
                            );
                        }
                    });
                }
            }
        } else {
            // No longer expanded.

            if (fullScreenTrap.current !== null) {
                fullScreenTrap.current.deactivate();
                fullScreenTrap.current = null;
            }

            document.body.classList.remove('fullscreen-mode');
        }
    }, [configuration, expanded, item.content, item.id, setNavExpanded]);

    useEffect(() => {
        // Handle autoHideReadOnMobile setting.
        if (selfoss.isSmartphone() && !expanded && previouslyExpanded) {
            const autoHideReadOnMobile =
                configuration.autoHideReadOnMobile && item.unread;
            if (autoHideReadOnMobile && !item.unread) {
                selfoss.entriesPage.setEntries((entries) =>
                    entries.filter(({ id }) => id !== item.id),
                );
            }
        }
    }, [configuration, expanded, item.id, item.unread, previouslyExpanded]);

    const entryOnClick = useCallback(
        (event: MouseEvent) =>
            handleClick({
                event,
                navigate,
                location,
                expanded,
                id: item.id,
                target: '.entry',
            }),
        [navigate, location, expanded, item.id],
    );

    const titleOnClick = useCallback(
        (event: MouseEvent) =>
            handleClick({
                event,
                navigate,
                location,
                expanded,
                id: item.id,
                target: '.entry-title',
            }),
        [navigate, location, expanded, item.id],
    );

    const starOnClick = useCallback(
        (event: MouseEvent) => {
            event.preventDefault();
            event.stopPropagation();
            selfoss.entriesPage.markEntryStarred(item.id, !item.starred);
        },
        [item],
    );

    const markReadOnClick = useCallback(
        (event: MouseEvent) => {
            event.preventDefault();
            event.stopPropagation();
            selfoss.entriesPage.markEntryRead(item.id, item.unread);
        },
        [item],
    );

    const loadImagesOnClick = useCallback(
        (event: MouseEvent) =>
            loadImages({ event, setImagesLoaded, contentBlock }),
        [],
    );

    const closeOnClick = useCallback(
        (event: MouseEvent) =>
            closeFullScreen({ event, navigate, location, entryId: item.id }),
        [navigate, location, item.id],
    );

    const titleHtml = useMemo(
        () => ({ __html: title ? title : selfoss.app._('no_title') }),
        [title],
    );

    const sourceLink = useMemo(
        () =>
            makeEntriesLinkLocation(location, {
                category: `source-${item.source}`,
                id: null,
            }),
        [item.source, location],
    );
    const forceReload = useForceReload();

    const canWrite = useAllowedToWrite();

    const _ = useContext(LocalizationContext);

    const sharers = useSharers({ configuration, _ });

    return (
        <div
            data-entry-id={item.id}
            data-entry-source={item.source}
            data-entry-url={item.link}
            className={classNames({
                entry: true,
                unread: item.unread,
                expanded,
                selected,
            })}
            role="article"
            aria-modal={usingFocusTrap}
            onClick={entryOnClick}
        >
            {/* icon */}
            <a
                href={item.link}
                className="entry-icon"
                tabIndex={-1}
                rel="noreferrer"
                aria-hidden="true"
                onClick={preventDefaultOnSmartphone}
            >
                {item.icon !== null &&
                item.icon.trim().length > 0 &&
                item.icon != '0' ? (
                    <img
                        src={`favicons/${item.icon}`}
                        aria-hidden="true"
                        alt=""
                    />
                ) : null}
            </a>

            {/* title */}
            <h3 className="entry-title" onClick={titleOnClick}>
                <span
                    className="entry-title-link"
                    aria-expanded={expanded}
                    aria-current={selected}
                    role="link"
                    tabIndex={0}
                    onKeyUp={handleKeyUp}
                    dangerouslySetInnerHTML={titleHtml}
                />
            </h3>

            <span className="entry-tags">
                {Object.entries(item.tags).map(([tag, color]) => (
                    <ItemTag key={tag} tag={tag} color={color} />
                ))}
            </span>

            {/* source */}
            <Link
                className="entry-source"
                to={sourceLink}
                onClick={preventDefaultOnSmartphone}
                state={forceReload}
            >
                {reHighlight(sourcetitle)}
            </Link>

            <span className="entry-separator">•</span>

            {/* author */}
            {author !== null ? (
                <>
                    <span className="entry-author">{author}</span>
                    <span className="entry-separator">•</span>
                </>
            ) : null}

            {/* datetime */}
            <a
                href={item.link}
                className={classNames({
                    'entry-datetime': true,
                    timestamped: relDate === null,
                })}
                target="_blank"
                rel="noreferrer"
                onClick={preventDefaultOnSmartphone}
            >
                {relDate !== null ? relDate : item.datetime.toLocaleString()}
            </a>

            {/* read time */}
            {configuration.readingSpeed !== null ? (
                <>
                    <span className="entry-separator">•</span>
                    <span className="entry-readtime">
                        {_('article_reading_time', [
                            Math.round(
                                item.wordCount / configuration.readingSpeed,
                            ),
                        ])}
                    </span>
                </>
            ) : null}

            {/* thumbnail */}
            {item.thumbnail && item.thumbnail.trim().length > 0 ? (
                <div
                    className={classNames({
                        'entry-thumbnail': true,
                        'entry-thumbnail-always-visible':
                            configuration.showThumbnails,
                    })}
                >
                    <a href={item.link} target="_blank" rel="noreferrer">
                        <img
                            src={`thumbnails/${item.thumbnail}`}
                            alt={item.strippedTitle}
                        />
                    </a>
                </div>
            ) : null}

            {/* content */}
            <div
                className={classNames({
                    'entry-content': true,
                    'entry-content-nocolumns': item.lengthWithoutTags < 500,
                })}
            >
                {slides.length !== 0 && (
                    <Lightbox
                        open={selectedPhotoIndex !== null}
                        index={selectedPhotoIndex}
                        close={() => setSelectedPhotoIndex(null)}
                        carousel={{
                            finite: true,
                        }}
                        controller={{
                            closeOnBackdropClick: true,
                        }}
                        on={{
                            entered: () => selfoss.lightboxActive.update(true),
                            exited: () => selfoss.lightboxActive.update(false),
                        }}
                        slides={slides}
                    />
                )}

                <div ref={contentBlock} />

                <div className="entry-smartphone-share">
                    <ul aria-label={_('article_actions')}>
                        <li>
                            <a
                                href={item.link}
                                className="entry-newwindow"
                                target="_blank"
                                rel="noreferrer"
                                accessKey="o"
                                onClick={stopPropagation}
                            >
                                <FontAwesomeIcon icon={icons.openWindow} />{' '}
                                {_('open_window')}
                            </a>
                        </li>
                        {sharers.map(({ key, label, icon, action }) => (
                            <li key={key}>
                                <ShareButton
                                    label={label}
                                    icon={icon}
                                    item={item}
                                    action={action}
                                />
                            </li>
                        ))}
                        <li>
                            <button
                                type="button"
                                accessKey="n"
                                className="entry-next"
                                onClick={openNext}
                            >
                                <FontAwesomeIcon icon={icons.next} />{' '}
                                {_('next')}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            {/* toolbar */}
            <ul aria-label={_('article_actions')} className="entry-toolbar">
                {canWrite && (
                    <li>
                        <button
                            accessKey="a"
                            className={classNames({
                                'entry-starr': true,
                                active: item.starred,
                            })}
                            onClick={starOnClick}
                        >
                            <FontAwesomeIcon
                                icon={item.starred ? icons.unstar : icons.star}
                            />{' '}
                            {item.starred ? _('unstar') : _('star')}
                        </button>
                    </li>
                )}
                {canWrite && (
                    <li>
                        <button
                            accessKey="u"
                            className={classNames({
                                'entry-unread': true,
                                active: item.unread,
                            })}
                            onClick={markReadOnClick}
                        >
                            <FontAwesomeIcon
                                icon={
                                    item.unread
                                        ? icons.markRead
                                        : icons.markUnread
                                }
                            />{' '}
                            {item.unread ? _('mark') : _('unmark')}
                        </button>
                    </li>
                )}
                <li>
                    <a
                        href={item.link}
                        className="entry-newwindow"
                        target="_blank"
                        rel="noreferrer"
                        accessKey="o"
                        onClick={stopPropagation}
                    >
                        <FontAwesomeIcon icon={icons.openWindow} />{' '}
                        {_('open_window')}
                    </a>
                </li>
                {!imagesLoaded ? (
                    <li>
                        <button
                            className="entry-loadimages"
                            onClick={loadImagesOnClick}
                        >
                            <FontAwesomeIcon icon={icons.loadImages} />{' '}
                            {_('load_img')}
                        </button>
                    </li>
                ) : null}
                <li>
                    <button
                        type="button"
                        accessKey="n"
                        className="entry-next"
                        onClick={openNext}
                    >
                        <FontAwesomeIcon icon={icons.next} /> {_('next')}
                    </button>
                </li>
                {sharers.map(({ key, label, icon, action }) => (
                    <li key={key}>
                        <ShareButton
                            label={label}
                            icon={icon}
                            item={item}
                            action={action}
                            showLabel={false}
                        />
                    </li>
                ))}
                <li>
                    <button
                        accessKey="c"
                        className="entry-close"
                        onClick={closeOnClick}
                    >
                        <FontAwesomeIcon icon={icons.close} />{' '}
                        {_('close_entry')}
                    </button>
                </li>
            </ul>
        </div>
    );
}
