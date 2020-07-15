<template>
    <div class="addAddress absolute">
        <div class="list">
            <div class="item acea-row row-between-wrapper">
                <div class="name">商户名称</div>
                <input
                        type="text"
                        placeholder="商户名称"
                        v-model="merchant.name"
                        required
                />
            </div>
            <div class="item acea-row row-between-wrapper">
                <div class="name">商户联系人</div>
                <input
                        type="text"
                        placeholder="请输入姓名"
                        v-model="merchant.link_name"
                        required
                />
            </div>
            <div class="item acea-row row-between-wrapper">
                <div class="name">商户联系电话</div>
                <input
                        type="text"
                        placeholder="请输入联系电话"
                        v-model="merchant.phone"
                        required
                />
            </div>
        </div>



        <div class="keepBnt bg-color-red" @click="submit">立即申请</div>
    </div>
</template>

<script>
import {  postMerchantApply } from "@api/merchant";
import attrs, { required, chs_phone } from "@utils/validate";
import { validatorDefaultCatch } from "@utils/dialog";
export default {
    data() {
        return {
           merchant:{
           },

        };
    },
    mounted: function() {

    },
    methods: {
        async submit() {
            let name = this.merchant.name,
                phone = this.merchant.phone;
            try {
                await this.$validator({
                    name: [
                        required(required.message("商家名称")),
                        attrs.range([2, 16], attrs.range.message("商家名称"))
                    ],
                    phone: [
                        required(required.message("联系电话")),
                        chs_phone(chs_phone.message())
                    ]
                }).validate({ name, phone});
            } catch (e) {
                return validatorDefaultCatch(e);
            }

            try {
                let that = this,
                    data = {
                        merchant:this.merchant
                    };
                console.info(data)
                postMerchantApply(data).then(function() {
                    that.$dialog.toast({ mes: "申请成功" });
                    that.$router.go(-1);
                });
            } catch (e) {
                this.$dialog.error(e.msg);
            }
        }
    }
};
</script>

<style scoped>

</style>