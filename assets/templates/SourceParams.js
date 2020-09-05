import React from 'jsx-dom';

export default function SourceParams({spout, params = null, sourceId}) {
    return (
        <ul>
            {Object.entries(spout.params).map(([spoutParamName, spoutParam]) => {
                let checked = false;

                let value = spoutParam.default;
                if (params !== null && Object.keys(params).includes(spoutParamName)) {
                    value = params[spoutParamName];
                    if (spoutParam.type === 'checkbox' && value == '1') {
                        checked = true;
                    }
                }

                if (['text', 'checkbox', 'url'].includes(spoutParam.type)) {
                    return (
                        <li>
                            <label for={`${spoutParamName}-${sourceId}`}>{spoutParam.title}</label>
                            <input id={`${spoutParamName}-${sourceId}`} type={spoutParam.type}
                                name={spoutParamName}
                                value={value}
                                checked={checked} />
                        </li>
                    );
                }

                if (spoutParam.type === 'password') {
                    return (
                        <li>
                            <label for={`${spoutParamName}-${sourceId}`}>{spoutParam.title}</label>
                            <input id={`${spoutParamName}-${sourceId}`} type={spoutParam.type}
                                name={spoutParamName}
                                placeholder={selfoss.ui._('source_pwd_placeholder')} />
                        </li>
                    );
                } else if (spoutParam.type === 'select') {
                    return (
                        <li>
                            <label for={`${spoutParamName}-${sourceId}`}>{spoutParam.title}</label>
                            <select id={`${spoutParamName}-${sourceId}`} name={spoutParamName} size="1">
                                {Object.entries(spoutParam.values).map(([optionName, optionTitle]) =>
                                    <option value={optionName} selected={optionName === value}>{optionTitle}</option>
                                )}
                            </select>
                        </li>
                    );
                } else {
                    return null;
                }
            })}
        </ul>
    );
}
