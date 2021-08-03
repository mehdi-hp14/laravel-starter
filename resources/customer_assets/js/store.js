import Vue from 'vue';
import Vuex from 'vuex';
import posts from './store/posts/index';


Vue.use(Vuex);

const store = new Vuex.Store({
    modules: {
        posts
    },
});

// Enable HMR for vuex
// if (module.hot) {
//     module.hot.accept([
//         './store/posts',
//     ], () => {
//         store.hotUpdate({
//             modules: {
//                 posts: require('./store/posts'),
//             },
//         })
//     })
// }


export default store;
