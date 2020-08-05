<template>
<div>
  <div class="product-con storeBox"  ref="box" @scroll.native="onScroll">
    <div class="superior" id="title2">
      <product-con-swiper
        :img-urls="storeInfo.slider_image"
      ></product-con-swiper>
      <div class="storeBox-box" style="background: #fff;">
        <div class="store-cent-left" style="width:5">
          <div class="store-name">{{ storeInfo.name }}</div>
          <Reta :size="48" :score="4.5"></Reta>
          <div class="store-address line1">
            {{ storeInfo.detailed_address }}
          </div>
          <div class="store-address line1">
                               已消费{{ storeInfo.sales }}笔
          </div>
          <div class="store-address line1">
                               营业中<span style="color:#282828;margin-left:0.2rem;">{{ storeInfo.day_time}}</span>
          </div>
        </div>
        <div class="row-right">
          <div style="padding-right: 0.1rem;">
            <a class="store-phone" :href="'tel:' + storeInfo.phone"
              ><span class="iconfont icon-dadianhua01"></span
            ></a>
          </div>
          <div class="store-distance" @click.stop="showMaoLocation(storeInfo)">
            <span class="addressTxt">查看地图</span>
            <span class="iconfont icon-youjian"></span>
          </div>
        </div>
     </div>
     <div class="pay_btn" @click="goPay(storeInfo)">点击向商家付款</div>
     <div class="productList">
     <div v-if="goodList.length > 0">
      <div class="title acea-row row-center-wrapper" style="line-height:0.8rem;">
        <div class="titleTxt">商家为您推荐</div>
      </div>
      <div class="list acea-row row-between-wrapper" ref="container" style="margin-top:0rem;">
	      <div
	        @click="goDetail(item)"
	        v-for="(item, index) in goodList"
	        :key="index"
	        class="item"
	        :class="Switch === true ? '' : 'on'"
	        :title="item.store_name"
	      >
	        <div class="pictrue" :class="Switch === true ? '' : 'on'">
	          <img :src="item.image" :class="Switch === true ? '' : 'on'" />
	        </div>
	        <div class="text" :class="Switch === true ? '' : 'on'">
	          <div class="name pline1">{{ item.store_name }}</div>
	          <div class="money font-color-red">
	                                      ￥<span class="num">{{ item.price }}</span>
	               <span class="shou">已售{{ item.sales }}{{ item.unit_name }}</span>
	          </div>
	          <div class="vip acea-row row-between-wrapper" :class="Switch === true ? '' : 'on'">
	             <div v-if="item.belong_t == 0">
		            <div class="vip" v-if="item.pay_paypoint > 0"  style="width:3.3rem;">
		               <img src="@assets/images/fu.png" class="image" style="width: 0.35rem;" />{{ item.pay_paypoint || 0}}积分+￥{{ item.pay_amount || 0}}
		            </div>
		            <div class="vip" v-if="item.pay_repeatpoint > 0" style="width:3.3rem;">
		               <img src="@assets/images/fu.png" class="image" style="width: 0.35rem;"/>{{ item.pay_repeatpoint || 0}}个重消积分+￥{{ item.pay_amount || 0}}
		            </div>
		            <div class="vip" v-if="item.pay_repeatpoint ==0&&item.pay_paypoint==0"  style="width:3.3rem;">
		               <img src="@assets/images/fu.png" class="image" style="width:0.35rem;"/>￥{{ item.pay_amount || 0}}
		            </div>
		         </div>
	             <div class="vip-money" v-else>
	                 <img src="@assets/images/give.png" style="width: 0.35rem;"/>￥{{ item.pay_point }}消费积分
	             </div>
	          </div>
	        </div>
	      </div>
	      </div>
    </div>
  </div>
 </div>
 <div>
      <iframe
        v-if="locationShow && !isWeixin"
        ref="geoPage"
        width="0"
        height="0"
        frameborder="0"
        style="display:none;"
        scrolling="no"
        :src="
          'https://apis.map.qq.com/tools/geolocation?key=' +
            mapKey +
            '&referer=myapp'
        "
      >
      </iframe>
    </div>
    <div class="geoPage" v-if="mapShow">
      <iframe
        width="100%"
        height="100%"
        frameborder="0"
        scrolling="no"
        :src="
          'https://apis.map.qq.com/uri/v1/geocoder?coord=' +
            system_store.latitude +
            ',' +
            system_store.longitude +
            '&referer=' +
            mapKey
        "
      >
      </iframe>
    </div>
 </div>
