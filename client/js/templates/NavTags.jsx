import React, { useContext, useCallback, useState } from 'react';
import PropTypes from 'prop-types';
import nullable from 'prop-types-nullable';
import { Link, useRouteMatch } from 'react-router-dom';
import classNames from 'classnames';
import { unescape } from 'html-escaper';
import {
    forceReload,
    makeEntriesLinkLocation,
    ENTRIES_ROUTE_PATTERN,
} from '../helpers/uri';
import ColorChooser from './ColorChooser';
import { updateTag } from '../requests/tags';
import { Collapse } from '@kunukn/react-collapse';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';
import { LocalizationContext } from '../helpers/i18n';

function Tag({ tag, active, collapseNav }) {
    const _ = useContext(LocalizationContext);
    const tagName = tag !== null ? tag.tag : null;

    const colorChanged = useCallback(
        ({ value }) => {
            updateTag(tagName, value)
                .then(() => {
                    selfoss.entriesPage?.reload();
                })
                .catch((error) => {
                    selfoss.app.showError(
                        selfoss.app._('error_saving_color') +
                            ' ' +
                            error.message,
                    );
                });
        },
        [tagName],
    );

    const category = tag === null ? 'all' : `tag-${tag.tag}`;
    const link = useCallback(
        (location) => ({
            ...location,
            ...makeEntriesLinkLocation(location, {
                category,
                id: null,
            }),
            state: forceReload(location),
        }),
        [category],
    );

    return (
        <li className={classNames({ read: tag !== null && tag.unread === 0 })}>
            <Link
                to={link}
                className={classNames({ 'nav-tags-all': tag === null, active })}
                onClick={collapseNav}
            >
                {tag === null ? (
                    _('alltags')
                ) : (
                    <React.Fragment>
                        <span className="tag">{unescape(tag.tag)}</span>
                        <span className="unread">
                            {tag.unread > 0 ? tag.unread : ''}
                        </span>
                        <ColorChooser tag={tag} onChange={colorChanged} />
                    </React.Fragment>
                )}
            </Link>
        </li>
    );
}

Tag.propTypes = {
    tag: nullable(PropTypes.object).isRequired,
    active: PropTypes.bool.isRequired,
    collapseNav: PropTypes.func.isRequired,
};

export default function NavTags({ setNavExpanded, tags }) {
    const [expanded, setExpanded] = useState(true);

    // useParams does not seem to work.
    const match = useRouteMatch(ENTRIES_ROUTE_PATTERN);
    const params = match !== null ? match.params : {};

    const currentAllTags = params.category === 'all';
    const currentTag = params.category?.startsWith('tag-')
        ? params.category.replace(/^tag-/, '')
        : null;

    const toggleExpanded = useCallback(
        () => setExpanded((expanded) => !expanded),
        [],
    );

    const collapseNav = useCallback(
        () => setNavExpanded(false),
        [setNavExpanded],
    );

    const _ = useContext(LocalizationContext);

    return (
        <React.Fragment>
            <h2>
                <button
                    type="button"
                    id="nav-tags-title"
                    className={classNames({
                        'nav-section-toggle': true,
                        'nav-tags-collapsed': !expanded,
                        'nav-tags-expanded': expanded,
                    })}
                    aria-expanded={expanded}
                    onClick={toggleExpanded}
                >
                    <FontAwesomeIcon
                        icon={
                            expanded
                                ? icons.arrowExpanded
                                : icons.arrowCollapsed
                        }
                        size="lg"
                        fixedWidth
                    />{' '}
                    {_('tags')}
                </button>
            </h2>
            <Collapse isOpen={expanded} className="collapse-css-transition">
                <ul id="nav-tags" aria-labelledby="nav-tags-title">
                    <Tag
                        tag={null}
                        active={currentAllTags}
                        collapseNav={collapseNav}
                    />
                    {tags.map((tag) => (
                        <Tag
                            key={tag.tag}
                            tag={tag}
                            active={currentTag === tag.tag}
                            collapseNav={collapseNav}
                        />
                    ))}
                </ul>
            </Collapse>
        </React.Fragment>
    );
}

NavTags.propTypes = {
    setNavExpanded: PropTypes.func.isRequired,
    tags: PropTypes.arrayOf(PropTypes.object).isRequired,
};
