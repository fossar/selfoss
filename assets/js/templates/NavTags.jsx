import React from 'react';
import { Link, useLocation, useRouteMatch } from 'react-router-dom';
import classNames from 'classnames';
import { unescape } from 'html-escaper';
import { makeEntriesLink, ENTRIES_ROUTE_PATTERN } from '../helpers/uri';
import { updateTag } from '../requests/tags';
import Collapse from '@kunukn/react-collapse';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';

function ColorChooser({tag}) {
    const colorChooser = React.useRef(null);

    React.useLayoutEffect(() => {
        // init colorpicker
        $(colorChooser.current).spectrum({
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
                    selfoss.ui.showError(selfoss.ui._('error_saving_color') + ' ' + error.message);
                });

            }
        });

        return () => {
            $(colorChooser.current).spectrum('destroy');
        };
    }, [tag.tag]);

    return (
        <span className="color" style={{backgroundColor: tag.color}} ref={colorChooser} />
    );
}

export default function NavTags({ tagsRepository, setNavExpanded }) {
    const [expanded, setExpanded] = React.useState(true);
    const [tags, setTags] = React.useState(tagsRepository.tags);

    const location = useLocation();
    // useParams does not seem to work.
    const match = useRouteMatch(ENTRIES_ROUTE_PATTERN);
    const params = match !== null ? match.params : {};

    const currentAllTags = params.category === 'all';
    const currentTag = params.category?.startsWith('tag-') ? params.category.replace(/^tag-/, '') : null;

    React.useEffect(() => {
        const tagsListener = (event) => {
            setTags(event.tags);
        };

        // It might happen that filter changes between creating the component and setting up the event handlers.
        tagsListener({ tags: tagsRepository.tags });

        tagsRepository.addEventListener('change', tagsListener);

        return () => {
            tagsRepository.removeEventListener('change', tagsListener);
        };
    }, [tagsRepository]);

    return (
        <React.Fragment>
            <h2><button type="button" id="nav-tags-title" className={classNames({'nav-section-toggle': true, 'nav-tags-collapsed': !expanded, 'nav-tags-expanded': expanded})} aria-expanded={expanded} onClick={() => setExpanded((expanded) => !expanded)}><FontAwesomeIcon icon={expanded ? icons.arrowExpanded : icons.arrowCollapsed} size="lg" fixedWidth />  {selfoss.ui._('tags')}</button></h2>
            <Collapse isOpen={expanded} className="collapse-css-transition">
                <ul id="nav-tags" aria-labelledby="nav-tags-title">
                    <li><Link to={makeEntriesLink(location, { category: 'all', id: null })} className={classNames({'nav-tags-all': true, active: currentAllTags})} onClick={() => setNavExpanded(false)}>{selfoss.ui._('alltags')}</Link></li>
                    {tags.map(tag =>
                        <li key={tag.tag}>
                            <Link to={makeEntriesLink(location, { category: `tag-${tag.tag}`, id: null })} className={classNames({active: currentTag === tag.tag})} onClick={() => setNavExpanded(false)}>
                                <span className="tag">{unescape(tag.tag)}</span>
                                <span className="unread">{tag.unread > 0 ? tag.unread : ''}</span>
                                <ColorChooser tag={tag} />
                            </Link>
                        </li>
                    )}
                </ul>
            </Collapse>
        </React.Fragment>
    );
}
