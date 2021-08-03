<template>
    <v-app id="inspire">
        <v-navigation-drawer
            v-model="drawer"
            right
            app
        >
            <v-list dense>
                <v-list-group
                    v-for="(menu,i) in menus"
                    :prepend-icon="menu.icon"
                    :key="i"
                    v-model="menu.active"
                    no-action
                >
                    <template v-slot:activator>
                        <v-list-item-title>{{menu.title}}</v-list-item-title>
                    </template>
                    <v-list-item v-for="(child,j) in menu.children" :key="j" link :to="child.link">
                        <v-list-item-content>
                            <v-list-item-title>{{child.title}}</v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>
                </v-list-group>
            </v-list>
        </v-navigation-drawer>

        <v-app-bar
            app
            color="indigo"
            dark
        >
            <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
            <v-toolbar-title>{{$route.meta.title}}</v-toolbar-title>
        </v-app-bar>

        <v-content>
            <v-container
                class="fill-height"
                fluid
            >
                <v-row
                    align="center"
                    justify="center"
                >
                    <v-col class="text-center">
                        <router-view></router-view>
                    </v-col>
                </v-row>
            </v-container>
        </v-content>
        <v-footer
            color="indigo"
            app
        >
            <span class="white--text">&copy; 2020</span>
        </v-footer>
    </v-app>
</template>

<script>
    export default {
        name: "AppLayout",
        props: {
            source: String,
        },
        data: () => ({
            drawer: null,
            selectedMenu: 2,
            menus: [
                {
                    icon: 'mdi-newspaper',
                    title: 'پست ها',
                    active: false,
                    children: [
                        {
                            title: 'لیست',
                            link: {name: 'posts.index'},
                        },
                        {
                            title: 'پست جدید',
                            link: {name: 'posts.create'},
                        }
                    ],
                },
                {
                    icon: 'mdi-newspaper',
                    title: 'دسته بندی ها',
                    active: false,
                    children: [
                        {
                            title: 'لیست',
                            link: {name: 'categories.index'},
                        },
                        {
                            title: 'دسته بندی جدید',
                            link: {name: 'categories.create'},
                        }
                    ],
                },
                {
                    icon: 'mdi-newspaper',
                    title: 'گالری',
                    active: false,
                    children: [
                        {
                            title: 'لیست',
                            link: {name: 'media.create'},
                        },
                    ],
                }
            ]
        }),
        methods: {
            activateActiveListGroup() {
                for (const [key, value] of Object.entries(this.menus)) {
                    for (const [key2, value2] of Object.entries(value.children)) {
                        if (value2.link && this.$route.name === value2.link.name) {
                            this.menus[key].active = true
                        }
                    }
                }
            }
        },
        mounted() {
            this.$vuetify.rtl = true;
            this.activateActiveListGroup();
        }
    }
</script>
