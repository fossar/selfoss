import React, {
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import PropTypes from 'prop-types';
import { Link, useHistory, useLocation } from 'react-router-dom';
import { usePreviousImmediate } from 'rooks';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import classNames from 'classnames';
import { createFocusTrap } from 'focus-trap';
import { useAllowedToWrite } from '../helpers/authorizations';
import { forceReload, makeEntriesLink, makeEntriesLinkLocation } from '../helpers/uri';
import * as icons from '../icons';
import { ConfigurationContext } from '../helpers/configuration';
import { LocalizationContext } from '../helpers/i18n';
import { Direction } from '../helpers/navigation';
import { useSharers } from '../sharers';
import Lightbox from 'yet-another-react-lightbox';

// TODO: do the search highlights client-side
function reHighlight(text) {
    return text.split(/<span class="found">(.+?)<\/span>/).map((n, i) => i % 2 == 0 ? n : <span key={i} className="found">{n}</span>);
}

function setupLightbox({
    element,
    setSlides,
    setSelectedPhotoIndex,
}) {
    const images = element.querySelectorAll('a[href$=".jpg"], a[href$=".jpeg"], a[href$=".png"], a[href$=".gif"], a[href$=".jpg:large"], a[href$=".jpeg:large"], a[href$=".png:large"], a[href$=".gif:large"]');

    setSlides(Array.from(images).map((link, index) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();

            setSelectedPhotoIndex(index);
        });

        return {
            src: link.getAttribute('href'),
        };
    }));
}

function useMultiClickHandler(handler, delay = 400) {
    const [state, setState] = useState({ clicks: 0, args: [] });

    useEffect(() => {
        const timer = setTimeout(() => {
            setState({ clicks: 0, args: [] });

            if (state.clicks > 0 && typeof handler[state.clicks] === 'function') {
                handler[state.clicks](...state.args);
            }
        }, delay);

        return () => clearTimeout(timer);
    }, [handler, delay, state.clicks, state.args]);

    return (...args) => {
        setState((prevState) => ({ clicks: prevState.clicks + 1, args }));

        if (typeof handler[0] === 'function') {
            handler[0](...args);
        }
    };
}

function stopPropagation(event) {
    event.stopPropagation();
}

