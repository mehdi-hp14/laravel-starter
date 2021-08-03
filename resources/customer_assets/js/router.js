import Vue from 'vue';
import VueRouter from 'vue-router';
import Posts from './routes/posts';
import Categories from './routes/categories';
import Media from './routes/media';

Vue.use(VueRouter);

let routes = []
    .concat(Posts)
    .concat(Categories)
    .concat(Media)
const router = new VueRouter({
    base: '/',
    mode: 'history',
    routes: routes,
    // scrollBehavior() {
    //     setTimeout(() => window.scrollTo({top: 0, behavior: 'smooth'}), 50);
    // },
});

export default router;

