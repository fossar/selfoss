import React from 'react';
import PropTypes from 'prop-types';
import { Link, useHistory, useLocation } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import classNames from 'classnames';
import { createFocusTrap } from 'focus-trap';
import { nextprev, Direction } from '../shortcuts';
import { makeEntriesLink } from '../helpers/uri';
import * as itemsRequests from '../requests/items';
import * as icons from '../icons';
import { LocalizationContext } from '../helpers/i18n';

function anonymize(url) {
    return (selfoss.config.anonymizer ?? '') + url;
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
function closeFullScreen({ event, history, location, entry, setFullScreenTrap }) {
    event.preventDefault();
    event.stopPropagation();
    selfoss.entriesPage.setEntryExpanded(entry.id, false);
    setFullScreenTrap((trap) => {
        if (trap !== null) {
            trap.deactivate();
        }

        return null;
    });

    document.body.classList.remove('fullscreen-mode');

    selfoss.entriesPage.setEntryExpanded(entry.id, false);
    history.replace(makeEntriesLink(location, { id: null }));

    const autoHideReadOnMobile = selfoss.config.autoHideReadOnMobile && entry.unread == 1;
    if (autoHideReadOnMobile && entry.unread != 1) {
        selfoss.entriesPage.setEntries((entries) => entries.filter(({ id }) => id !== entry.id));
    }
}

// show/hide entry
function handleClick({ event, history, location, target, entry, contentBlock, setFullScreenTrap, setImagesLoaded, setNavExpanded }) {
    const expected = selfoss.isMobile() ? '.entry' : '.entry-title';
    if (target !== expected) {
        return;
    }
    event.preventDefault();
    event.stopPropagation();


    const entryId = entry.id;

    // show/hide (with toolbar)
    selfoss.entriesPage.setEntryExpanded(entry.id, (expanded) => {
        const parent = document.querySelector(`.entry[data-entry-id="${entry.id}"]`);
        if (expanded) {
            if (selfoss.isSmartphone()) {
                parent.querySelector('.entry-close').click();
            } else {
                history.replace(makeEntriesLink(location, { id: null }));
            }
        } else {
            if (selfoss.config.autoCollapse) {
                selfoss.entriesPage.collapseAllEntries();
            }
            selfoss.entriesPage.setSelectedEntry(entry.id);
            history.replace(makeEntriesLink(location, { id: entryId }));

            if (contentBlock.current.childElementCount === 0) {
                contentBlock.current.innerHTML = entry.content;
            }

            // load images not on mobile devices
            if (selfoss.isMobile() == false || selfoss.config.loadImagesOnMobile) {
                setImagesLoaded(true);
                lazyLoadImages(contentBlock.current);
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

                let trap = createFocusTrap(parent);
                setFullScreenTrap(trap);
                trap.activate();
                fixLinkBubbling(contentBlock.current);
            } else {
                // setup fancyBox image viewer
                selfoss.setupFancyBox(contentBlock.current, entryId);

                // scroll to article header
                if (selfoss.config.scrollToArticleHeader) {
                    parent.scrollIntoView();
                }

                // turn of column view if entry is too long
                requestAnimationFrame(() => {
                    // Delayed into next frame so that the entry is expanded when the height is being determined.
                    if (contentBlock.current.getBoundingClientRect().height > document.body.clientHeight) {
                        contentBlock.current.parentNode.classList.add('entry-content-nocolumns');
                    }
                });
            }

            // automark as read
            const autoMarkAsRead = selfoss.loggedin.value && selfoss.config.autoMarkAsRead && entry.unread == 1;
            if (autoMarkAsRead) {
                parent.querySelector('.entry-unread').click();
            }

            // anonymize
            selfoss.anonymize(contentBlock.current);
        }

        return !expanded;
    });
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
        nextprev(Direction.NEXT, true);
    });
}

// hookup the share icon click events
function share({ event, entry, name }) {
    event.preventDefault();
    event.stopPropagation();

    selfoss.shares.share(name, {
        id: entry.id,
        url: entry.link,
        // TODO: remove HTML
        title: entry.title
    });
}

