<template>
    <div>
        <div class="drag-img">
            <div v-for="(file, key) in files" class="file-listing">
                <img class="preview" :ref="'preview'+parseInt( key )" @click="initiateCrop($event, responsive32.x, responsive32.y,key,getOldId(file),file)">
                <div class="remove-container" @click="$store.commit('removeFile',{ key , file })" v-if="showDeleteButton(file)">
                </div>
            </div>
            <div class="file-listing select-img">
                <input type="file" name="select_img" id="select_img" class="hidden" @change="userSelectImage" accept="image/*">
                <label for="select_img" ref="fileLabel" @dragover="formDraggedIn" @dragleave="formDraggedLeave" @drop="formDraggedLeave">افزودن تصویر</label>
            </div>
        </div>
        <div id="suggestions" v-if="showModal">
            <form id="croppie-container" style="width: 100%;height: 100%" @submit.prevent="submitCrop">
                <div class="size">
                    <a href="javascript:void(0)" class="modal-crop-btn crop_1_1 bottom-115" @click="cropTo11">1 : 1</a>
                    <a href="javascript:void(0)" class="modal-crop-btn crop_3_2 bottom-160" @click="cropTo32">3 : 2</a>
                    <a href="javascript:void(0)" class="modal-crop-btn crop_2_3 bottom-205" @click="cropTo23">2 : 3</a>
                </div>
                <div class="info">
                    <progress max="100" :value.prop="singleUploadPercentages"></progress>


                    <button class="modal-crop-btn" id="submit-crop" @click="checkUploadFormIsComplete">تایید برش</button>
                    <a href="javascript:void(0)" class="modal-crop-btn bottom-70" id="cancel-crop" @click="cancelCrop">انصراف</a>
                </div>
                <div :class="['group__input',{group__input__category:form.image_categories.length>1}]">
                    <div>
                        <input type="text" id="image_description" class="image_description" v-model="imageDescription" placeholder="توضیحات تصویر">
                    </div>
                    <div v-show="form.image_categories.length>1">
                        <select class="image_category" id="desc_id" v-model="imageCategory" required>
                            <option :value="''" disabled>دسته بندی...</option>
                            <option :value="option.value" v-for="option in form.image_categories" :selected="selectedCategory">{{option.title}}</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="search-overlay" v-if="showModal || showFilesUpload" @click.self="cancelCrop"></div>
    </div>
</template>

