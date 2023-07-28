import React, { useCallback, useContext } from 'react';
import PropTypes from 'prop-types';
import { LocalizationContext } from '../helpers/i18n';

export default function SourceParam({
    spoutParamName,
    spoutParam,
    params = {},
    sourceErrors,
    sourceId,
    setEditedSource,
    setDirty,
}) {
    const updateSourceParam = useCallback(
        (event) => {
            setDirty(true);
            setEditedSource(({ params, ...restSource }) => ({
                ...restSource,
                params: {
                    ...params,
                    [spoutParamName]: event.target.value,
                },
            }));
        },
        [setEditedSource, setDirty, spoutParamName],
    );

    const updateSourceParamBool = useCallback(
        (event) =>
            updateSourceParam({
                target: {
                    value: event.target.checked ? '1' : undefined,
                },
            }),
        [updateSourceParam],
    );

    let value =
        spoutParamName in params ? params[spoutParamName] : spoutParam.default;
    let control = null;

    const _ = useContext(LocalizationContext);

    if (['text', 'checkbox', 'url'].includes(spoutParam.type)) {
        let checked;

        if (spoutParam.type === 'checkbox') {
            checked = value == '1';
            // Value always has to be 1 since HTML sends [name]=[value] when a checkbox is checked
            // and omits the field altogether from HTTP request when not checked.
            value = '1';
        }

        control = (
            <input
                id={`${spoutParamName}-${sourceId}`}
                type={spoutParam.type}
                name={spoutParamName}
                value={value}
                checked={checked}
                onChange={
                    spoutParam.type !== 'checkbox'
                        ? updateSourceParam
                        : updateSourceParamBool
                }
            />
        );
    } else if (spoutParam.type === 'password') {
        control = (
            <input
                id={`${spoutParamName}-${sourceId}`}
                type={spoutParam.type}
                name={spoutParamName}
                placeholder={_('source_pwd_placeholder')}
                onChange={updateSourceParam}
            />
        );
    } else if (spoutParam.type === 'select') {
        control = (
            <select
                id={`${spoutParamName}-${sourceId}`}
                name={spoutParamName}
                size="1"
                value={value}
                onChange={updateSourceParam}
            >
                {Object.entries(spoutParam.values).map(
                    ([optionName, optionTitle]) => (
                        <option key={optionName} value={optionName}>
                            {optionTitle}
                        </option>
                    ),
                )}
            </select>
        );
    } else {
        return null;
    }

    return (
        <li>
            <label htmlFor={`${spoutParamName}-${sourceId}`}>
                {spoutParam.title}
            </label>
            {control}
            {sourceErrors[spoutParamName] ? (
                <span className="error">{sourceErrors[spoutParamName]}</span>
            ) : null}
        </li>
    );
}

SourceParam.propTypes = {
    spoutParamName: PropTypes.string.isRequired,
    spoutParam: PropTypes.object.isRequired,
    params: PropTypes.object.isRequired,
    sourceErrors: PropTypes.objectOf(PropTypes.string).isRequired,
    sourceId: PropTypes.number.isRequired,
    setEditedSource: PropTypes.func.isRequired,
    setDirty: PropTypes.func.isRequired,
};
