<template>
  <div class="user">
    <div class="myOrder1">
      <div class="title">
		 <router-link :to="'/user/account'" class="title1">
		  <div class="title1-2">我的余额</div>
		  <div class="title1-3" style="color: #16ac57;">￥{{ userInfo.now_money || 0 }}<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
       <div class="title" v-if="userInfo.store_name!=''">
		 <router-link :to="'/user/huokuan'" class="title1">
		  <div class="title1-2">我的货款</div>
		  <div class="title1-3" style="color: #16ac57;">￥{{ userInfo.huokuan || 0 }}<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	  <div class="title">
		 <router-link :to="'/user/give'" class="title1">
		  <div class="title1-2">我的购物积分</div>
		  <div class="title1-3" style="color: #16ac57;">￥{{ userInfo.give_point || 0 }}<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	  <div class="title">
		 <router-link :to="'/user/paypoint'" class="title1">
		  <div class="title1-2">我的消费积分</div>
		  <div class="title1-3" style="color: #16ac57;">￥{{ userInfo.pay_point || 0 }}<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
      <div class="title">
		 <router-link :to="'/user/repaypoint'" class="title1">
		  <div class="title1-2">我的重消积分</div>
		  <div class="title1-3" style="color: #16ac57;">￥{{ userInfo.repeat_point || 0 }}<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	  <div class="title">
		 <router-link :to="'#'" class="title1">
		  <div class="title1-2">我的股权</div>
		  <div class="title1-3" style="color: #16ac57;">{{ userInfo.stockAmount || 0 }}</div>
		 </router-link>
	  </div>
	  <div class="title">
		 <router-link :to="'/user/user_coupon'" class="title1">
		  <div class="title1-2">我的抵扣券</div>
		  <div class="title1-3">查看抵扣券<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	<div class="foot">
	  <div class="foot3">© 2017-2020 佰仟万电商平台 版权所有，并保留所有权利。</div>
	</div>		  
  </div>
	  
</template>

<script>
import { getUser} from "@api/user";
import { isWeixin } from "@utils";
import SwitchWindow from "@components/SwitchWindow";
import GeneralWindow from "@components/GeneralWindow";
const NAME = "UserFinance";

export default {
  name: NAME,
  components: {
    SwitchWindow,
    GeneralWindow
  },
  props: {},
  data: function() {
    return {
      userInfo: {},
      orderStatusNum: {},
      switchActive: false,
      isWeixin: false,
      generalActive: false,
      generalContent: {
        promoterNum: "",
        title: ""
      }
    };
  },
  watch: {
    $route(n) {
      if (n.name === NAME) this.User();
    }
  },
  mounted: function() {
    this.User();
    this.isWeixin = isWeixin();
  },
  methods: {
    changeswitch: function(data) {
      this.switchActive = data;
    },
    User: function() {
      let that = this;
      getUser().then(res => {
        that.userInfo = res.data;
        that.orderStatusNum = res.data.orderStatusNum;
      });
    },
    closeGeneralWindow(msg) {
      this.generalActive = msg;
    }
  }
};
</script>

<style scoped>
.footer-line-height {
  height: 1rem;
}
.user .myOrder1 .title1-2{
  width:50%;
}
.order-status-num {
  min-width: 0.33rem;
  background-color: #fff;
  color: #ee5a52;
  border-radius: 15px;
  position: absolute;
  right: -0.14rem;
  top: -0.15rem;
  font-size: 0.2rem;
  padding: 0 0.08rem;
  border: 1px solid #ee5a52;
}

.pictrue {
  position: relative;
}
.switch-h5 {
  margin-left: 0.2rem;
}
.binding {
  padding: 0.05rem 0.2rem;
  background-color: #ca1f10;
  border-radius: 50px;
  font-size: 0.14rem;
  border: 1px solid #e8695e;
  color: #ffffff;
}
</style>
