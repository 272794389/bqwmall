<template>
    <div class="my-order" ref="container">
        <div class="header bg-color-red">
            <div class="picTxt acea-row row-between-wrapper">
                <div class="text">
                    <div class="name">门店信息</div>
                    <div>
                        门店数量：{{ storeList.length || 0 }}
                    </div>

                </div>
                <div class="pictrue"><img src="@assets/images/orderTime.png"/></div>
            </div>

        </div>

        <div class="nav acea-row row-around">
            <div
                    class="item"
            >
                <div class="bnt  default" @click="toAdd">新增门店</div>
            </div>
        </div>

        <div class="list">
            <div class="item" v-for="store in storeList" :key="store.id">
                <div class="title acea-row row-between-wrapper">
                    <div class="acea-row row-middle">
                        {{ store.name }}
                    </div>
                    <div class="font-color-red">{{ store.phone }}</div>
                </div>
                <div class="totalPrice">
                    {{ store.address }}    {{ store.detailed_address }}
                </div>
                <div class="bottom acea-row row-right row-middle">
                    <template>
                        <div class="bnt bg-color-red" @click="toEdit(store.id)">
                            修改
                        </div>

                        <div class="bnt bg-color-red">
                            详情
                        </div>
                    </template>
                </div>
            </div>
        </div>
        <div class="noCart" v-if="storeList.length === 0 && page > 1">
            没有门店，去新增吧
        </div>
        <Loading :loaded="loaded" :loading="loading"></Loading>
    </div>
</template>
<script>
    import {getStoreList,} from "@api/merchant";
    import {
        cancelOrderHandle,
        payOrderHandle,
        takeOrderHandle
    } from "@libs/order";
    import Loading from "@components/Loading";
    import Payment from "@components/Payment";
    import {mapGetters} from "vuex";
    import {isWeixin} from "@utils";

    const STATUS = [
        "待付款",
        "待发货",
        "待收货",
        "待评价",
        "已完成",
        "",
        "",
        "",
        "",
        "待付款"
    ];

    const NAME = "MyOrder";

    export default {
        name: NAME,
        data() {
            return {
                mer_id: this.$route.params.id,
                offlinePayStatus: 2,
                storeList: [],
                type: parseInt(this.$route.params.type) || 0,
                page: 1,
                limit: 20,
                loaded: false,
                loading: false,
                orderList: [],
                pay: false,
                payType: ["yue", "weixin"],
                from: isWeixin() ? "weixin" : "weixinh5"
            };
        },
        components: {
            Loading,
            Payment
        },
        computed: mapGetters(["userInfo"]),
        watch: {
            $route(n) {
                if (n.name === NAME) {
                    const type = parseInt(this.$route.params.type) || 0;
                    if (this.type !== type) {
                        this.changeType(type);
                    }
                    this.getstoreList();
                }
            }
        },
        methods: {
            toAdd() {
                this.$router.push({path: "/merchant/storeAdd/0"});
            },
            toEdit($id){
                this.$router.push({path: "/merchant/storeAdd/"+$id});
            },

            setOfflinePayStatus: function (status) {
                var that = this;
                that.offlinePayStatus = status;
                if (status === 1) {
                    if (that.payType.indexOf("offline") < 0) {
                        that.payType.push("offline");
                    }
                }
            },
            takeOrder(order) {
                takeOrderHandle(order.order_id).finally(() => {
                    this.reload();
                    this.getstoreList();
                });
            },
            reload() {
                this.changeType(this.type);
            },
            changeType(type) {
                this.type = type;
                this.orderList = [];
                this.page = 1;
                this.loaded = false;
                this.loading = false;
                this.getOrderList();
            },
            getOrderList() {
                if (this.loading || this.loaded) return;
                this.loading = true;
                const {page, limit, type, mer_id} = this;
                getStoreList({
                    page,
                    limit,
                    type,
                    mer_id: mer_id,
                }).then(res => {
                    console.info(res);

                    this.storeList = this.storeList.concat(res.data);
                    this.page++;
                    this.loaded = res.data.length < this.limit;
                    this.loading = false;
                });
            },
            getStatus(order) {
                return STATUS[order._status._type];
            },
            cancelOrder(order) {
                cancelOrderHandle(order.order_id)
                    .then(() => {
                        this.orderList.splice(this.orderList.indexOf(order), 1);
                    })
                    .catch(() => {
                        this.reload();
                    });
            },
            toDetail: function (order) {
                var that = this;
                return that.$router.push({
                    path: "/order/detail/" + order.order_id
                });
            },
            paymentTap: function (order) {
                var that = this;
                if (
                    !(
                        order.combination_id > 0 ||
                        order.bargain_id > 0 ||
                        order.seckill_id > 0
                    )
                ) {
                    that.setOfflinePayStatus(order.offlinePayStatus);
                }
                this.pay = true;
                this.toPay = type => {
                    payOrderHandle(order.order_id, type, that.from)
                        .then(() => {
                            const type = parseInt(this.$route.params.type) || 0;
                            that.changeType(type);
                            that.getstoreList();
                        })
                        .catch(res => {
                            if (res.status === "WECHAT_H5_PAY")
                                return that.$router.push({
                                    path: "/order/status/" + order.order_id + "/5"
                                });
                            const type = parseInt(that.$route.params.type) || 0;
                            that.changeType(type);
                            that.getstoreList();
                        });
                };
            },
            toPay() {
            }
        },
        mounted() {
            this.getOrderList();
            this.$scroll(this.$refs.container, () => {
                !this.loading && this.getOrderList();
            });
        }
    };
</script>

<style scoped>
    .noCart {
        margin-top: 0.17rem;
        padding-top: 0.1rem;
    }

    .pictrue {
        width: 1rem;
        height: 1rem;
        margin: 0.7rem auto 0.5rem auto;
    }

  .pictrue img {
        width: 100%;
        height: 100%;
    }
</style>
