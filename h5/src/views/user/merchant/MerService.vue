<template>
    <div
            class="address-management"
            :class="addressList.length < 1 && page > 1 ? 'on' : ''"
            ref="container"
    >
        <div class="line" v-if="addressList.length > 0">
            <img src="@assets/images/line.jpg" />
        </div>
        <div class="item" v-for="(item, index) in addressList" :key="index">
            <div class="address">
                <div class="consignee">
                    客服昵称：{{ item.nickname
                    }}<span class="phone">真实姓名：{{ item.real_name }}</span>
                </div>
            </div>
            <div class="operation acea-row row-between-wrapper">
                <div class="select-btn">
                    <div class="checkbox-wrapper">
                        <label class="well-check"><input
                                type="radio"
                                :name="'admin' +item.id"
                                value=""
                                :checked="item.is_admin ? true : false"
                                @click="radioAdminChange(index)"
                                /><i class="icon"></i><span class="default">管理员</span></label
                        >
                    </div>
                    <div class="checkbox-wrapper">
                        <label class="well-check"><input
                                type="radio"
                                :name="'check' +item.id"
                                value=""
                                :checked="item.is_check ? true : false"
                                @click="radioCheckChange(index)"
                        /><i class="icon"></i><span class="default">核销员</span></label
                        >
                    </div>
                </div>
                <div class="acea-row row-middle">
                    <div @click="delAddress(index)">
                        <span class="iconfont icon-shanchu"></span>删除
                    </div>
                </div>
            </div>
        </div>
        <Loading :loaded="loadend" :loading="loading"></Loading>
        <div class="noCommodity" v-if="addressList.length < 1 && page > 1">
            <div class="noPictrue">
                    <span>没有任何客服</span>
            </div>
        </div>
        <div style="height:1.2rem;"></div>
        <div class="footer acea-row row-between-wrapper">

            <div class="addressBnt on bg-color-red"  @click="addService">
                <span class="iconfont icon-tianjiadizhi"></span>添加客服
            </div>

        </div>
    </div>
</template>
<style scoped>
    .address-management.on {
        background-color: #fff;
        height: 100vh;
    }
</style>
<script type="text/babel">
    import {
        postAddress
    } from "@api/user";
    import {getServiceList,
        getServiceRemove,
        setServiceAdmin,
        setServiceCheck,
    } from "@api/merchant"
    import Loading from "@components/Loading";
    import { isWeixin } from "@utils";
    import { openAddress } from "@libs/wechat";

    export default {
        components: {
            Loading
        },
        data() {
            return {
                store_id:this.$route.params.id,
                page: 1,
                limit: 20,
                addressList: [],
                loadTitle: "",
                loading: false,
                loadend: false,
                isWechat: isWeixin()
            };
        },
        mounted: function() {
            this.AddressList();
            this.$scroll(this.$refs.container, () => {
                !this.loading && this.AddressList();
            });
        },
        methods: {
            /**
             * 获取地址列表
             *
             */
            AddressList: function() {
                let that = this;
                if (that.loading) return; //阻止下次请求（false可以进行请求）；
                if (that.loadend) return; //阻止结束当前请求（false可以进行请求）；
                that.loading = true;
                getServiceList({ page: that.page, limit: that.limit,store_id:that.store_id }).then(res => {
                    that.loading = false;
                    //apply();js将一个数组插入另一个数组;
                    that.addressList.push.apply(that.addressList, res.data);

                    console.info(that.addressList)

                    that.loadend = res.data.length < that.limit; //判断所有数据是否加载完成；
                    that.page = that.page + 1;
                });
            },
            /**
             * 编辑地址
             */
            editAddress: function(index) {
                this.$router.push({
                    path: "/user/add_address/" + this.addressList[index].id
                });
            },
            /**
             * 删除地址
             */
            delAddress: function(index) {
                let that = this;
                let address = this.addressList[index];
                let id = address.id;
                getServiceRemove(id).then(function() {
                    that.$dialog.toast({
                        mes: "删除成功!",
                        callback: () => {
                            that.addressList.splice(index, 1);
                            that.$set(that, "addressList", that.addressList);
                        }
                    });
                });
            },
            radioAdminChange: function(index) {
                let that = this,
                    address = this.addressList[index],
                    id = address.id;
                setServiceAdmin(id,!that.addressList[index].is_admin).then(function() {
                         that.addressList[index].is_admin = !that.addressList[index].is_admin;
                    that.$set(that, "addressList", that.addressList);
                });
            },
            radioCheckChange: function(index) {
                let that = this,
                    address = this.addressList[index],
                    id = address.id;
                setServiceCheck(id,!that.addressList[index].is_check).then(function() {
                    that.addressList[index].is_check = !that.addressList[index].is_check;

                    that.$set(that, "addressList", that.addressList);
                });
            },

            /**
             * 新增地址
             */
            addService: function() {
                this.$router.push({
                    path: "/merchant/serviceAdd/"+this.store_id
                });
            },
            getAddress() {
                openAddress().then(userInfo => {
                    this.$dialog.loading.open();
                    postAddress({
                        real_name: userInfo.userName,
                        phone: userInfo.telNumber,
                        address: {
                            province: userInfo.provinceName,
                            city: userInfo.cityName,
                            district: userInfo.countryName
                        },
                        detail: userInfo.detailInfo,
                        post_code: userInfo.postalCode,
                        wx_export: 1
                    })
                        .then(() => {
                            this.page = 1;
                            this.loading = false;
                            this.loadend = false;
                            this.addressList = [];
                            this.AddressList();

                            this.$dialog.loading.close();
                            this.$dialog.toast({ mes: "添加成功" });
                        })
                        .catch(err => {
                            this.$dialog.loading.close();
                            this.$dialog.error(err.msg || "添加失败");
                        });
                });
            }
        }
    };
</script>