// starr/unstarr
function handleStarredToggle({ event, entry }) {
    event.preventDefault();
    event.stopPropagation();
    // only loggedin users
    if (!selfoss.loggedin.value) {
        return;
    }

    const { id } = entry;
    const starr = entry.starred != 1;

    selfoss.entriesPage.starEntry(id, starr);

    // update statistics in main menu
    function updateStats(starr) {
        selfoss.app.setStarredItemsCount((starred) => starred + (starr ? 1 : -1));
    }
    updateStats(starr);

    if (selfoss.db.enableOffline.value) {
        selfoss.dbOffline.entryStar(id, starr);
    }

    itemsRequests.starr(id, starr).then(() => {
        selfoss.db.setOnline();
    }).catch(function(error) {
        selfoss.handleAjaxError(error).then(function() {
            selfoss.dbOffline.enqueueStatus(id, 'starred', starr);
        }).catch(function(error) {
            // rollback ui changes
            selfoss.entriesPage.starEntry(id, !starr);
            updateStats(!starr);
            selfoss.app.showError(selfoss.app._('error_star_item') + ' ' + error.message);
        });
    });
}

// read/unread
function handleReadToggle({ event, entry }) {
    event.preventDefault();
    event.stopPropagation();
    // only loggedin users
    if (!selfoss.loggedin.value) {
        return;
    }

    const { id } = entry;
    const unread = entry.unread == 1;

    selfoss.entriesPage.markEntry(id, !unread);

    // update statistics in main menue and the currently active tag
    function updateStats(unread) {
        // update all unread counters
        const unreadstats = selfoss.app.state.unreadItemsCount;
        const diff = unread ? -1 : 1;

        selfoss.refreshUnread(unreadstats + diff);

        // update unread on tags and sources
        // Only a single instance of each tag per entry so we can just assign.
        const entryTags = Object.fromEntries(Object.keys(entry.tags).map((tag) => [tag, diff]));
        selfoss.app.refreshTagSourceUnread(
            entryTags,
            {[entry.source]: diff}
        );
    }
    updateStats(unread);

    if (selfoss.db.enableOffline.value) {
        selfoss.dbOffline.entryMark(id, !unread);
    }

    itemsRequests.mark(id, !unread).then(() => {
        selfoss.db.setOnline();
    }).catch(function(error) {
        selfoss.handleAjaxError(error).then(function() {
            selfoss.dbOffline.enqueueStatus(id, 'unread', !unread);
        }).catch(function(error) {
            // rollback ui changes
            selfoss.entriesPage.markEntry(id, unread);
            updateStats(!unread);
            selfoss.app.showError(selfoss.app._('error_mark_item') + ' ' + error.message);
        });
    });
}

function ShareButton({ name, label, icon, item, showLabel = true }) {
    const shareOnClick = React.useCallback(
        (event) => share({ event, entry: item, name }),
        [item, name]
    );

    return (
        <button
            type="button"
            className={`entry-share entry-share${name}`}
            title={label}
            aria-label={label}
            onClick={shareOnClick}
        >
            {icon} {showLabel ? label : null}
        </button>
    );
}

ShareButton.propTypes = {
    name: PropTypes.string.isRequired,
    label: PropTypes.string.isRequired,
    icon: PropTypes.element.isRequired,
    item: PropTypes.object.isRequired,
    showLabel: PropTypes.bool,
};

