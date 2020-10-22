<template>
  <div class="user">
    <div class="followCode" v-if="followCode">
      <div class="pictrue"><img :src="followUrl" /></div>
      <div class="mask" @click="closeFollowCode"></div>
    </div>
    <div class="header bg-color-red acea-row row-between-wrapper">
      <div class="picTxt acea-row row-between-wrapper">
        <div class="pictrue"><img :src="userInfo.avatar" /></div>
        <div class="text">
          <div class="acea-row row-middle">
            <div class="name line1">{{ userInfo.nickname }}</div>
            <div class="member acea-row row-middle" v-if="userInfo.vip">
              <img :src="userInfo.vip_icon" />{{ userInfo.vip_name }}
            </div>
          </div>
          <router-link :to="'/user/data'" class="id">
            ID：{{ userInfo.uid || 0
            }}<span class="iconfont icon-bianji1"></span>
          </router-link>
          <router-link :to="'/user/binding'" class="binding" v-if="userInfo.phone==''">
            <span>绑定手机号</span>
          </router-link>
        </div>
      </div>
      <span
        class="iconfont icon-shezhi"
        @click="$router.push({ path: '/user/data' })"
      ></span>
    </div>
    <div class="wrapper1">
      <div class="nav acea-row row-middle">
        <router-link :to="{ path: '/user/account' }" class="item">
          <div class="num">{{ userInfo.now_money || 0 }}</div>
          <div>余额</div>
        </router-link>
        <router-link :to="{ path: '/user/huokuan' }" class="item" v-if="userInfo.store_name!=''">
          <div class="num">{{ userInfo.huokuan || 0 }}</div>
          <div>货款</div>
        </router-link>
        <router-link :to="'/user/give'" class="item">
          <div class="num">{{ userInfo.give_point || 0 }}</div>
          <div>购物积分</div>
        </router-link>
        <router-link :to="'/user/paypoint'" class="item">
          <div class="num">{{ userInfo.pay_point || 0 }}</div>
          <div>消费积分</div>
        </router-link>
        <router-link :to="'/user/repaypoint'" class="item">
          <div class="num">{{ userInfo.repeat_point || 0 }}</div>
          <div>重消积分</div>
        </router-link>
      </div>
      <div class="myOrder">
        <div class="title acea-row row-between-wrapper">
          <div>全部订单</div>
          <router-link :to="'/order/list/'" class="allOrder">
            查看全部订单<span class="iconfont icon-jiantou"></span>
          </router-link>
        </div>
        <div class="orderState acea-row row-middle">
          <router-link :to="{ path: '/order/list/' + 0 }" class="item">
            <div class="pictrue">
              <img src="@assets/images/dfk.png" />
              <span
                class="order-status-num"
                v-if="orderStatusNum.unpaid_count > 0"
                >{{ orderStatusNum.unpaid_count }}</span
              >
            </div>
            <div>待付款</div>
          </router-link>
          <router-link :to="{ path: '/order/list/' + 1 }" class="item">
            <div class="pictrue">
              <img src="@assets/images/dfh.png" />
              <span
                class="order-status-num"
                v-if="orderStatusNum.unshipped_count > 0"
                >{{ orderStatusNum.unshipped_count }}</span
              >
            </div>
            <div>待发货</div>
          </router-link>
          <router-link :to="{ path: '/order/list/' + 2 }" class="item">
            <div class="pictrue">
              <img src="@assets/images/dsh.png" />
              <span
                class="order-status-num"
                v-if="orderStatusNum.received_count > 0"
                >{{ orderStatusNum.received_count }}</span
              >
            </div>
            <div>待收货</div>
          </router-link>
          <router-link :to="{ path: '/order/list/' + 3 }" class="item">
            <div class="pictrue">
              <img src="@assets/images/dpj.png" />
              <span
                class="order-status-num"
                v-if="orderStatusNum.evaluated_count > 0"
                >{{ orderStatusNum.evaluated_count }}</span
              >
            </div>
            <div>已完成</div>
          </router-link>
        </div>
      </div>
    </div>
    <div class="myOrder1">
     <div class="title" v-if="subscribe!=1">
		 <div class="title1" @click="followTap">
		  <div class="title1-1"><img src="@assets/images/focus.png" /></div>
		  <div class="title1-2">关注我们</div>
		  <div class="title1-3">关注接收余额通知<span class="iconfont icon-jiantou"></span></div>
		 </div>
	  </div>
      <div class="title">
		 <router-link :to="'/user/user_finance'" class="title1">
		  <div class="title1-1"><img src="@assets/images/balance.png" /></div>
		  <div class="title1-2">财富中心</div>
		  <div class="title1-3">查看我的财富<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
      <div class="title">
		 <router-link :to="'/user/user_promotion'" class="title1">
		  <div class="title1-1"><img src="@assets/images/extension.png" /></div>
		  <div class="title1-2">我的推广</div>
		  <div class="title1-3">查看我的推广<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	  <div class="title">
		 <router-link :to="'/user/payorder'" class="title1">
		  <div class="title1-1"><img src="@assets/images/record3.png" /></div>
		  <div class="title1-2">消费统计</div>
		  <div class="title1-3">查看消费订单<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	  <div class="title">
		 <router-link :to="'/collection'" class="title1">
		  <div class="title1-1"><img src="@assets/images/collection.png" /></div>
		  <div class="title1-2">我的收藏</div>
		  <div class="title1-3">查看我收藏的商品<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	  <div class="title">
		 <router-link :to="'/user/add_manage'" class="title1">
		  <div class="title1-1"><img src="@assets/images/address.png" /></div>
		  <div class="title1-2">收货地址</div>
		  <div class="title1-3">更改我的收货地址<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
      <div class="title">
		 <router-link :to="'/user/user_coupon'" class="title1">
		  <div class="title1-1"><img src="@assets/images/coupon.png" /></div>
		  <div class="title1-2">我的抵扣券</div>
		  <div class="title1-3">
		    <span style="color:#f00;" v-if="userInfo.coupon_price>0">金额：￥{{ userInfo.coupon_price }}</span>
		    <span v-else>查看我的抵扣券</span>
		    <span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	  <div class="title"  v-if="userInfo.is_check==1">
		 <router-link :to="'/order/order_cancellation'" class="title1">
		  <div class="title1-1"><img src="@assets/images/hex.png" /></div>
		  <div class="title1-2">订单核销</div>
		  <div class="title1-3">扫码核销订单<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	  
	  <div class="title"  v-if="userInfo.is_check==1">
		 <router-link :to="'/customer/orders/1'" class="title1">
		  <div class="title1-1"><img src="@assets/images/dsh.png" /></div>
		  <div class="title1-2">商家订单</div> 
		  <div class="title1-3">订单发货<span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	  
	  <div class="title">
		 <router-link :to="'/merchant/home'" class="title1">
		  <div class="title1-1"><img src="@assets/images/shop.png" /></div>
		  <div class="title1-2">我是商家</div>
		  <div class="title1-3"><span v-if="userInfo.is_store==1">管理我的店铺</span><span v-else>我要入驻</span><span class="iconfont icon-jiantou"></span></div>
		 </router-link>
	  </div>
	  <div class="title">
		 <a href="tel:08596888801" class="title1">
		  <div class="title1-1"><img src="@assets/images/customer.png" /></div>
		  <div class="title1-2">联系客服</div>
		  <div class="title1-3"><span class="iconfont icon-jiantou"></span></div>
		 </a>
	  </div>
	<div class="foot">
	  <div class="foot3">© 2017-2020 佰仟万电商平台 版权所有，并保留所有权利。</div>
	</div>		  
  </div>
	  
