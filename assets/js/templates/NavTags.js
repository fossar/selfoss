import { unescape } from 'html-escaper';
import React from 'jsx-dom';

export default function NavTags({tags}) {
    return (
        <React.Fragment>
            <li><a class="active nav-tags-all" href="#">{selfoss.ui._('alltags')}</a></li>
            {tags.map(tag =>
                <li>
                    <a href="#">
                        <span class="tag">{unescape(tag.tag)}</span>
                        <span class="unread">{tag.unread > 0 ? tag.unread : ''}</span>
                        <span class="color" style={`background-color: ${tag.color}`}></span>
                    </a>
                </li>
            )}
        </React.Fragment>
    );
}
