// Unfortunately we need to declare everything manually for Parcel to pick those up.

import ca from '../locale/ca.json';
import cs from '../locale/cs.json';
import de from '../locale/de.json';
import en from '../locale/en.json';
import enGB from '../locale/en-GB.json';
import es from '../locale/es.json';
import et from '../locale/et.json';
import fi from '../locale/fi.json';
import fr from '../locale/fr.json';
import frCA from '../locale/fr-CA.json';
import gl from '../locale/gl.json';
import he from '../locale/he.json';
import hu from '../locale/hu.json';
import id from '../locale/id.json';
import it from '../locale/it.json';
import ja from '../locale/ja.json';
import lv from '../locale/lv.json';
import nb from '../locale/nb.json';
import nl from '../locale/nl.json';
import pl from '../locale/pl.json';
import pt from '../locale/pt.json';
import ptBR from '../locale/pt-BR.json';
import rm from '../locale/rm.json';
import ru from '../locale/ru.json';
import sk from '../locale/sk.json';
import sv from '../locale/sv.json';
import ta from '../locale/ta.json';
import tr from '../locale/tr.json';
import uk from '../locale/uk.json';
import zhCN from '../locale/zh-CN.json';
import zhTW from '../locale/zh-TW.json';

const locales_ = {
    ca: ca,
    cs: cs,
    de: de,
    en: en,
    'en-GB': enGB,
    es: es,
    et: et,
    fi: fi,
    fr: fr,
    'fr-CA': frCA,
    gl: gl,
    he: he,
    hu: hu,
    id: id,
    it: it,
    ja: ja,
    lv: lv,
    nb: nb,
    nl: nl,
    pl: pl,
    pt: pt,
    'pt-BR': ptBR,
    rm: rm,
    ru: ru,
    sk: sk,
    sv: sv,
    ta: ta,
    tr: tr,
    uk: uk,
    'zh-CN': zhCN,
    'zh-TW': zhTW,
};

export type MessageKey = keyof typeof en;

export type LocaleKey = keyof typeof locales_;

export type Locale = Record<MessageKey, string>;

const localeKeys = new Set(Object.keys(locales_)) as Set<LocaleKey>;

const locales: Record<LocaleKey, Partial<Locale>> & { en: Locale } = locales_;

export default locales;

export function isValidLocale(lang: string): lang is LocaleKey {
    return (localeKeys as Set<string>).has(lang);
}
