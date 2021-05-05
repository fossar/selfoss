import React from 'react';
import PropTypes from 'prop-types';
import nullable from 'prop-types-nullable';
import { Link, useLocation, useRouteMatch } from 'react-router-dom';
import classNames from 'classnames';
import { unescape } from 'html-escaper';
import { makeEntriesLink, ENTRIES_ROUTE_PATTERN } from '../helpers/uri';
import { updateTag } from '../requests/tags';
import Collapse from '@kunukn/react-collapse';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';
import { LocalizationContext } from '../helpers/i18n';

function ColorChooser({tag}) {
    const colorChooser = React.useRef(null);

    React.useLayoutEffect(() => {
        // init colorpicker
        const picker = colorChooser.current;
        $(picker).spectrum({
            showPaletteOnly: true,
            color: 'blanchedalmond',
            palette: [
                ['#ffccc9', '#ffce93', '#fffc9e', '#ffffc7', '#9aff99', '#96fffb', '#cdffff', '#cbcefb', '#fffe65', '#cfcfcf', '#fd6864', '#fe996b', '#fcff2f', '#67fd9a', '#38fff8', '#68fdff', '#9698ed', '#c0c0c0', '#fe0000', '#f8a102', '#ffcc67', '#f8ff00', '#34ff34', '#68cbd0', '#34cdf9', '#6665cd', '#9b9b9b', '#cb0000', '#f56b00', '#ffcb2f', '#ffc702', '#32cb00', '#00d2cb', '#3166ff', '#6434fc', '#656565', '#9a0000', '#ce6301', '#cd9934', '#999903', '#009901', '#329a9d', '#3531ff', '#6200c9', '#343434', '#680100', '#963400', '#986536', '#646809', '#036400', '#34696d', '#00009b', '#303498', '#000000', '#330001', '#643403', '#663234', '#343300', '#013300', '#003532', '#010066', '#340096']
            ],
            change: function(color) {
                updateTag(
                    tag.tag,
                    color.toHexString()
                ).then(() => {
                    selfoss.entriesPage?.reloadList();
                }).catch((error) => {
                    selfoss.app.showError(selfoss.app._('error_saving_color') + ' ' + error.message);
                });

            }
        });

        return () => {
            $(picker).spectrum('destroy');
        };
    }, [tag.tag]);

    const style = React.useMemo(
        () => ({ backgroundColor: tag.color }),
        [tag.color]
    );

    return (
        <span className="color" style={style} ref={colorChooser} />
    );
}

ColorChooser.propTypes = {
    tag: PropTypes.object.isRequired,
};

function Tag({ tag, active, collapseNav }) {
    const location = useLocation();
    const _ = React.useContext(LocalizationContext);

    return (
        <li>
            <Link
                to={makeEntriesLink(location, {
                    category: tag === null ? 'all' : `tag-${tag.tag}`,
                    id: null
                })}
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
                        <ColorChooser tag={tag} />
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
    const [expanded, setExpanded] = React.useState(true);

    // useParams does not seem to work.
    const match = useRouteMatch(ENTRIES_ROUTE_PATTERN);
    const params = match !== null ? match.params : {};

    const currentAllTags = params.category === 'all';
    const currentTag = params.category?.startsWith('tag-') ? params.category.replace(/^tag-/, '') : null;

    const toggleExpanded = React.useCallback(
        () => setExpanded((expanded) => !expanded),
        []
    );

    const collapseNav = React.useCallback(
        () => setNavExpanded(false),
        [setNavExpanded]
    );

    const _ = React.useContext(LocalizationContext);

    return (
        <React.Fragment>
            <h2><button type="button" id="nav-tags-title" className={classNames({'nav-section-toggle': true, 'nav-tags-collapsed': !expanded, 'nav-tags-expanded': expanded})} aria-expanded={expanded} onClick={toggleExpanded}><FontAwesomeIcon icon={expanded ? icons.arrowExpanded : icons.arrowCollapsed} size="lg" fixedWidth />  {_('tags')}</button></h2>
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