function lazyLoadImages(content) {
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
    if (event.keyCode === 13) { // ENTER key
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
function closeFullScreen({ event, history, location, entryId }) {
    event.preventDefault();
    event.stopPropagation();
    selfoss.entriesPage.setEntryExpanded(entryId, false);
    history.replace(makeEntriesLink(location, { id: null }));
}

// show/hide entry
function handleToggleOpenClick({ event, history, location, expanded, id, target }) {
    const expected = selfoss.isMobile() ? '.entry' : '.entry-title';
    if (target !== expected) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    if (expanded) {
        selfoss.entriesPage.setSelectedEntry(id);
        selfoss.entriesPage.deactivateEntry(id);
        history.replace(makeEntriesLink(location, { id: null }));
    } else {
        selfoss.entriesPage.activateEntry(id);
        history.replace(makeEntriesLink(location, { id }));
    }
}

// mark entry read/unread
function handleToggleReadClick({ event, unread, id }) {
    event.preventDefault();
    event.stopPropagation();

    selfoss.entriesPage.markEntryRead(id, unread == 1);
}

// load images
function loadImages({ event, setImagesLoaded, contentBlock }) {
    event.preventDefault();
    event.stopPropagation();
    lazyLoadImages(contentBlock.current);
    setImagesLoaded(true);
}

// next item on tablet and smartphone
function openNext(event) {
    event.preventDefault();
    event.stopPropagation();

    // TODO: Figure out why it does not work when run immediately.
    requestAnimationFrame(() => {
        selfoss.entriesPage?.nextPrev(Direction.NEXT, true);
    });
}

function ShareButton({
    label,
    icon,
    item,
    action,
    showLabel = true,
}) {
    const shareOnClick = useCallback(
        (event) => {
            event.preventDefault();
            event.stopPropagation();

            action({
                id: item.id,
                url: item.link,
                // TODO: remove HTML
                title: item.title
            });
        },
        [item, action]
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

ShareButton.propTypes = {
    label: PropTypes.string.isRequired,
    icon: PropTypes.oneOfType([
        PropTypes.string,
        PropTypes.element,
    ]).isRequired,
    item: PropTypes.object.isRequired,
    action: PropTypes.func.isRequired,
    showLabel: PropTypes.bool,
};

function ItemTag({tag, color}) {
    const style = useMemo(
        () => ({ color: color.foreColor, backgroundColor: color.backColor }),
        [color]
    );

    const link = useCallback(
        (location) => ({
            ...location,
            ...makeEntriesLinkLocation(location, { category: `tag-${tag}`, id: null }),
            state: forceReload(location),
        }),
        [tag]
    );

    return (
        <Link
            className="entry-tags-tag"
            style={style}
            to={link}
            onClick={preventDefaultOnSmartphone}
        >
            {tag}
        </Link>
    );
}

ItemTag.propTypes = {
    tag: PropTypes.string.isRequired,
    color: PropTypes.object.isRequired,
};

/**
 * Converts Date to a relative string.
 * When the date is too old, null is returned instead.
 * @param {Date} currentTime
 * @param {Date} datetime
 * @return {?String} relative time reference
 */
function datetimeRelative(currentTime, datetime) {
    const ageInseconds = (currentTime - datetime) / 1000;
    const ageInMinutes = ageInseconds / 60;
    const ageInHours = ageInMinutes / 60;
    const ageInDays = ageInHours / 24;

    if (ageInHours < 1) {
        return selfoss.app._('minutes', [Math.round(ageInMinutes)]);
    } else if (ageInDays < 1) {
        return selfoss.app._('hours', [Math.round(ageInHours)]);
    } else {
        return null;
    }
}

export default function Item({ currentTime, item, selected, expanded, setNavExpanded }) {
    const { title, author, sourcetitle } = item;

    const [fullScreenTrap, setFullScreenTrap] = useState(null);
    const [imagesLoaded, setImagesLoaded] = useState(false);
    const contentBlock = useRef(null);

    const location = useLocation();
    const history = useHistory();

    const relDate = useMemo(
        () => datetimeRelative(currentTime, item.datetime),
        [currentTime, item.datetime]
    );

    const canWrite = useAllowedToWrite();

    const previouslyExpanded = usePreviousImmediate(expanded);
    const configuration = useContext(ConfigurationContext);

    const [slides, setSlides] = useState([]);
    const [selectedPhotoIndex, setSelectedPhotoIndex] = useState(null);

    useEffect(() => {
        // Handle entry becoming/ceasing to be expanded.
        const parent = document.querySelector(`.entry[data-entry-id="${item.id}"]`);
        if (expanded) {
            const firstExpansion = contentBlock.current.childElementCount === 0;
            if (firstExpansion) {
                contentBlock.current.innerHTML = item.content;

                sanitizeContent(contentBlock.current);

                selfoss.extensionPoints.processItemContents(contentBlock.current);

                // load images not on mobile devices
                if (selfoss.isMobile() == false || configuration.loadImagesOnMobile) {
                    setImagesLoaded(true);
                    lazyLoadImages(contentBlock.current);
                }
            }

            if (selfoss.isSmartphone()) {
                // save scroll position
                let scrollTop = window.scrollY;

                setNavExpanded((expanded) => {
                    // hide nav
                    if (expanded) {
                        scrollTop = scrollTop - document.querySelector('#nav').getBoundingClientRect().height;
                        scrollTop = scrollTop < 0 ? 0 : scrollTop;
                        window.scrollTo({ top: scrollTop });

                        return false;
                    }

                    return expanded;
                });

                // show fullscreen
                document.body.classList.add('fullscreen-mode');

                const trap = createFocusTrap(parent);
                setFullScreenTrap(trap);
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
                        if (contentBlock.current.getBoundingClientRect().height > document.body.clientHeight) {
                            contentBlock.current.parentNode.classList.add('entry-content-nocolumns');
                        }
                    });
                }
            }
        } else {
            // No longer expanded.

            setFullScreenTrap((trap) => {
                if (trap !== null) {
                    trap.deactivate();
                }

                return null;
            });

            document.body.classList.remove('fullscreen-mode');
        }
    }, [configuration, expanded, item.content, item.id, setNavExpanded]);

    useEffect(() => {
        // Handle autoHideReadOnMobile setting.
        if (selfoss.isSmartphone() && !expanded && previouslyExpanded) {
            const autoHideReadOnMobile = configuration.autoHideReadOnMobile && item.unread == 1;
            if (autoHideReadOnMobile && item.unread != 1) {
                selfoss.entriesPage.setEntries((entries) => entries.filter(({ id }) => id !== item.id));
            }
        }
    }, [configuration, expanded, item.id, item.unread, previouslyExpanded]);

    const entryOnClick = useCallback(
        (event) => handleToggleOpenClick({ event, history, location, expanded, id: item.id, target: '.entry' }),
        [history, location, expanded, item.id]
    );

    const titleOnClick = useCallback(
        (event) => handleToggleOpenClick({ event, history, location, expanded, id: item.id, target: '.entry-title' }),
        [history, location, expanded, item.id]
    );

    const titleOnMultiClick = useMultiClickHandler({
        0: (event) => {
            event.preventDefault();
        },
        1: titleOnClick,
        2: useCallback(
            (event) => {
                if (canWrite && !selfoss.isSmartphone()) {
                    handleToggleReadClick({ event, unread: item.unread, id: item.id });
                }
            },
            [canWrite, item.unread, item.id]
        )
    });

    const starOnClick = useCallback(
        (event) => {
            event.preventDefault();
            event.stopPropagation();
            selfoss.entriesPage.markEntryStarred(item.id, item.starred != 1);
        },
        [item]
    );

    const markReadOnClick = useCallback(
        (event) => handleToggleReadClick({ event, unread: item.unread, id: item.id }),
        [item.unread, item.id]
    );

    const loadImagesOnClick = useCallback(
        (event) => loadImages({ event, setImagesLoaded, contentBlock }),
        []
    );

    const closeOnClick = useCallback(
        (event) => closeFullScreen({ event, history, location, entryId: item.id }),
        [history, location, item.id]
    );

    const titleHtml = useMemo(
        () => ({ __html: title ? title : selfoss.app._('no_title') }),
        [title]
    );

    const sourceLink = useCallback(
        (location) => ({
            ...location,
            ...makeEntriesLinkLocation(location, { category: `source-${item.source}`, id: null }),
            state: forceReload(location),
        }),
        [item.source]
    );

    const _ = useContext(LocalizationContext);

    const sharers = useSharers({ configuration, _ });

    return (
        <div data-entry-id={item.id}
            data-entry-source={item.source}
            data-entry-url={item.link}
            className={classNames({entry: true, unread: item.unread == 1, expanded, selected})}
            role="article"
            aria-modal={fullScreenTrap !== null}
            onClick={entryOnClick}
        >

            {/* icon */}
            <a
                href={item.link}
                className="entry-icon"
                tabIndex="-1"
                rel="noreferrer"
                aria-hidden="true"
                onClick={preventDefaultOnSmartphone}
            >
                {item.icon !== null && item.icon.trim().length > 0 && item.icon != '0' ?
                    <img src={`favicons/${item.icon}`} aria-hidden="true" alt="" />
                    : null}
            </a>

            {/* title */}
            <h3
                className="entry-title"
                onClick={configuration.doubleClickMarkAsRead ? titleOnMultiClick : titleOnClick}
            >
                <span
                    className="entry-title-link"
                    aria-expanded={expanded}
                    aria-current={selected}
                    role="link"
                    tabIndex="0"
                    onKeyUp={handleKeyUp}
                    dangerouslySetInnerHTML={titleHtml}
                />
            </h3>

            <span className="entry-tags">
                {Object.entries(item.tags).map(([tag, color]) =>
                    <ItemTag
                        key={tag}
                        tag={tag}
                        color={color}
                    />
                )}
            </span>

            {/* source */}
            <Link
                className="entry-source"
                to={sourceLink}
                onClick={preventDefaultOnSmartphone}
            >
                {reHighlight(sourcetitle)}
            </Link>

            <span className="entry-separator">•</span>

            {/* author */}
            {author !== null ?
                <React.Fragment>
                    <span className="entry-author">{author}</span>
                    <span className="entry-separator">•</span>
                </React.Fragment>
                : null}

            {/* datetime */}
            <a
                href={item.link}
                className={classNames({'entry-datetime': true, timestamped: relDate === null})}
                target="_blank"
                rel="noreferrer"
                onClick={preventDefaultOnSmartphone}
            >
                {relDate !== null ? relDate : item.datetime.toLocaleString()}
            </a>

            {/* read time */}
            {configuration.readingSpeed !== null ?
                <React.Fragment>
                    <span className="entry-separator">•</span>
                    <span className="entry-readtime">{_('article_reading_time', [Math.round(item.wordCount / configuration.readingSpeed)])}</span>
                </React.Fragment>
                : null}

            {/* thumbnail */}
            {item.thumbnail && item.thumbnail.trim().length > 0 ?
                <div className={classNames({'entry-thumbnail': true, 'entry-thumbnail-always-visible': configuration.showThumbnails})}>
                    <a href={item.link} target="_blank" rel="noreferrer">
                        <img src={`thumbnails/${item.thumbnail}`} alt={item.strippedTitle} />
                    </a>
                </div>
                : null}

            {/* content */}
            <div className={classNames({'entry-content': true, 'entry-content-nocolumns': item.lengthWithoutTags < 500})}>
                {slides.length !== 0 && <Lightbox
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
                />}

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
                                <FontAwesomeIcon icon={icons.openWindow} /> {_('open_window')}
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
                            <button type="button" accessKey="n" className="entry-next" onClick={openNext}>
                                <FontAwesomeIcon icon={icons.next} /> {_('next')}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            {/* toolbar */}
            <ul aria-label={_('article_actions')} className="entry-toolbar">
                {canWrite &&
                    <li>
                        <button
                            accessKey="a"
                            className={classNames({'entry-starr': true, active: item.starred == 1})}
                            onClick={starOnClick}
                        >
                            <FontAwesomeIcon icon={item.starred == 1 ? icons.unstar : icons.star} /> {item.starred == 1 ? _('unstar') : _('star')}
                        </button>
                    </li>
                }
                {canWrite &&
                    <li>
                        <button
                            accessKey="u"
                            className={classNames({'entry-unread': true, active: item.unread == 1})}
                            onClick={markReadOnClick}
                        >
                            <FontAwesomeIcon icon={item.unread == 1 ? icons.markRead : icons.markUnread} /> {item.unread == 1 ? _('mark') : _('unmark')}
                        </button>
                    </li>
                }
                <li>
                    <a
                        href={item.link}
                        className="entry-newwindow"
                        target="_blank"
                        rel="noreferrer"
                        accessKey="o"
                        onClick={stopPropagation}
                    >
                        <FontAwesomeIcon icon={icons.openWindow} /> {_('open_window')}
                    </a>
                </li>
                {!imagesLoaded ?
                    <li>
                        <button className="entry-loadimages" onClick={loadImagesOnClick}>
                            <FontAwesomeIcon icon={icons.loadImages} /> {_('load_img')}
                        </button>
                    </li>
                    : null
                }
                <li>
                    <button type="button" accessKey="n" className="entry-next" onClick={openNext}>
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
                    <button accessKey="c" className="entry-close" onClick={closeOnClick}>
                        <FontAwesomeIcon icon={icons.close} /> {_('close_entry')}
                    </button>
                </li>
            </ul>
        </div>
    );
}

Item.propTypes = {
    currentTime: PropTypes.instanceOf(Date).isRequired,
    item: PropTypes.object.isRequired,
    selected: PropTypes.bool.isRequired,
    expanded: PropTypes.bool.isRequired,
    setNavExpanded: PropTypes.func.isRequired,
};
