<template>
    <div>
        <v-row justify="center">
            <v-col
                cols="12"
                md="8"
                sm="10"
            >
                <v-card class="pa-5">
                    <v-row justify="center">
                        <v-col md="6" sm="12">
                            <v-autocomplete
                                v-model="posts"
                                :items="postItems"
                                :rules="postRules"
                                :loading="isLoading"
                                :search-input.sync="searchPost"
                                label='انتخاب پست'></v-autocomplete>
                        </v-col>
                    </v-row>
                </v-card>
            </v-col>
        </v-row>
        <MediaUpload :serverImages="serverImages" :postID="posts"/>
    </div>
</template>

<script>
    import MediaUpload from "./MediaUpload";

    export default {
        name: "MediaCreate",
        data() {
            return {
                posts: null,
                postItems: [],
                postRules: [],
                isLoading: false,
                searchPost: null,
                serverImages: [],
            }
        },
        components: {MediaUpload},
        watch: {
            posts(val) {
                let thePost = this.postItems.find(item => item.value == val)
                this.serverImages = thePost.media
            },
            searchPost: {
                handler(val) {
                    if (this.isLoading) return
                    this.isLoading = true
                    // Lazily load input items
                    axios.post('/customer/contents/posts/search', {val})
                        .then(res => {
                            this.postItems = res.data.data
                            this.count = res.data.length
                            if (this.posts == null) {
                                this.posts = this.postItems[0].value;
                            }
                        })
                        .catch(err => {
                            console.log(err)
                        })
                        .finally(() => (this.isLoading = false))
                },
                immediate: true
            },
        },
    }
</script>
