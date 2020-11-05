<template>
  <div class="paybox">
    <div class="pay_box">
      <img :src="storeInfo.license"/>
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
    }
  }
};
</script>
<style scoped>
.paybox{background: #fff;width: 96%;margin-left: 2%; margin-top: 0.5rem;box-shadow: 2px 2px 5px #ccc;}
.pay_box{width: 100%;}
.pay_box img{width: 100%;}
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
