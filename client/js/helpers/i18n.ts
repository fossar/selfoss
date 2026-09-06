import React from 'react';
import { MessageKey } from '../locales';

type PluralKw = 'zero' | 'one' | 'other';

enum FmtState {
    Out,
    Plural,
    Index,
    Type,
}

/*
 * This is a naive and partial implementation for parsing the
 * local-aware formatted strings from the Fat-Free Framework.
 * The full spec is at https://fatfreeframework.com/3.6/base#format and is
 * not fully implemented.
 */
export function i18nFormat(
    translated: string,
    params?: { [index: string]: number | string },
): string {
    let formatted = '';

    let curChar: string;
    let buffer = '';

    let state = FmtState.Out;
    let placeholder:
        | {
              index?: string;
              value?: number | string;
              type?: string;
          }
        | undefined;
    let plural: Partial<Record<PluralKw, string>> | undefined;
    let pluralKeyword: PluralKw | undefined;
    let pluralValue: string | undefined;

    for (let i = 0, len = translated.length; i < len; i++) {
        curChar = translated.charAt(i);
        switch (curChar) {
            case '{':
                if (placeholder) {
                    if (state === FmtState.Plural) {
                        const kw = buffer.trim();
                        if (kw === 'zero' || kw === 'one' || kw === 'other') {
                            pluralKeyword = kw;
                            buffer = '';
                        } else {
                            pluralKeyword = undefined;
                        }
                    }
                } else {
                    formatted = formatted + buffer;
                    buffer = '';
                    placeholder = {};
                    state = FmtState.Index;
                }
                break;
            case '}':
            case ',':
                if (placeholder) {
                    if (state === FmtState.Index) {
                        placeholder.index = buffer.trim();
                        placeholder.value = params[placeholder.index];
                        buffer = '';
                    } else if (state === FmtState.Type) {
                        placeholder.type = buffer.trim();
                        buffer = '';
                        if (placeholder.type === 'plural') {
                            plural = {};
                            state = FmtState.Plural;
                        }
                    }
                    if (curChar === '}') {
                        if (state === FmtState.Plural && pluralKeyword) {
                            plural[pluralKeyword] = buffer;
                            buffer = '';
                            pluralKeyword = undefined;
                        } else if (plural) {
                            if ('zero' in plural && placeholder.value === 0) {
                                pluralValue = plural.zero;
                            } else if (
                                'one' in plural &&
                                placeholder.value === 1
                            ) {
                                pluralValue = plural.one;
                            } else {
                                pluralValue = plural.other;
                            }
                            formatted =
                                formatted +
                                pluralValue.replace(
                                    '#',
                                    placeholder.value.toString(),
                                );
                            plural = undefined;
                            placeholder = undefined;
                            state = FmtState.Out;
                        } else {
                            formatted = formatted + placeholder.value;
                            placeholder = undefined;
                            state = FmtState.Out;
                        }
                    } else if (curChar === ',' && state === FmtState.Index) {
                        state = FmtState.Type;
                    }
                }
                break;
            default:
                buffer = buffer + curChar;
                break;
        }
    }

    if (state !== FmtState.Out) {
        return "Error formatting '" + translated + "', bug report?";
    }

    formatted = formatted + buffer;

    return formatted;
}

export const LocalizationContext = React.createContext<Translate>(() => {
    throw new Error('Missing l10n context');
});

export type Translate = (
    translated: MessageKey,
    params?: { [index: string]: string | number },
) => string;
