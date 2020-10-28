<template>
<div>
  <div class="product-con storeBox"  ref="box" @scroll.native="onScroll">
    <div class="superior" id="title2">
      <div class="store-name">{{ storeInfo.name }}</div>
      <div style="width: 100%;line-height: 0.8rem;color: #282828;">
                    {{storeInfo.cate_name}} &nbsp;|&nbsp;{{ storeInfo.sales }}人消费过 </div>
      <div class="storeBox-box">
        <div class="pingfen" v-if="couponList.length > 0">
	       <div v-for="(item, index) in couponList" :key="index">
	           <span class="activity">满{{item.use_min_price}}元抵{{item.coupon_price}}元</span>
	       </div>
        </div>
     </div>
 </div>
 </div>
 </div>
</template>
<script>
import {swiperSlide } from "vue-awesome-swiper";
import "@assets/css/swiper.min.css";
import ProductConSwiper from "@components/ProductConSwiper";
import UserEvaluation from "@components/UserEvaluation";
import CouponPop from "@components/CouponPop";
import ProductWindow from "@components/ProductWindow";
import debounce from "lodash.debounce";
import {
  getProductDetail,
  getProductCode,
  storeListApi,
  getStoreDetail
} from "@api/store";
import {getUserInfo} from "@api/user";
import { isWeixin } from "@utils/index";
import { wechatEvevt } from "@libs/wechat";
import { imageBase64 } from "@api/public";
import { mapGetters } from "vuex";
import cookie from "@utils/store/cookie";
let NAME = "CouponCon";
export default {
  name: NAME,
  components: {
    ProductConSwiper,
    UserEvaluation,
    ProductWindow
  },
  data: function() {
    return {
      id: 0,
      storeInfo: {},
      couponList:[]
    };
  },
  watch: {
    $route(n) {
      if (n.name === NAME) {
        this.id = n.params.id;
        this.productCon();
      }
    }
  },
  updated() {
    // window.scroll(0, 0);
  },
  mounted: function() {
    this.id = this.$route.params.id;
    this.productCon();
  },
  methods: {
    //商家详情接口；
    productCon: function() {
      let that = this;
      getStoreDetail(that.id)
        .then(res => {
          that.$set(that, "storeInfo", res.data.storeInfo);
          that.$set(that, "couponList", res.data.acoupon_List || []);
        })
        .catch(res => {
          that.$dialog.error(res.msg);
          that.$router.go(-1);
        });
    },
  }
};
</script>
<style scoped>
.product-bg{height:3.5rem;border-radius:0.1rem;}
.store-name {
  color: #282828;
  font-size: 0.5rem;
  font-weight: 800;
  line-height: 0.6rem;
  padding-top: 0.2rem;
}
.pingfen{float:left;width:100%;border-bottom: 1px solid #eee;color: #282828;}
.pingfent{float:left;width:100%;border-bottom: 1px solid #eee;color: #282828;padding-top: 0.1rem;
    padding-bottom: 0.2rem;}
.pingfen span{float:left;}
.pingfen_box{float:left;wdith:80%;margin-left:0.1rem;margin-top: 0.05rem;}
.daohang{float:right;color:#666;width:7%;}
.daohang img{width:0.5rem;}
.location{float:left;width:90%;line-height: 0.6rem;}

.geoPage {
  position: fixed;
  width: 100%;
  height: 100%;
  top: 0;
  z-index: 10000;
}
.storeBox {
  width: 100%;
  background-color: #fff;
  padding: 0 0.3rem;
}
.storeBox-box {
  width: 100%;
  height: auto;
  padding: 0.23rem 0;
  float: left;
}
.store-phone{float:right;margin-left:0.1rem;}
.activity {
    width:47%;
    height: .4rem;
    padding: 0 .2rem;
    border: 1px solid #f2857b;
    color: #e93323;
    font-size: .24rem;
    line-height: .4rem;
    position: relative;
    margin: 0rem .15rem .1rem 0;
}
.activity:before {
    content: " ";
    position: absolute;
    width: .07rem;
    height: .1rem;
    border-radius: 0 .07rem .07rem 0;
    border: 1px solid #f2857b;
    background-color: #fff;
    bottom: 50%;
    left: -.02rem;
    margin-bottom: -.07rem;
    border-left-color: #fff;
}
.activity:after {
    content: " ";
    position: absolute;
    width: .07rem;
    height: .1rem;
    border-radius: .07rem 0 0 .07rem;
    border: 1px solid #f2857b;
    background-color: #fff;
    right: -.02rem;
    bottom: 50%;
    margin-bottom: -.05rem;
    border-right-color: #fff;
}
</style>
