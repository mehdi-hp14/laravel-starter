<template>
    <v-dialog
        v-model="dialog"
        max-width="290"
    >
        <template v-slot:activator="{ on, attrs }">
            <v-btn
                color="error"
                dark
                v-bind="attrs"
                v-on="on"
            >
                <v-icon
                >
                    mdi-delete
                </v-icon>
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline"></v-card-title>

            <v-card-text>آیا از پاک کردن این آیتم اطمینان داری ؟</v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                    color="green darken-1"
                    text
                    @click="clicked(true)"
                >
                    بله
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn
                    color="red darken-1"
                    text
                    @click="clicked(false)"
                >
                    خیر
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
    export default {
        name: "ConfirmDelete",
        data() {
            return {
                dialog: false
            }
        },
        props: {
            value: Boolean
        },
        methods: {
            clicked(isDeleted) {
                this.dialog = false;
                this.$emit('input', false);
                if (isDeleted) {
                    this.$emit('deleteConfirmed', isDeleted)
                }
            }
        },
        watch: {
            value(val) {
                this.dialog = val
            }
        }
    }
</script>
