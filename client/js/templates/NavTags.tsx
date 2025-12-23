import React, { use, useMemo, useCallback, useState } from 'react';
import { Link, useLocation } from 'react-router';
import classNames from 'classnames';
import { unescape } from 'html-escaper';
import selfoss from '../selfoss-base';
import {
    useForceReload,
    makeEntriesLinkLocation,
    useEntriesParams,
} from '../helpers/uri';
import ColorChooser from './ColorChooser';
import { updateTag } from '../requests/tags';
import { Collapse } from '@kunukn/react-collapse';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';
import { LocalizationContext } from '../helpers/i18n';
import { NavTag } from '../requests/items';
import { ClickEvent } from '@szhsin/react-menu';

type TagProps = {
    tag: NavTag | null;
    active: boolean;
    collapseNav: () => void;
    showError: (message: string) => void;
};

function Tag(props: TagProps): React.JSX.Element {
    const { tag, active, collapseNav, showError } = props;

    const _ = use(LocalizationContext);
    const tagName = tag !== null ? tag.tag : null;

    const colorChanged = useCallback(
        ({ value }: ClickEvent) => {
            updateTag(tagName, value)
                .then(() => {
                    selfoss.entriesPage?.reload();
                })
                .catch((error) => {
                    showError(_('error_saving_color') + ' ' + error.message);
                });
        },
        [tagName, showError, _],
    );

    const category = tag === null ? 'all' : `tag-${tag.tag}`;
    const location = useLocation();
    const link = useMemo(
        () =>
            makeEntriesLinkLocation(location, {
                category,
                id: null,
            }),
        [category, location],
    );

    const forceReload = useForceReload();

    return (
        <li className={classNames({ read: tag !== null && tag.unread === 0 })}>
            <Link
                to={link}
                className={classNames({ 'nav-tags-all': tag === null, active })}
                onClick={collapseNav}
                state={forceReload}
            >
                {tag === null ? (
                    _('alltags')
                ) : (
                    <>
                        <span className="tag">{unescape(tag.tag)}</span>
                        <span className="unread">
                            {tag.unread > 0 ? tag.unread : ''}
                        </span>
                        <ColorChooser tag={tag} onChange={colorChanged} />
                    </>
                )}
            </Link>
        </li>
    );
}

type NavTagsProps = {
    setNavExpanded: React.Dispatch<React.SetStateAction<boolean>>;
    tags: Array<NavTag>;
    showError: (message: string) => void;
};

export default function NavTags(props: NavTagsProps): React.JSX.Element {
    const { setNavExpanded, tags, showError } = props;

    const [expanded, setExpanded] = useState(true);

    const params = useEntriesParams();

    const currentAllTags = params?.category === 'all';
    const currentTag = params?.category?.startsWith('tag-')
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

    const _ = use(LocalizationContext);

    return (
        <>
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
                        showError={showError}
                    />
                    {tags.map((tag) => (
                        <Tag
                            key={tag.tag}
                            tag={tag}
                            active={currentTag === tag.tag}
                            collapseNav={collapseNav}
                            showError={showError}
                        />
                    ))}
                </ul>
            </Collapse>
        </>
    );
}
