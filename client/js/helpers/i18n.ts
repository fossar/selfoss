import React from 'react';

/*
 * This is a naive and partial implementation for parsing the
 * local-aware formatted strings from the Fat-Free Framework.
 * The full spec is at https://fatfreeframework.com/3.6/base#format and is
 * not fully implemented.
 */
export function i18nFormat(translated, params) {
    let formatted = '';

    let curChar;
    let buffer = '';

    let state = 'out';
    let placeholder;
    let plural;
    let pluralKeyword;
    let pluralValue;

    for (let i = 0, len = translated.length; i < len; i++) {
        curChar = translated.charAt(i);
        switch (curChar) {
            case '{':
                if (placeholder) {
                    if (state == 'plural') {
                        pluralKeyword = buffer.trim();
                        if (['zero', 'one', 'other'].includes(pluralKeyword)) {
                            buffer = '';
                        } else {
                            pluralKeyword = undefined;
                        }
                    }
                } else {
                    formatted = formatted + buffer;
                    buffer = '';
                    placeholder = {};
                    state = 'index';
                }
                break;
            case '}':
            case ',':
                if (placeholder) {
                    if (state == 'index') {
                        const index = buffer.trim();
                        const intIndex = parseInt(index);
                        placeholder.index = Number.isNaN(intIndex)
                            ? index
                            : intIndex;
                        placeholder.value = params[placeholder.index];
                        buffer = '';
                    } else if (state == 'type') {
                        placeholder.type = buffer.trim();
                        buffer = '';
                        if (placeholder.type == 'plural') {
                            plural = {};
                            state = 'plural';
                        }
                    }
                    if (curChar == '}') {
                        if (state == 'plural' && pluralKeyword) {
                            plural[pluralKeyword] = buffer;
                            buffer = '';
                            pluralKeyword = undefined;
                        } else if (plural) {
                            if ('zero' in plural && placeholder.value === 0) {
                                pluralValue = plural.zero;
                            } else if (
                                'one' in plural &&
                                placeholder.value == 1
                            ) {
                                pluralValue = plural.one;
                            } else {
                                pluralValue = plural.other;
                            }
                            formatted =
                                formatted +
                                pluralValue.replace('#', placeholder.value);
                            plural = undefined;
                            placeholder = undefined;
                            state = 'out';
                        } else {
                            formatted = formatted + placeholder.value;
                            placeholder = undefined;
                            state = 'out';
                        }
                    } else if (curChar == ',' && state == 'index') {
                        state = 'type';
                    }
                }
                break;
            default:
                buffer = buffer + curChar;
                break;
        }
    }

    if (state != 'out') {
        return "Error formatting '" + translated + "', bug report?";
    }

    formatted = formatted + buffer;

    return formatted;
}

export const LocalizationContext = React.createContext();