<script>
    import {mapState, mapGetters} from "vuex";
    // import {Croppie} from "croppie";
    import Exif from "exif-js";

    var Croppie = require("croppie");
    export default {
        name: "UploadImage",

        data() {
            return {
                dragAndDropCapable: false,
                imageDescription: '',
                imageCategory: '',
                user_selected_image_url: null,
                currentImg: {},
                croppieInstance: {},
                finalCroppieWidth: 850,
                finalCroppieHeight: 850,
                currentImageKey: 0,
                currentCropedImage: {},
                fileKeyInVuex: {},
                currentImageOldId: null,
                cancelImgNomineeKey: null,
                recentUploadFileId: 0,
                singleUploadPercentages: 0,
                isComponentMounted: false,
            }
        },
        computed: {
            ...mapState([
                'currentStep',
                'files',
                'showFilesUpload',
                'showModal',
                'form',
                'reviewId',
                'section',
                'isUploadImageMounted',
            ]),

            selectedCategory() {
                return (this.form.image_categories.length === 1)
            },
            responsive11() {
                if (window.innerWidth < 600) {
                    let x = window.innerWidth * (85 / 100);
                    return {x: x, y: x};
                }
                return {x: 600, y: 600}
            },
            responsive32() {
                if (window.innerWidth < 600) {
                    let x = window.innerWidth * (85 / 100);
                    return {x: x, y: x * (2 / 3)};
                }
                return {x: 600, y: 400}
            },
            responsive23() {
                if (window.innerWidth < 600) {
                    let x = window.innerWidth * (85 / 100);
                    return {x: x, y: x * (3 / 2)};
                }
                return {x: 400, y: 600}
            },

        },
        watch: {
            files: {
                // files(oldVal, newVal) {
                handler(oldVal, newVal) {
                    this.getImagePreviews();
                }
                ,

                immediate: true,
                //
                // this.getImagePreviews();

            },
            recentUploadFileId: {
                handler(oldVal, newVal) {
                    let key = this.files.length - 1;
                    this.files[key].oldId = newVal;
                },
                immediate: false,
            },
        },
        methods: {
            showDeleteButton(file) {
                return !$(file).data('isApproved');
            },
            checkUploadFormIsComplete($event) {
                if (!this.imageCategory) {
                    toastr.error('فیلد دسته بندی ضروری می باشند', {progressBar: true, preventDuplicates: true});
                }

            },
            cancelCrop() {
                this.$store.commit('showModal', 0);
                if (this.cancelImgNomineeKey !== null) {
                    this.$store.commit('removeEarlyFile', this.cancelImgNomineeKey);
                }
            },
            getOldId(file) {
                return file.oldId > 0 ? file.oldId : $(file).data('old-id');
            },
            cropTo11() {
                this.finalCroppieWidth = 850;
                this.finalCroppieHeight = 850;
                let x = this.responsive11.x;
                let y = this.responsive11.y;
                this.initiateCrop(this.currentImg, x, y, null, this.currentImageOldId)
            },
            cropTo32() {
                this.finalCroppieWidth = 850;
                this.finalCroppieHeight = 567;
                let x = this.responsive32.x;
                let y = this.responsive32.y;

                this.initiateCrop(this.currentImg, x, y, null, this.currentImageOldId)

            },
            cropTo23() {
                this.finalCroppieWidth = 567;
                this.finalCroppieHeight = 850;
                let x = this.responsive23.x;
                let y = this.responsive23.y;
                this.initiateCrop(this.currentImg, x, y, null, this.currentImageOldId)
            },
            formDraggedIn(e) {
                $(e.target).parent().css({'border': '3px dashed #666666'})
            },
            formDraggedLeave(e) {
                $(e.target).parent().css({'border': 'none'})
            },
            checkValidImg(item, callback) {
                const fileType = item['type'];
                var ValidImage = true;
                const validImageTypes = ['image/gif', 'image/jpeg', 'image/png', 'image/jpg'];
                toastr.clear();
                toastr.options.timeOut = 0;
                toastr.options.extendedTimeOut = 0;
                var image = new Image();
                image.onload = function () {
                    return proceed();
                };
                image.onerror = function () { //this can somehow check mimetype
                    ValidImage = false;
                    return proceed();

                };
                var url = window.URL || window.webkitURL;
                image.src = url.createObjectURL(item);

                function proceed() {
                    if (!validImageTypes.includes(fileType) || !ValidImage) {
                        toastr.error(window.err_img_format);
                        return false;
                    }
                    if (item.size > (20 * 1024 * 1024)) {
                        toastr.error(window.err_img_size);
                        return false;
                    }
                    callback(); //return true;
                };

            },
            userSelectImage(e) {
                if (e.target.files[0]) {
                    for (let item of e.target.files) {
                        this.checkValidImg(item, () => {
                            if (this.files.length >= this.form.maximum_images_count) {
                                toastr.error(`حداکثر می توانید ${this.form.maximum_images_count} عکس آپلود کنید`);
                                return;
                            }
                            this.$store.commit('addFiles', item);
                            // this.$store.state.form.media.push(item);
                            this.$nextTick(() => {
                                var key = this.files.length - 1;
                                this.cancelImgNomineeKey = key;
                                var lastElement = this.$refs['preview' + parseInt(key)];
                                $(lastElement).click();
                            })
                        })
                    }
                }
                document.getElementById("select_img").value = null;
            },
            mountDragAndDrop() {
                /*
                  Determine if drag and drop functionality is capable in the browser
                */
                this.dragAndDropCapable = this.determineDragAndDropCapable();

                /*
                  If drag and drop capable, then we continue to bind events to our elements.
                */
                if (this.dragAndDropCapable) {
                    /*
                      Listen to all of the drag events and bind an event listener to each
                      for the fileLabel.
                    */
                    ['drag', 'dragstart', 'dragend', 'dragover', 'dragenter', 'dragleave', 'drop'].forEach(evt => {
                            /*
                              For each event add an event listener that prevents the default action
                              (opening the file in the browser) and stop the propagation of the event (so
                              no other elements open the file in the browser)
                            */

                            this.$refs.fileLabel.addEventListener(evt, e => {
                                e.preventDefault();
                                e.stopPropagation();
                            }, false);
                        }
                    );

                    /*
                      Add an event listener for drop to the form
                    */

                    this.$refs.fileLabel.addEventListener('drop', e => {
                        /*
                          Capture the files from the drop event and add them to our local files
                          array.
                        */
                        if (this.files.length >= this.form.maximum_images_count) {
                            toastr.error(`حداکثر می توانید ${this.form.maximum_images_count} عکس آپلود کنید`);
                            return;
                        }
                        for (let i = 0; i < e.dataTransfer.files.length; i++) {
                            if (i === 1) {
                                toastr.error("فایل ها می بایست تک به تک آپلود شوند.");

                                toastr.options = {
                                    "closeButton": false,
                                    "debug": false,
                                    "newestOnTop": false,
                                    "progressBar": true,
                                    "positionClass": "toast-bottom-right",
                                    "preventDuplicates": false,
                                    "onclick": null,
                                    "showDuration": "300",
                                    "hideDuration": "1000",
                                    "timeOut": "5000",
                                    "extendedTimeOut": "1000",
                                    "showEasing": "swing",
                                    "hideEasing": "linear",
                                    "showMethod": "fadeIn",
                                    "hideMethod": "fadeOut"
                                };
                                break;
                            }
                            var item = e.dataTransfer.files[0];

                            this.checkValidImg(item, () => {
                                this.$store.commit('addFiles', item); //only get the first image
                                // this.$store.state.form.media.push(item);

                                this.$nextTick(() => {
                                    var key = this.files.length - 1;
                                    this.cancelImgNomineeKey = key;
                                    var lastElement = this.$refs['preview' + parseInt(key)];
                                    $(lastElement).click();
                                })
                            })

                        }
                        document.getElementById("select_img").value = null;

                    });
                }
            },
            /*
             Determines if the drag and drop functionality is in the
             window
            */
            determineDragAndDropCapable() {
                /*
                  Create a test element to see if certain events
                  are present that let us do drag and drop.
                */
                var div = document.createElement('div');

                /*
                  Check to see if the `draggable` event is in the element
                  or the `ondragstart` and `ondrop` events are in the element. If
                  they are, then we have what we need for dragging and dropping files.

                  We also check to see if the window has `FormData` and `FileReader` objects
                  present so we can do our AJAX uploading
                */
                return (('draggable' in div)
                    || ('ondragstart' in div && 'ondrop' in div))
                    && 'FormData' in window
                    && 'FileReader' in window;
            },
            /*
                Gets the image preview for the file.
            */

            getImagePreviews() {
                /*
                  Iterate over all of the files and generate an image preview for each one.
                */
                for (let i = 0; i < this.files.length; i++) {
                    let img = this.files[i];

                    /*
                      Ensure the file is an image file
                    */
                    if ($(img).hasClass('draft-img')) {
                        this.$nextTick(function () {
                            let previewImg = this.$refs['preview' + parseInt(i)][0];
                            previewImg.src = img.src;
                            $(previewImg).attr('data-old-id', $(img).data('old-id'));
                            $(previewImg).attr('data-is-approved', $(img).data('is-approved'));
                            $(previewImg).attr('data-description', $(img).data('description'));
                            $(previewImg).attr('data-category', $(img).data('category'));

                        });
                        // this.$store.commit('addImagesRepetitive', i);
                    } else if (/\.(jpe?g|png|gif)$/i.test(img.name)) {
                        /*
                          Create a new FileReader object
                        */
                        let reader = new FileReader();

                        /*
                          Add an event listener for when the file has been loaded
                          to update the src on the file preview.
                        */
                        reader.addEventListener("load", () => {
                            let previewImg = this.$refs['preview' + parseInt(i)][0];
                            previewImg.src = reader.result;
                        }, false);

                        /*
                          Read the data for the file in through the reader. When it has
                          been loaded, we listen to the event propagated and set the image
                          src to what was loaded from the reader.
                        */

                        reader.readAsDataURL(img);
                    } else {
                        /*
                          We do the next tick so the reference is bound and we can access it.
                        */
                        this.$nextTick(function () {
                            this.$refs['preview' + parseInt(i)][0].src = '/images/file.png';
                        });
                    }
                }
            },

            initiateCrop(e, width = 600, height = 400, fileKeyInVuex = null, currentImageOldId = null, file = null) {
                if (file && $(file).data('isApproved')) {
                    toastr.error('شما نمی توانید تصاویر تایید شده را ویرایش کنید', '', {timeOut: 5000});
                    return;
                }
                let image, finalWidthHeight;
                if (fileKeyInVuex === null) { //it comes when customer hit the change ratio button
                    fileKeyInVuex = this.currentImageKey;
                } else { //comes when customer click on thumbnail
                    this.currentImageKey = fileKeyInVuex;
                    if (this.form.image_categories.length === 1) { //when there is just one category select it anyway
                        this.imageCategory = this.form.image_categories[0].value;
                    }
                }
                this.fileKeyInVuex = this.currentImageKey;
                if (currentImageOldId) {
                    this.currentImageOldId = currentImageOldId;
                } else {
                    this.currentImageOldId = null;
                }
                if (e.target) {
                    image = this.currentImg = e.target; //comes when customer click on thumbnail
                    this.imageDescription = $(image).data('description') || '';
                    // if (!this.imageCategory) {
                    if (this.form.image_categories.length > 1) {
                        this.imageCategory = $(image).data('category') || '';
                    }
                } else {
                    image = this.currentImg = e;
                    var ChangeDimensionClicked = 1; //comes when customer click on change dimension
                    finalWidthHeight = [width, height];
                }

                this.$store.commit('showModal', 1);

                if (image.naturalWidth !== 0) { //means when customer not just clicked on browse image
                    if (!ChangeDimensionClicked) { //we want only when clicked on thumbnails.
                        finalWidthHeight = image.naturalWidth > image.naturalHeight ? [width, height] /*3:2*/ : [height, width] /*2:3*/;
                        finalWidthHeight = image.naturalWidth === image.naturalHeight ? [width, width] /*1:1*/ : finalWidthHeight;
                        if (window.innerWidth < 600) {
                            finalWidthHeight = image.naturalWidth > image.naturalHeight ? [this.responsive32.x, this.responsive32.y] /*3:2*/ : [this.responsive23.x, this.responsive23.y] /*2:3*/;
                            finalWidthHeight = image.naturalWidth === image.naturalHeight ? [this.responsive11.x, this.responsive11.y] /*1:1*/ : finalWidthHeight;
                        }
                    }
                } else {
                    finalWidthHeight = [width, width]; //means when customer just clicked on browse image we want an square
                }
                this.$nextTick(() => {
                    this.renderCroppie(finalWidthHeight, image)
                });
            },
            renderCroppie(finalWidthHeight, image) {
                setTimeout(() => {
                    var src = $(image).attr('src');
                    if (src == undefined) {
                        this.renderCroppie(finalWidthHeight, image);
                        return;
                    }
                    let el = document.getElementById('croppie-container');
                    if (this.croppieInstance instanceof Croppie) {
                        this.croppieInstance.destroy();
                    }
                    var showZoomer = true;
                    if (window.innerWidth < 600) {
                        showZoomer = false;
                    }
                    this.croppieInstance = new Croppie(el, {
                        viewport: {width: finalWidthHeight[0], height: finalWidthHeight[1]},
                        boundary: {width: finalWidthHeight[0], height: finalWidthHeight[1]},
                        enableResize: false,
                        enableZoom: true,
                        enableExif: true,
                        showZoomer: showZoomer,
                        maxZoom: 5,
                    });
                    this.croppieInstance.bind({
                        url: src
                    });
                    this.currentCropedImage = image;
                    return true;
                }, 50);
            },
            resizeImg(size, callback) {
                this.croppieInstance.result({type: 'blob', size: {width: this.finalCroppieWidth, height: this.finalCroppieHeight}, format: 'jpeg', quality: size}).then(blob => {
                    if ((blob.size / 1024) < 300) {
                        var objectURL = URL.createObjectURL(blob);
                        this.currentCropedImage.src = objectURL;
                        callback(blob);
                    } else {
                        this.resizeImg(size - 0.01, callback)
                    }
                });
            },
            submitCrop() {
                this.resizeImg(1, (blob) => {
                    this.$store.commit('modifyFile', {key: this.fileKeyInVuex, url: this.currentCropedImage.src});
                    this.cancelImgNomineeKey = null;
                    $(this.currentImg).data('category', this.imageCategory);
                    $(this.currentImg).data('description', this.imageDescription);
                    this.singleUploadImage({
                        file: blob,
                        oldId: this.currentImageOldId,
                        descriptions: this.imageDescription,
                        category: this.imageCategory
                    });
                });
            },
            singleUploadImage({file, category, descriptions, oldId}) {
                let formData = new FormData();

                formData.append('id', this.reviewId);
                formData.append('type', this.section);
                formData.append('image', file);
                formData.append('category', category);
                formData.append('description', descriptions);
                formData.append('old_id', oldId);
                toastr.info('لطفا صبر کنید ...');
                $('#submit-crop').attr('disabled', 'disabled');
                $.ajax({
                    url: '/reviews/media/upload',
                    data: formData,
                    enctype: 'multipart/form-data',
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    xhr: () => {
                        // get the native XmlHttpRequest object
                        var xhr = $.ajaxSettings.xhr();
                        // set the onprogress event handler
                        xhr.upload.onprogress = evt => {
                            this.singleUploadPercentages = Math.round(evt.loaded / evt.total * 100);
                        };
                        // set the onload event handler
                        xhr.upload.onload = function () {
                            console.log('DONE!')
                        };
                        // return the customized object
                        return xhr;
                    },
                    success: (response) => {
                        this.recentUploadFileId = response.media.id;
                        this.$store.commit('showModal', false);
                        this.singleUploadPercentages = 0;
                        this.$store.state.form.media.push(response.media);

                    },
                    error: (error, xhr) => {
                        this.$store.commit('showModal', false)
                    }
                }).always(() => {
                    toastr.clear();
                    $('#submit-crop').removeAttr('disabled');

                });
            },
        },
        mounted() {
            if (this.isUploadImageMounted) {
                this.$store.state.files = [];
            }
            this.$nextTick(() => {
                for (let item of this.form.media) {

                    var img = new Image();
                    img.src = item.url;

                    let x = img.src.split('/');

                    img.name = x[x.length - 1];

                    $(img).addClass('draft-img');
                    $(img).data('old-id', item.id);
                    $(img).data('category', item.category_id);
                    $(img).data('description', item.description);
                    $(img).data('is-approved', item.is_approved);

                    this.$store.commit('addFiles', img);
                }
                this.mountDragAndDrop();
                this.$store.state.isUploadImageMounted = true;
            });
        }
    }
</script>

<style scoped>

</style>
