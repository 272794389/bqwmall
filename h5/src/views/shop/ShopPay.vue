<template>
  <div class="paybox">
    <div class="pay_box">
       <div class="shop_name">商家：{{storeInfo.name}}</div>
       <div class="shop_amount"><span>￥</span><input type="text" placeholder="" v-model="amount" /></div>
       <div class="pay_btn" @click="confirm" type="text">立即结算</div>
    </div>
</template>
<script>
import { mapGetters } from "vuex";

import { validatorDefaultCatch } from "@utils/dialog";
import { shopPay,getStoreDetail } from "@api/store";
import { VUE_APP_API_URL } from "@utils";

export default {
  name: "ShopPay",
  components: {},
  props: {},
  data: function() {
    return {
      amount: 0, //消费金额
      id: 0,
      storeInfo: {}
    };
  },
  computed: mapGetters(["isLogin"]),
  watch: {
    $route(n) {
      if (n.name === NAME) {
        this.id = n.params.id;
      }
    }
  },
  mounted: function() {
   this.id = this.$route.params.id;
   this.getStoreInfo();
  },
  methods: {
     //商家详情接口；
    getStoreInfo: function() {
      let that = this;
      getStoreDetail(that.id).then(res => {
          that.$set(that, "storeInfo", res.data.storeInfo);
        }).catch(res => {
          that.$dialog.error(res.msg);
          that.$router.go(-1);
        });
    },
    async confirm() {
      let that = this;
      const { amount } = that;
      shopPay({amount: this.amount,store_id: this.id })
        .then(res => {
          if (res.data !== undefined&&res.data.order_id>0) {
            this.$router.push({ path: "/shopset/" + res.data.order_id });
          } else {
            that.$dialog.error(res.msg);
          }
        })
        .catch(res => {
          that.$dialog.error(res.msg);
        });
    }
  }
};
</script>
<style scoped>
.paybox{background: #fff;width: 96%;margin-left: 2%; margin-top: 0.5rem;box-shadow: 2px 2px 5px #ccc;}
.pay_box{width: 90%; margin-left: 5%;}
.shop_name{line-height: 0.5rem;margin-top: 0.3rem;font-size: 0.35rem;}
.shop_amount{line-height: 1rem;border-bottom: 1px solid #999;font-size: 0.5rem;margin-top:0.2rem;margin-bottom:0.5rem;}
.pay_btn{width:100%;margin-left:0rem;}
.shop_amount span{width:10%;}
.shop_amount input{width:90%;}
.codeVal {
  width: 1.5rem;
  height: 0.5rem;
}
.codeVal img {
  width: 100%;
  height: 100%;
}
</style>
