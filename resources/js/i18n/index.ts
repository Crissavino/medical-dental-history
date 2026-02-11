import { createI18n } from 'vue-i18n';
import en from './en.json';
import ro from './ro.json';

const i18n = createI18n({
    legacy: false,
    locale: localStorage.getItem('locale') || 'en',
    fallbackLocale: 'en',
    messages: { en, ro },
});

export default i18n;
