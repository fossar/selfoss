import React from 'react';
import { useRouteMatch } from 'react-router-dom';
import Source from './Source';
import * as sourceRequests from '../requests/sources';
import { getAllSources } from '../requests/sources';

function rand() {
    // https://www.php.net/manual/en/function.mt-getrandmax.php#117620
    return Math.floor(Math.random() * 2147483647);
}

function handleAddSource({ event, setSources, setSpouts }) {
    event.preventDefault();

    // add new source
    sourceRequests
        .getSpouts()
        .then(({ spouts }) => {
            // Update spout data.
            setSpouts(spouts);
            // Add new empty source.
            setSources((sources) => [{ id: 'new-' + rand() }, ...sources]);
        })
        .catch((error) => {
            selfoss.ui.showError(
                selfoss.ui._('error_add_source') + ' ' + error.message
            );
        });
}

function loadSources() { // load sources
    if (selfoss.activeAjaxReq !== null) {
        selfoss.activeAjaxReq.controller.abort();
    }
    selfoss.activeAjaxReq = getAllSources();
    selfoss.activeAjaxReq.promise.then(({sources, spouts}) => {
        selfoss.sourcesPage.setSpouts(spouts);
        selfoss.sourcesPage.setSources(sources);
    }).catch((error) => {
        if (error.name === 'AbortError') {
            return;
        }

        selfoss.handleAjaxError(error, false).catch(function(error) {
            selfoss.ui.showError(selfoss.ui._('error_loading') + ' ' + error.message);
        });
    });
}

export function SourcesPage({ sources, setSources, spouts, setSpouts }) {
    const match = useRouteMatch();

    React.useEffect(() => {
        loadSources();
    }, [match]);

    return (
        <React.Fragment>
            <button
                className="source-add"
                onClick={(event) =>
                    handleAddSource({ event, setSources, setSpouts })
                }
            >
                {selfoss.ui._('source_add')}
            </button>
            <a className="source-export" href="opmlexport">
                {selfoss.ui._('source_export')}
            </a>
            <a className="source-opml" href="opml">
                {selfoss.ui._('source_opml')}
            </a>
            {sources.map((source) => (
                <Source
                    key={source.id}
                    {...{ source, setSources, spouts, setSpouts }}
                />
            ))}
        </React.Fragment>
    );
}

export default class StateHolder extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            sources: [],
            spouts: []
        };
    }

    setSources(sources) {
        if (typeof sources === 'function') {
            this.setState({ sources: sources(this.state.sources) });
        } else {
            this.setState({ sources });
        }
    }

    setSpouts(spouts) {
        if (typeof spouts === 'function') {
            this.setState({ spouts: spouts(this.state.spouts) });
        } else {
            this.setState({ spouts });
        }
    }

    render() {
        return (
            <SourcesPage
                sources={this.state.sources}
                setSources={this.setSources.bind(this)}
                spouts={this.state.spouts}
                setSpouts={this.setSpouts.bind(this)}
            />
        );
    }
}
