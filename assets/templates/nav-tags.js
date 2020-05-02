export default ({tags}) =>
    [
        <li><a class="active nav-tags-all" href="#">{selfoss.ui._('alltags')}</a></li>
    ].concat(tags.map(tag =>
        <li>
            <a href="#">
                <span class="tag">{`${tag.tag}`}</span>
                <span class="unread">{`${(tag.unread > 0) ? tag.unread : ''}`}</span>
                <span class="color" style={`background-color: ${tag.color}`}></span>
            </a>
        </li>
    ))
