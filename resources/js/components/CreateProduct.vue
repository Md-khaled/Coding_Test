<template>
    <section>
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="">Product Name</label>
                                <input type="text" v-model="product_name" placeholder="Product Name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="">Product SKU</label>
                                <input type="text" v-model="product_sku" placeholder="Product Name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="">Description</label>
                                <textarea v-model="description" id="" cols="30" rows="4" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Media</h6>
                        </div>
                        <div class="card-body border">
                            <div v-if="!imageEditMode">
                                <button @click="changeImg()" class="btn btn-primary">Change Image</button>
                            </div>
                            <div v-else>
                                <vue-dropzone ref="myVueDropzone" id="dropzone" :options="dropzoneOptions" v-on:vdropzone-success="uploadImage"></vue-dropzone>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Variants</h6>
                        </div>
                        <div class="card-body">
                            <div class="row" v-for="(item,index) in product_variant">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Option</label>
                                        <select v-model="item.option" class="form-control">
                                            <option v-for="variant in variants"
                                                    :value="variant.id">
                                                {{ variant.title }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label v-if="product_variant.length != 1" @click="product_variant.splice(index,1); checkVariant"
                                               class="float-right text-primary"
                                               style="cursor: pointer;">Remove</label>
                                        <label v-else for="">.</label>
                                        <input-tag v-model="item.tags" @input="checkVariant" class="form-control"></input-tag>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer" v-if="product_variant.length < variants.length && product_variant.length < 3">
                            <button @click="newVariant"  class="btn btn-primary">Add another option</button>
                        </div>

                        <div class="card-header text-uppercase">Preview</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <td>Variant</td>
                                        <td>Price</td>
                                        <td>Stock</td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="variant_price in product_variant_prices">
                                        <td>{{ variant_price.title }}</td>
                                        <td>
                                            <input type="text" class="form-control" v-model="variant_price.price">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" v-model="variant_price.stock">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button @click="saveProduct" type="submit" class="btn btn-lg btn-primary"><span v-if="Mode"></div>Update</span> <span v-else>Save</span></button>
            <button type="button" class="btn btn-secondary btn-lg">Cancel</button>
    </section>
</template>

<script>
import vue2Dropzone from 'vue2-dropzone'
import 'vue2-dropzone/dist/vue2Dropzone.min.css'
import InputTag from 'vue-input-tag'

export default {
    components: {
        vueDropzone: vue2Dropzone,
        InputTag
    },
    props: {
        Mode: {
            type: Boolean,
            default:false
        },
        variants: {
            type: Array,
            required: true
        },
        products: {
            type: Object,
        },
        image: {
            type: Object,
        },
        prices: {
            type: Object,
        }
    },
    data() {
        return {
            imageEditMode:false,
            product_name: '',
            product_sku: '',
            description: '',
            images: [],
            product_variant: [
                {
                    option: this.variants[0].id,
                    tags: []
                }
            ],
            product_variant_prices: [],
            dropzoneOptions: {
                url: 'https://httpbin.org/post',
                thumbnailWidth: 215,
                maxFilesize: 0.5,
                acceptedFiles: '.jpg, .jpeg, .png .gif',
                addRemoveLinks: true,
                dictDefaultMessage: "<i class='fas fa-cloud-upload-alt'></i>UPLOAD ME",
                headers: {
                    "My-Awesome-Header": "header value",
                    "X-CSRF-TOKEN": document.head.querySelector("[name=csrf-token]").content
                }
            }
        }
    },
    methods: {
        // it will push a new object into product variant
        uploadImage(file) {
            let image=file.dataURL;
            this.images.push(image);
            iziToast.success({
                title: 'Success',
                message: "Image upload success",
            });
            console.log(file)
        },
        changeImg(){
            this.imageEditMode=true;
            this.images=[];
        },
        newVariant() {
            let all_variants = this.variants.map(el => el.id)
            let selected_variants = this.product_variant.map(el => el.option);
            let available_variants = all_variants.filter(entry1 => !selected_variants.some(entry2 => entry1 == entry2))
            console.log(available_variants)

            this.product_variant.push({
                option: available_variants[0],
                tags: []
            })
        },

        // check the variant and render all the combination
        checkVariant() {
            let tags = [];
            this.product_variant_prices = [];
            this.product_variant.filter((item) => {
                tags.push(item.tags);
            })

            this.getCombn(tags).forEach(item => {
                this.product_variant_prices.push({
                    title: item,
                    price: 0,
                    stock: 0
                })
            })
        },

        // combination algorithm
        getCombn(arr, pre) {
            pre = pre || '';
            if (!arr.length) {
                return pre;
            }
            let self = this;
            let ans = arr[0].reduce(function (ans, value) {
                return ans.concat(self.getCombn(arr.slice(1), pre + value + '/'));
            }, []);
            return ans;
        },

        // store product into database
        saveProduct() {
            this.$Progress.start();
            let product = {
                editImage:this.imageEditMode,
                title: this.product_name,
                sku: this.product_sku,
                description: this.description,
                product_image: this.images,
                product_variant: this.product_variant,
                product_variant_prices: this.product_variant_prices
            }

            if(this.Mode){
                axios.put('/product/'+this.products.id, product).then(response => {
                   this.successMsg("Record updated successfully");
                   this.$Progress.finish();
                }).catch((error )=> {
                    let errors=error.response.data.errors;
                    this.errorMsg(errors);
                    this.$Progress.fail();
                })

            }else{
                axios.post('/product', product).then(response => {
                        this.successMsg("Record created successfully");
                        this.$Progress.finish();
                }).catch((error) => {
                    let errors=error.response.data.errors;
                    this.errorMsg(errors);
                    this.$Progress.fail();
                })
            }

            
        },
        successMsg(msg){
            iziToast.success({
                title: 'Success',
                position: 'topRight',
                message: msg,
            });
        },
        errorMsg(msg){
            $.each(msg,function(index,value) {
                iziToast.error({
                    title: 'Error',
                    position: 'topRight',
                    message: value,
                });
            })
        }


    },
    mounted() {
        if (this.Mode==false) {
            this.imageEditMode=true;
        }
        if(this.Mode){
            this.imageEditMode=false;
            this.product_name=this.products.title;
            this.product_sku=this.products.sku;
            this.description=this.products.description;
            this.product_variant=[];
            var available_variants=[];
            var ref=this;
            /*product_variant*/
            $.each(ref.products.product_variants,function(index,value) {
                let tag=[];
                available_variants[index];
                 $.each(value,function(key,variant) {
                    tag.push(variant.variant);
                 })
                ref.product_variant.push({
                    option: index,
                    tags: tag
                })
            })
            
            /*product_prices*/
            $.each(ref.prices.prices,function(index,value) {
                let items='';
                let variant_one=(value.variant_one == null)?'':value.variant_one.variant;
                let variant_two=value.variant_two == null?'':value.variant_two.variant;
                let variant_three=value.variant_three == null?'':value.variant_three.variant;
                items=variant_one+'/'+variant_two+'/'+variant_three;
                
                ref.product_variant_prices.push({
                        title: items,
                        price: value.price,
                        stock: value.stock
                    })
            
                
            })

            /*images*/
            $.each(ref.image.images,function(key,value) {
                ref.images.push(value.file_path);
            })
            var url = "http://127.0.0.1:8000/images/";
            //this.$refs.myVueDropzone.manuallyAddFile(file, url);
            //https://github.com/rowanwins/vue-dropzone/issues/455
            console.log(this.images);
        }
    }
}
</script>
