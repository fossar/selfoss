import { filterTypeToString } from '../helpers/uri';
import React from 'react';
import ReactDOM from 'react-dom';
import classNames from 'classnames';
import { unescape } from 'html-escaper';
import { updateTag } from '../requests/tags';

function handleClick(e, tag) {
    e.preventDefault();

    if (!selfoss.db.online) {
        return;
    }

    if (tag !== null) {
        selfoss.events.setHash(filterTypeToString(selfoss.filter.type), `tag-${tag}`);
    } else {
        selfoss.events.setHash(filterTypeToString(selfoss.filter.type), 'all');
    }

    selfoss.ui.hideMobileNav();
}

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
                    selfoss.ui.beforeReloadList();
                    selfoss.dbOnline.reloadList();
                    selfoss.ui.afterReloadList();
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

function NavTags({tagsRepository, filter}) {
    const [currentAllTags, setCurrentAllTags] = React.useState(filter.tag === null && filter.source === null);
    const [currentTag, setCurrentTag] = React.useState(filter.tag);
    const [tags, setTags] = React.useState(tagsRepository.tags);

    React.useEffect(() => {
        const filterListener = (event) => {
            setCurrentAllTags(event.filter.tag === null && event.filter.source === null);
            setCurrentTag(event.filter.tag);
        };
        const tagsListener = (event) => {
            setTags(event.tags);
        };

        // It might happen that filter changes between creating the component and setting up the event handlers.
        filterListener({ filter });
        tagsListener({ tags: tagsRepository.tags });

        filter.addEventListener('change', filterListener);
        tagsRepository.addEventListener('change', tagsListener);

        return () => {
            filter.removeEventListener('change', filterListener);
            tagsRepository.removeEventListener('change', tagsListener);
        };
    }, [tagsRepository, filter]);

    return (
        <React.Fragment>
            <li><a className={classNames({'nav-tags-all': true, active: currentAllTags})} href="#" onClick={(e) => handleClick(e, null)}>{selfoss.ui._('alltags')}</a></li>
            {tags.map(tag =>
                <li key={tag.tag}>
                    <a className={classNames({active: currentTag === tag.tag})} href="#" onClick={(e) => handleClick(e, tag.tag)}>
                        <span className="tag">{unescape(tag.tag)}</span>
                        <span className="unread">{tag.unread > 0 ? tag.unread : ''}</span>
                        <ColorChooser tag={tag} />
                    </a>
                </li>
            )}
        </React.Fragment>
    );
}

export function anchor(element, tags, filter) {
    ReactDOM.render(<NavTags tagsRepository={tags} filter={filter} />, element);
}