function ItemTag({tag, color}) {
    const style = React.useMemo(
        () => ({ color: color.foreColor, backgroundColor: color.backColor }),
        [color]
    );
    const location = useLocation();

    return (
        <Link
            className="entry-tags-tag"
            style={style}
            to={makeEntriesLink(location, { category: `tag-${tag}`, id: null })}
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

    const [fullScreenTrap, setFullScreenTrap] = React.useState(null);
    const [imagesLoaded, setImagesLoaded] = React.useState(false);
    const contentBlock = React.useRef(null);

    const location = useLocation();
    const history = useHistory();

    const relDate = React.useMemo(
        () => datetimeRelative(currentTime, item.datetime),
        [currentTime, item.datetime]
    );
    const shares = selfoss.shares.getAll();

    const entryOnClick = React.useCallback(
        (event) => handleClick({ event, history, location, entry: item, target: '.entry', contentBlock, setFullScreenTrap, setImagesLoaded, setNavExpanded }),
        [history, location, item, setNavExpanded]
    );

    const titleOnClick = React.useCallback(
        (event) => handleClick({ event, history, location, entry: item, target: '.entry-title', contentBlock, setFullScreenTrap, setImagesLoaded }),
        [history, location, item]
    );

    const starOnClick = React.useCallback(
        (event) => handleStarredToggle({ event, entry: item }),
        [item]
    );

    const markReadOnClick = React.useCallback(
        (event) => handleReadToggle({ event, entry: item }),
        [item]
    );

    const loadImagesOnClick = React.useCallback(
        (event) => loadImages({ event, setImagesLoaded, contentBlock }),
        []
    );

    const closeOnClick = React.useCallback(
        (event) => closeFullScreen({ event, history, location, entry: item, setFullScreenTrap }),
        [history, location, item]
    );

    const titleHtml = React.useMemo(
        () => ({ __html: title }),
        [title]
    );

    const _ = React.useContext(LocalizationContext);

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
                href={anonymize(item.link)}
                className="entry-icon"
                tabIndex="-1"
                rel="noopener noreferrer"
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
                onClick={titleOnClick}
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
                to={makeEntriesLink(location, { category: `source-${item.source}`, id: null })}
                onClick={preventDefaultOnSmartphone}
            >
                {sourcetitle}
            </Link>

            <span className="entry-separator">•</span>

            {/* author */}
            {author.trim() !== '' ?
                <React.Fragment>
                    <span className="entry-author">{author}</span>
                    <span className="entry-separator">•</span>
                </React.Fragment>
                : null}

            {/* datetime */}
            <a
                href={anonymize(item.link)}
                className={classNames({'entry-datetime': true, timestamped: relDate === null})}
                target="_blank"
                rel="noopener noreferrer"
                onClick={preventDefaultOnSmartphone}
            >
                {relDate !== null ? relDate : item.datetime.toLocaleString()}
            </a>

            {/* read time */}
            {selfoss.config.readingSpeed !== null ?
                <React.Fragment>
                    <span className="entry-separator">•</span>
                    <span className="entry-readtime">{_('article_reading_time', [Math.round(item.wordCount / selfoss.config.readingSpeed)])}</span>
                </React.Fragment>
                : null}

            {/* thumbnail */}
            {item.thumbnail && item.thumbnail.trim().length > 0 ?
                <div className={classNames({'entry-thumbnail': true, 'entry-thumbnail-always-visible': selfoss.config.showThumbnails})}>
                    <a href={anonymize(item.link)} target="_blank" rel="noopener noreferrer">
                        <img src={`thumbnails/${item.thumbnail}`} alt={item.strippedTitle} />
                    </a>
                </div>
                : null}

            {/* content */}
            <div className={classNames({'entry-content': true, 'entry-content-nocolumns': item.lengthWithoutTags < 500})}>
                <div ref={contentBlock} />

                <div className="entry-smartphone-share">
                    <ul aria-label={_('article_actions')}>
                        <li>
                            <a
                                href={anonymize(item.link)}
                                className="entry-newwindow"
                                target="_blank"
                                rel="noopener noreferrer"
                                accessKey="o"
                                onClick={stopPropagation}
                            >
                                <FontAwesomeIcon icon={icons.openWindow} /> {_('open_window')}
                            </a>
                        </li>
                        {shares.map(({ name, label, icon }) => (
                            <li key={name}>
                                <ShareButton
                                    name={name}
                                    label={label}
                                    icon={icon}
                                    item={item}
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
                <li>
                    <button
                        accessKey="a"
                        className={classNames({'entry-starr': true, active: item.starred == 1})}
                        onClick={starOnClick}
                    >
                        <FontAwesomeIcon icon={item.starred == 1 ? icons.unstar : icons.star} /> {item.starred == 1 ? _('unstar') : _('star')}
                    </button>
                </li>
                <li>
                    <button
                        accessKey="u"
                        className={classNames({'entry-unread': true, active: item.unread == 1})}
                        onClick={markReadOnClick}
                    >
                        <FontAwesomeIcon icon={item.unread == 1 ? icons.markRead : icons.markUnread} /> {item.unread == 1 ? _('mark') : _('unmark')}
                    </button>
                </li>
                <li>
                    <a
                        href={anonymize(item.link)}
                        className="entry-newwindow"
                        target="_blank"
                        rel="noopener noreferrer"
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
                {shares.map(({ name, label, icon }) => (
                    <li key={name}>
                        <ShareButton
                            name={name}
                            label={label}
                            icon={icon}
                            item={item}
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
