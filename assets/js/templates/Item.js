import React from 'jsx-dom';

function anonymize(url) {
    return (selfoss.config.anonymizer ?? '') + url;
}

export default function Item({item}) {
    let { title, author, content, sourcetitle } = item;

    return (
        <div data-entry-id={item.id}
            data-entry-source={item.source}
            data-entry-datetime={item.datetime.toISOString()}
            data-entry-url={item.link}
            class={['entry', item.unread == 1 ? 'unread' : null]} role="article">

            {/* icon */}
            <a href={anonymize(item.link)} class="entry-icon" tabindex="-1" rel="noopener noreferrer" aria-hidden="true">
                {item.icon !== null && item.icon.trim().length > 0 && item.icon != '0' ?
                    <img src={`favicons/${item.icon}`} aria-hidden="true" alt="" />
                    : null}
            </a>

            {/* title */}
            <h3 class="entry-title"><span class="entry-title-link" aria-expanded="false" role="link" tabindex="0" innerHTML={title} /></h3>

            <span class="entry-tags">
                {Object.entries(item.tags).map(([tag, color]) =>
                    <span class="entry-tags-tag" style={{color: color['foreColor'], 'background-color': color['backColor']}}>{tag}</span>
                )}
            </span>

            {/* source */}
            <span class="entry-source" innerHTML={sourcetitle} />

            <span class="entry-separator">•</span>

            {/* author */}
            {author.trim() !== '' ?
                <React.Fragment>
                    <span class="entry-author">{author}</span>
                    <span class="entry-separator">•</span>
                </React.Fragment>
                : null}

            {/* datetime */}
            <a href={anonymize(item.link)} class="entry-datetime" target="_blank" rel="noopener noreferrer"></a>

            {/* read time */}
            {selfoss.config.readingSpeed !== null ?
                <React.Fragment>
                    <span class="entry-separator">•</span>
                    <span class="entry-readtime">{selfoss.ui._('article_reading_time', [Math.round(item.wordCount / selfoss.config.readingSpeed)])}</span>
                </React.Fragment>
                : null}

            {/* thumbnail */}
            {selfoss.config.showThumbnails && item.thumbnail && item.thumbnail.trim().length > 0 ?
                <div class="entry-thumbnail">
                    <a href={anonymize(item.link)} target="_blank" rel="noopener noreferrer">
                        <img src={`thumbnails/${item.thumbnail}`} alt={item.strippedTitle} />
                    </a>
                </div>
                : null}

            {/* content */}
            <div class={['entry-content', item.lengthWithoutTags < 500 ? 'entry-content-nocolumns' : null]}>
                <div innerHTML={content} />

                <div class="entry-smartphone-share">
                    <ul aria-label={selfoss.ui._('article_actions')}>
                        <li><button accesskey="o" aria-haspopup="true" class="entry-newwindow"><i class="fas fa-external-link-alt"></i> {selfoss.ui._('open_window')}</button></li>
                        <li><button accesskey="n" class="entry-next"><i class="fas fa-arrow-right"></i> {selfoss.ui._('next')}</button></li>
                    </ul>
                </div>
            </div>

            {/* toolbar */}
            <ul aria-label={selfoss.ui._('article_actions')} class="entry-toolbar">
                <li><button accesskey="a" class={['entry-starr', item.starred == 1 ? 'active' : null]}><i class={[`fa${item.starred == 1 ? 's' : 'r'}`, 'fa-star']}></i> {item.starred == 1 ? selfoss.ui._('unstar') : selfoss.ui._('star')}</button></li>
                <li><button accesskey="u" class={['entry-unread', item.unread == 1 ? 'active' : null]}><i class={[`fa${item.unread == 1 ? 's' : 'r'}`, 'fa-check-circle']}></i> {item.unread == 1 ? selfoss.ui._('mark') : selfoss.ui._('unmark')}</button></li>
                <li><a href={anonymize(item.link)} class="entry-newwindow" target="_blank" rel="noopener noreferrer" accesskey="o"><i class="fas fa-external-link-alt"></i> {selfoss.ui._('open_window')}</a></li>
                <li><button class="entry-loadimages"><i class="fas fa-arrow-alt-circle-down"></i> {selfoss.ui._('load_img')}</button></li>
                <li><button accesskey="n" class="entry-next"><i class="fas fa-arrow-right"></i> {selfoss.ui._('next')}</button></li>
                <li><button accesskey="c" class="entry-close"><i class="far fa-times-circle"></i> {selfoss.ui._('close_entry')}</button></li>
            </ul>
        </div>
    );
}