</template>

<script>
import { getUser, getMenuUser } from "@api/user";
import { isWeixin } from "@utils";
import { follow} from "@api/public";
import SwitchWindow from "@components/SwitchWindow";
import GeneralWindow from "@components/GeneralWindow";
const NAME = "User";

export default {
  name: NAME,
  components: {
    SwitchWindow,
    GeneralWindow
  },
  props: {},
  data: function() {
    return {
      isWeixin: isWeixin(),
      followUrl: "",
      subscribe: false,
      followHid: false,
      followCode: false,
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
    this.getFollow();
    this.User();
    this.MenuUser();
    this.isWeixin = isWeixin();
  },
  methods: {
    changeswitch: function(data) {
      this.switchActive = data;
    },
    closeFollow() {
      this.followHid = false;
    },
    followTap() {
      this.followCode = true;
      this.followHid = false;
    },
    closeFollowCode() {
      this.followCode = false;
      this.followHid = true;
    },
    getFollow() {
      follow()
        .then(res => {
          this.followUrl = res.data.path;
        })
        .catch(() => {});
    },
    User: function() {
      let that = this;
      getUser().then(res => {
        that.userInfo = res.data;
        that.orderStatusNum = res.data.orderStatusNum;
        that.subscribe = res.data.subscribe;
      });
    },
    MenuUser: function() {
      let that = this;
      getMenuUser().then(res => {
        that.MyMenus = res.data.routine_my_menus;
      });
    },
   
    goPages: function(index) {
      let url = this.MyMenus[index].wap_url;
      if (url === "/user/user_promotion") {
        if (!this.userInfo.is_promoter && this.userInfo.statu == 1)
          return this.$dialog.toast({ mes: "您还没有推广权限！！" });
        if (!this.userInfo.is_promoter && this.userInfo.statu == 2) {
          return (this.generalActive = true);
        }
      }
      if (url === "/customer/index" && !this.userInfo.adminid) {
        return this.$dialog.toast({ mes: "您还不是管理员！！" });
      }
      this.$router.push({ path: this.MyMenus[index].wap_url });
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

.followCode .pictrue {
    width: 5rem;
    height: 7.2rem;
    border-radius: 12px;
    left: 50%;
    top: 50%;
    margin-left: -2.5rem;
    margin-top: -3.6rem;
    position: fixed;
    z-index: 100000;
}
.followCode .pictrue img {
    width: 100%;
    height: 100%;
}
</style>
