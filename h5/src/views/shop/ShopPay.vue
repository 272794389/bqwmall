<template>
  <div class="paybox">
    <div class="pay_box">
       <div class="shop_name">商家：{{storeInfo.name}}</div>
       <div class="shop_amount"><span style="font-size:0.7rem; color:#000">￥</span><input type="text" placeholder="" v-model="amount" /></div>
       <div class="pay_btn" @click="confirm" type="text">立即付款</div>
    </div>
  </div>
</template>
<script>
import { mapGetters } from "vuex";
import { getUser } from "@api/user";
import { validatorDefaultCatch } from "@utils/dialog";
import { shopPay,getStoreDetail } from "@api/store";
import { isWeixin } from "@utils";
import { VUE_APP_API_URL } from "@utils";

export default {
  name: "ShopPay",
  components: {},
  props: {},
  data: function() {
  const { checkId=0 } = this.$route.query;
    return {
      amount: '', //消费金额
      id: 0,
      checkId:0,
      isWeixin: false,
      storeInfo: {}
    };
  },
  computed: mapGetters(["isLogin"]),
  watch: {
    $route(n) {
      if (n.name === NAME) {
        this.id = n.params.id;
        alert(this.id);
        const { checkId=0 } = this.$route.query;
        this.checkId = checkId;
        if (n.name === NAME) this.User();
      }
    }
  },
  mounted: function() {
   this.id = this.$route.params.id;
   const { checkId=0 } = this.$route.query;
   this.checkId = checkId;
   this.getStoreInfo();
   this.User();
   this.isWeixin = isWeixin();
  },
  methods: {
     User: function() {
      let that = this;
      getUser().then(res => {
        that.userInfo = res.data;
        that.orderStatusNum = res.data.orderStatusNum;
      });
    },
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
      shopPay({amount: this.amount,store_id: this.id,check_id: this.checkId })
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
.shop_amount{line-height: 1rem;border-bottom: 1px solid #efefef;font-size: 0.5rem;margin-top:0.2rem;margin-bottom:0.5rem;}
.pay_btn{width:100%;margin-left:0rem;}
.shop_amount span{width:10%;}
.shop_amount input{width:80%;}
.codeVal {
  width: 1.5rem;
  height: 0.5rem;
}
.codeVal img {
  width: 100%;
  height: 100%;
}
.checkbox-wrapper .icon {
    right: 0;
    left: unset;
}
.integral {
    margin-right: .4rem;
}
</style>
