export default ({sources}) => sources.map(source =>
    <li>
        <a href="#" id={`source${source.id}`} class={(source.unread > 0) ? 'unread' : ''} data-source-id={`${source.id}`}>
            <span class="nav-source">{`${source.title}`}</span>
            <span class="unread">{`${(source.unread > 0) ? `${source.unread}` : ''}`}</span>
        </a>
    </li>
);
