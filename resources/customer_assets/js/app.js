window.axios = require('axios');
window.$ = window.jQuery = require('jquery');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
    },
});
import Vue from 'vue'
import vuetify from './plugins/vuetify' // path to vuetify export
import App from './components/App'
import router from './router'
import store from './store';
import CKEditor from '@ckeditor/ckeditor5-vue';

Vue.use(CKEditor);

new Vue({
    vuetify,
    router,
    store,
    el: '#app',
    render: h => h(App),
})