</template>
<script>
import {swiperSlide } from "vue-awesome-swiper";
import "@assets/css/swiper.min.css";
import ProductConSwiper from "@components/ProductConSwiper";
import UserEvaluation from "@components/UserEvaluation";
import ShareRedPackets from "@components/ShareRedPackets";
import CouponPop from "@components/CouponPop";
import ProductWindow from "@components/ProductWindow";
import StorePoster from "@components/StorePoster";
import ShareInfo from "@components/ShareInfo";
import Reta from "@components/Star";
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
let NAME = "GoodsCon";
const LONGITUDE = "user_longitude";
const LATITUDE = "user_latitude";
const MAPKEY = "mapKey";
export default {
  name: NAME,
  components: {
    Reta,
    ProductConSwiper,
    UserEvaluation,
    ProductWindow
  },
  data: function() {
    return {
      shareInfoStatus: false,
      weixinStatus: false,
      mapShow: false,
      mapKey: cookie.get(MAPKEY),
      id: 0,
      storeInfo: {},
      goodList: [],
      lock: false,
      opacity: 0
    };
  },
  computed: mapGetters(["isLogin"]),
  watch: {
    $route(n) {
      if (n.name === NAME) {
        this.id = n.params.id;
        this.storeInfo.slider_image = [];
        this.productCon();
      }
    }
  },
  updated() {
    // window.scroll(0, 0);
  },
  mounted: function() {
   if (!cookie.get(LONGITUDE) && cookie.get(LATITUDE)) {
      this.selfLocation();
    }
    document.addEventListener("scroll", this.onScroll, false);
    this.id = this.$route.params.id;
    this.storeInfo.slider_image = [];
    this.productCon();
    window.addEventListener("scroll", this.handleScroll);
  },
  methods: {
    handleScroll() {
      let top = document.body.scrollTop || document.documentElement.scrollTop;
      let opacity = top / 350;
      opacity = opacity > 1 ? 1 : opacity;
      this.opacity = opacity;
    },
    goPay(item) {
        this.$router.push({ path: "/shoppay/" + item.id });
    },
    selfLocation() {
      if (isWeixin()) {
        wxShowLocation()
          .then(res => {
            cookie.set(LATITUDE, res.latitude);
            cookie.set(LONGITUDE, res.longitude);
            this.getList();
          })
          .catch(() => {
            cookie.remove(LATITUDE);
            cookie.remove(LONGITUDE);
            this.getList();
          });
      } else {
        if (!cookie.get(MAPKEY))
          return this.$dialog.error(
            "暂无法使用查看地图，请配置您的腾讯地图key"
          );
        let loc;
        let _this = this;
        if (cookie.get(MAPKEY)) _this.locationShow = true;
        //监听定位组件的message事件
        window.addEventListener(
          "message",
          function(event) {
            loc = event.data; // 接收位置信息 LONGITUDE
            console.log("location", loc);
            if (loc && loc.module == "geolocation") {
              cookie.set(LATITUDE, loc.lat);
              cookie.set(LONGITUDE, loc.lng);
              _this.getList();
            } else {
              cookie.remove(LATITUDE);
              cookie.remove(LONGITUDE);
              _this.getList();
              //定位组件在定位失败后，也会触发message, event.data为null
              console.log("定位失败");
            }
          },
          false
        );
        // this.$refs.geoPage.contentWindow.postMessage("getLocation", "*");
      }
    },
    showMaoLocation(e) {
      this.system_store = e;
      if (isWeixin()) {
        let config = {
          latitude: parseFloat(this.system_store.latitude),
          longitude: parseFloat(this.system_store.longitude),
          name: this.system_store.name,
          address:
            this.system_store.address + this.system_store.detailed_address
        };
        wechatEvevt("openLocation", config)
          .then(res => {
            console.log(res);
          })
          .catch(res => {
            if (res.is_ready) {
              res.wx.openLocation(config);
            }
          });
      } else {
        if (!cookie.get(MAPKEY))
          return this.$dialog.error(
            "暂无法使用查看地图，请配置您的腾讯地图key"
          );
        this.mapShow = true;
      }
    },
    // 商品详情跳转
    goDetail(item) {
        this.$router.push({ path: "/detail/" + item.id });
    },
    onScroll: debounce(function() {
      if (this.lock) {
        return;
      }
      const headerHeight = this.$refs.header.offsetHeight,
        { scrollY } = window,
        titles = [];
      
      if (this.goodList.length) {
        titles.push(document.querySelector("#title2"));
      }
      titles.push(document.querySelector("#title3"));
      titles.reduce((initial, title, index) => {
        if (initial) return initial;
        if (scrollY + headerHeight < title.offsetTop + title.offsetHeight) {
          initial = true;
        }
        return initial;
      }, false);
      this.lock = true;
    }, 500),
    showChang: function() {
      if (isWeixin()) {
        let config = {
          latitude: parseFloat(this.storeItems.latitude),
          longitude: parseFloat(this.storeItems.longitude),
          name: this.storeItems.name,
          address: this.storeItems.address + this.system_store.detailed_address
        };
        wechatEvevt("openLocation", config)
          .then(res => {
            console.log(res);
          })
          .catch(res => {
            if (res.is_ready) {
              res.wx.openLocation(config);
            }
          });
      } else {
        if (!this.mapKey)
          return this.$dialog.error(
            "暂无法使用查看地图，请配置您的腾讯地图key"
          );
        this.mapShow = true;
      }
    },
   
    //商家详情接口；
    productCon: function() {
      let that = this;
      getStoreDetail(that.id)
        .then(res => {
          that.$set(that, "storeInfo", res.data.storeInfo);
          that.$set(that, "goodList", res.data.good_list || []);
         that.mapKey = res.data.mapKey;
        })
        .catch(res => {
          that.$dialog.error(res.msg);
          that.$router.go(-1);
        });
    },
    goGoods(item) {
      if (item.activity && item.activity.type === "1") {
        this.$router.push({
          path:
            "/activity/seckill_detail/" +
            item.activity.id +
            "/" +
            item.activity.time +
            "/1"
        });
      } else if (item.activity && item.activity.type === "2") {
        this.$router.push({
          path: "/activity/dargain_detail/" + item.activity.id
        });
      } else if (item.activity && item.activity.type === "3") {
        this.$router.push({
          path: "/activity/group_detail/" + item.activity.id
        });
      } else {
        this.$router.push({ path: "/detail/" + item.id });
      }
    },
    
    listenerActionSheet: function() {
      if (isWeixin() === true) {
        this.weixinStatus = true;
      }
      this.posters = true;
    },
    listenerActionClose: function() {
      this.posters = false;
    }
  },
  beforeDestroy: function() {
    document.removeEventListener("scroll", this.onScroll, false);
    window.removeEventListener("scroll", this.handleScroll);
  }
};
</script>
<style scoped>
.product-bg{height:5.5rem;}
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
  width: 96%;
  margin-left:2%;
  height: auto;
  display: flex;
  align-items: center;
  padding: 0.23rem 0;
  justify-content: space-between;
  border-bottom: 1px solid #eee;
}
.store-cent {
  display: flex;
  align-items: center;
  width: 80%;
}
.store-cent-left {
  width: 70%;
}
.store-img {
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 0.06rem;
  margin-right: 0.22rem;
}
.store-img img {
  width: 100%;
}
.store-name {
  color: #282828;
  font-size: 0.3rem;
  font-weight: 800;
  letter-spacing: 3px;
  margin-bottom: 0.2rem;
  text-overflow: ellipsis;display: -webkit-box;-webkit-box-orient: vertical;overflow: hidden;-webkit-line-clamp: 1;
}
.store-address {
  color: #282828;
  font-size: 0.24rem;
  line-height:0.4rem;
  letter-spacing: 1px;
}
.store-phone {
  width: 0.5rem;
  height: 0.5rem;
  color: #fff;
  border-radius: 50%;
  display: block;
  text-align: center;
  line-height: 0.5rem;
  background-color: #e83323;
  margin-bottom: 0.22rem;
}
.store-distance {
  font-size: 0.22rem;
  color: #e83323;
}
.iconfont {
  font-size: 0.2rem;
}
.row-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  width: 28%;
}
</style>
