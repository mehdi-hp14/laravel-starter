<template>
    <v-form
        ref="form"
        v-model="valid"
        @submit="submit"
    >
        <v-row justify="center">
            <v-col
                cols="12"
                md="8"
                sm="10"
            >
                <v-card class="pa-5">
                    <v-text-field
                        label="عنوان"
                        v-model="form.title"
                        reverse
                        :rules="titleRules"
                    ></v-text-field>
                    <v-row>
                        <v-col md="12" sm="12">
                            <v-autocomplete
                                v-model="form.parent"
                                :items="catItems"
                                :rules="categoryRules"
                                :loading="isLoading"
                                :search-input.sync="search"
                                return-object
                                label='دسته بندی مادر'></v-autocomplete>
                        </v-col>
                    </v-row>
                    <v-btn color="primary" ripple block class="mt-5" :disabled="!valid" @click="submit"
                           :loading="formLoading">ذخیره
                    </v-btn>

                </v-card>
            </v-col>
        </v-row>
    </v-form>

</template>


<script>


    import MyEditor from "../Layouts/my-editor";

    export default {
        name: "CategoriesCreate",
        components: {MyEditor},
        data() {
            return {
                valid: false,
                isLoading: false,
                formLoading: false,
                form: {
                    title: '',
                    categories: [],
                    parent: {},
                },
                categories: '',
                search: null,
                catItems: [
                    {text: 'همه', value: 1}
                ],
                titleRules: [
                    v => !!v || 'عنوان الزامی است',
                ],
                categoryRules: [
                    v => {
                        return !!this.form.parent || 'انتخاب دسته بندی الزامی است'
                    },
                ]
            }
        },
        computed: {
            contentError() {
                return this.form.content.length < 150 ? 'حداقل 150 کاراکتر الزامی است' : false;
            }
        },
        methods: {
            submit() {
                this.formLoading = true;
                axios.post('/customer/contents/categories/store', this.form).then(r => {
                    this.$router.push({name: 'categories.index'});
                })
            }
        },
        watch: {
            search(val) {
                // Items have already been requested
                if (this.isLoading) return

                this.isLoading = true

                // Lazily load input items
                axios.post('/customer/contents/categories/search', {val})
                    .then(res => {
                        console.log(res.data)
                        this.count = res.data.length
                        this.catItems = res.data.data
                    })
                    .catch(err => {
                        console.log(err)
                    })
                    .finally(() => (this.isLoading = false))
            }
        }
    }
</script>

