import { TrivialResponse } from './common';
import { TagWithUnread } from './tags';
import * as ajax from '../helpers/ajax';

export type SourceWithUnread = {
    id: number;
    title: string;
    unread: number;
};

type UpdateResponse = {
    success: true;
    id: number;
    title: string;
    tags: Array<TagWithUnread>;
    sources: Array<SourceWithUnread>;
};

/**
 * Updates source with given ID.
 */
export function update(id: string, values: object): Promise<UpdateResponse> {
    return ajax
        .post(`source/${id}`, {
            headers: {
                'content-type': 'application/json; charset=utf-8',
            },
            body: JSON.stringify(values),
            failOnHttpErrors: false,
        })
        .promise.then(
            ajax.rejectUnless(
                (response) => response.ok || response.status === 400,
            ),
        )
        .then((response) => response.json());
}

/**
 * Triggers an update of the source with given ID.
 */
export function refreshSingle(id: number): Promise<Response> {
    return ajax.post('source/' + id + '/update', {
        timeout: 0,
    }).promise;
}

/**
 * Triggers an update of all sources.
 */
export function refreshAll(): Promise<string> {
    return ajax
        .get('update', {
            headers: {
                Accept: 'text/event-stream',
            },
            timeout: 0,
        })
        .promise.then((response) => response.text());
}

/**
 * Removes source with given ID.
 */
export function remove(id: number): Promise<TrivialResponse> {
    return ajax
        .delete_(`source/${id}`)
        .promise.then((response) => response.json());
}

enum SpoutParameterTypePlain {
    Text = 'text',
    Url = 'url',
    Password = 'password',
    Checkbox = 'checkbox',
}

enum SpoutParameterTypeSelect {
    Select = 'select',
}

enum SpoutParameterValidation {
    Alpha = 'alpha',
    Email = 'email',
    Numeric = 'numeric',
    Int = 'int',
    Alphanumeric = 'alnum',
    NonEmpty = 'notempty',
}

interface SpoutParameterInfoBase {
    title: string;
    default: string;
    required: boolean;
    validation: Array<SpoutParameterValidation>;
}

interface SpoutParameterInfoPlain extends SpoutParameterInfoBase {
    type: SpoutParameterTypePlain;
}

interface SpoutParameterInfoSelect extends SpoutParameterInfoBase {
    type: SpoutParameterTypeSelect;
    values: {
        [index: string]: string;
    };
}

type SpoutParameterInfo = SpoutParameterInfoPlain | SpoutParameterInfoSelect;

type Spout = {
    name: string;
    description: string;
    params: {
        [index: string]: SpoutParameterInfo;
    };
};

export type SourceWithIcon = {
    id: number;
    title: string;
    tags: Array<string>;
    spout: string;
    params: { [name: string]: string };
    filter: string | null;
    error: string | null;
    lastentry: number | null;
    icon: string | null;
};

type AllSourcesResponse = {
    spouts: { [key: string]: Spout };
    sources: Array<SourceWithIcon>;
};

/**
 * Gets all sources.
 */
export function getAllSources(
    abortController: AbortController,
): Promise<AllSourcesResponse> {
    return ajax
        .get('sources', {
            abortController,
        })
        .promise.then((response) => response.json());
}

type SpoutsResponse = {
    [key: string]: Spout;
};

/**
 * Gets list of supported spouts and their paramaters.
 */
export function getSpouts(): Promise<SpoutsResponse> {
    return ajax
        .get('sources/spouts')
        .promise.then((response) => response.json());
}

type SpoutParamsResponse = {
    id: string;
    spout: Spout;
};

/**
 * Gets parameters for given spout.
 */
export function getSpoutParams(
    spoutClass: string,
): Promise<SpoutParamsResponse> {
    return ajax
        .get('source/params', {
            body: ajax.makeSearchParams({ spout: spoutClass }),
        })
        .promise.then((res) => res.json());
}

/**
 * Gets source unread stats.
 */
export function getStats(): Promise<Array<SourceWithUnread>> {
    return ajax
        .get('sources/stats')
        .promise.then((response) => response.json());
}
