import React, { useCallback, useContext } from 'react';
import { LocalizationContext } from '../helpers/i18n';

type SourceParamProps = {
    spoutParamName: string;
    spoutParam: object;
    params: object;
    sourceErrors: { [index: string]: string };
    sourceId: number;
    setEditedSource: React.Dispatch<React.SetStateAction<object | null>>;
    setDirty: React.Dispatch<React.SetStateAction<boolean>>;
};

export default function SourceParam(props: SourceParamProps) {
    const {
        spoutParamName,
        spoutParam,
        params = {},
        sourceErrors,
        sourceId,
        setEditedSource,
        setDirty,
    } = props;

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
