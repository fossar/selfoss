import React from 'jsx-dom';
import { unescape } from 'html-escaper';
import SourceParams from './SourceParams';

// Taken from https://stackoverflow.com/a/15289883/160386
const MS_PER_DAY = 1000 * 60 * 60 * 24;

function daysAgo(date) {
    // Get number of days between now and when the last entry was seen
    // Note: The time of the two dates is set to midnight
    // to get the difference of the two dates in calendar days
    // instead of a day equaling any 24 hour period which makes it
    // impossible to distinguish today and yesterday.
    const now = new Date();
    const today = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate());
    const old = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());

    return Math.floor((today - old) / MS_PER_DAY);
}

function rand() {
    // https://www.php.net/manual/en/function.mt-getrandmax.php#117620
    return Math.floor(Math.random() * 2147483647);
}

export default function Source({source = null, spouts}) {
    let sourceId = source ? source.id : 'new-' + rand();
    let classes = [
        'source',
        source === null ? 'source-new' : null,
        (source !== null && source.error && source.error.length > 0) ? 'error' : null
    ];

    return (
        <form data-source-id={sourceId}
            class={classes}>
            <div class="source-icon">
                {source && source.icon && source.icon != '0' ?
                    <img src={`favicons/${source.icon}`} aria-hidden="true" alt="" />
                    : null}
            </div>

            <div class="source-title">{source !== null ? unescape(source.title) : selfoss.ui._('source_new')}</div>

            {' '}

            <div class="source-edit-delete">
                <button type="button" accesskey="e" class="source-showparams">
                    {selfoss.ui._('source_edit')}
                </button>
                {' • '}
                <button type="button" accesskey="d" class="source-delete">
                    {selfoss.ui._('source_delete')}
                </button>
            </div>

            <div class="source-days">
                {source !== null && source.lastentry ?
                    ` • ${selfoss.ui._('source_last_post')} ${selfoss.ui._('days', [daysAgo(new Date(source.lastentry * 1000))])}`
                    : null}
            </div>


            {/* edit */}
            <ul class="source-edit-form">
                {/* title */}
                <li>
                    <label for={`title-${sourceId}`}>{selfoss.ui._('source_title')}</label>
                    <input id={`title-${sourceId}`} type="text" name="title" accesskey="t" value={source !== null ? unescape(source.title) : ''} placeholder={selfoss.ui._('source_autotitle_hint')} />
                </li>


                {/* tags */}
                <li>
                    <label for={`tags-${sourceId}`}>{selfoss.ui._('source_tags')}</label>
                    <input id={`tags-${sourceId}`} type="text" name="tags" accesskey="g" value={source !== null ? source.tags.map(unescape).join(',') : ''} />
                    <span class="source-edit-form-help"> {selfoss.ui._('source_comma')}</span>
                </li>

                {/* filter */}
                <li>
                    <label for={`filter-${sourceId}`}>{selfoss.ui._('source_filter')}</label>
                    <input id={`filter-${sourceId}`} type="text" name="filter" accesskey="f" value={source !== null ? source.filter : ''} />
                </li>

                {/* type */}
                <li>
                    <label for={`type-${sourceId}`}>{selfoss.ui._('source_type')}</label>
                    <select id={`type-${sourceId}`} class="source-spout" name="spout" accesskey="y">
                        <option value="">{selfoss.ui._('source_select')}</option>
                        {Object.entries(spouts).map(([spouttype, spout]) =>
                            <option title={spout.description} value={spouttype.replaceAll('\\', '_')} selected={source !== null && spouttype === source.spout}>
                                {spout.name}
                            </option>
                        )}
                    </select>
                </li>

                {/* settings */}
                <li class="source-params">
                    {(source !== null && Object.keys(spouts).includes(source.spout) && Object.keys(spouts[source.spout].params).length > 0) ?
                        SourceParams({
                            spout: spouts[source.spout],
                            params: source.params,
                            sourceId
                        })
                        : null}
                </li>

                {/* error messages */}
                {source !== null && source.error ?
                    <li class="source-error" aria-live="assertive">
                        {source.error}
                    </li>
                    : null}

                {/* save/delete */}
                <li class="source-action">
                    <button type="submit" class="source-save" accesskey="s">{selfoss.ui._('source_save')}</button>{' • '}<button type="submit" class="source-cancel" accesskey="c">{selfoss.ui._('source_cancel')}</button>
                </li>
            </ul>


        </form>);
}
