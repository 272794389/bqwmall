<template>
  <div class="index" v-cloak>
    <div
      class="follow acea-row row-between-wrapper"
      v-if="followHid && isWeixin"
    >
      <div>点击“立即关注”即可关注公众号</div>
      <div class="acea-row row-middle">
        <div class="bnt" @click="followTap">立即关注</div>
        <span class="iconfont icon-guanbi" @click="closeFollow"></span>
      </div>
    </div>
    <div class="followCode" v-if="followCode">
      <div class="pictrue"><img :src="followUrl" /></div>
      <div class="mask" @click="closeFollowCode"></div>
    </div>
    <div class="header acea-row row-center-wrapper">
      <div class="logo"><img :src="logoUrl" /></div>
      <router-link :to="'/search'" class="search acea-row row-middle">
        <span class="iconfont icon-xiazai5"></span>搜索商品
      </router-link>
    </div>
    <div class="slider-banner banner">
      <swiper :options="swiperOption" v-if="banner.length > 0">
        <swiper-slide v-for="(item, index) in banner" :key="index">
          <img :src="item.pic" />
        </swiper-slide>
        <div class="swiper-pagination paginationBanner" slot="pagination"></div>
      </swiper>
    </div>
    <!--分类菜单导航-->
    <div class="nav acea-row">
     <swiper :options="dswiperOption" v-if="info.fastList.length > 0" style="padding-bottom:0.5rem;">
        <swiper-slide>
		      <router-link tag="a" target="_blank"
		        :to="'/gcategory/'+item.id"
		        class="item"
		        v-for="(item, index) in info.fastList"
		        :key="index"
		      >
		        <div class="pictrue"><img :src="cdnZipImg(item.pic)" /></div>
		        <div>{{ item.cate_name }}</div>
		      </router-link>
		   </swiper-slide>
		   <swiper-slide v-if="info.sfastList.length > 0">
		      <router-link tag="a" target="_blank" 
		        :to="'/gcategory/'+item.id"
		        class="item"
		        v-for="(item, index) in info.sfastList"
		        :key="index"
		      >
		        <div class="pictrue"><img :src="cdnZipImg(item.pic)" /></div>
		        <div>{{ item.cate_name }}</div>
		      </router-link>
		   </swiper-slide>
           <swiper-slide v-if="info.tfastList.length > 0">
		      <router-link tag="a" target="_blank" 
		        :to="'/gcategory/'+item.id"
		        class="item"
		        v-for="(item, index) in info.tfastList"
		        :key="index"
		      >
		        <div class="pictrue"><img :src="cdnZipImg(item.pic)" /></div>
		        <div>{{ item.cate_name }}</div>
		      </router-link>
		   </swiper-slide>
           <swiper-slide v-if="info.ffastList.length > 0">
		      <router-link tag="a" target="_blank" 
		        :to="'/gcategory/'+item.id"
		        class="item"
		        v-for="(item, index) in info.ffastList"
		        :key="index"
		      >
		        <div class="pictrue"><img  :src="cdnZipImg(item.pic)"/></div>
		        <div>{{ item.cate_name }}</div>
		      </router-link>
		   </swiper-slide>
		   <div class="swiper-pagination dpaginationBanner" style="margin-top:1.3rem;" slot="pagination"></div>
       </swiper>
    </div>
    <div class="ad_tong" v-if="article.title">
        <marquee direction="left" class="marqstyle">
        <router-link :to="'/news_detail/' + article.id" class="acea-row row-middle"
          >{{ article.title }}</router-link></marquee>
    </div>
    <div class="specialArea acea-row row-between-wrapper">
      <router-link :to="''" class="assemble" style="background: rgb(9,197,15);">
        <div class="sp">
	          <div class="name">商品中心</div>
	          <div class="infor">各种实用实惠的商品</div>
	        </div>
      </router-link>
      <router-link :to="''" class="assemble" style="background: rgb(44,176,126);">
        <div class="sp">
	        <div class="text">
	          <div class="name">周边的店</div>
	          <div class="infor">本地吃喝玩乐购商家</div>
	        </div>
        </div>
      </router-link>
      <router-link :to="''"  class="assemble" style="background: rgb(23,162,229);" >
          <div class="name">网店	</div>
          <div class="infor">商家产品优惠套餐</div>
      </router-link>
    </div>
    <div class="wrapper" v-if="info.bastList.length > 0">
      <div class="title acea-row row-between-wrapper">
        <div class="text">
          <div class="name line1">商品中心推荐</div>
          <div class="line1" style="color: #f00;">支持消费积分、重消积分兑换</div>
        </div>
        <router-link :to="{ path: '/hot_new_goods/' + 1 }" class="more"
          >更多<span class="iconfont icon-jiantou"></span
        ></router-link>
      </div>
      <ShopGood-list :good-list="info.bastList" :is-sort="false"></ShopGood-list>
    </div>
    
    <div class="wrapper" v-if="info.netGoodList">
      <div class="title acea-row row-between-wrapper">
        <div class="text">
          <div class="name line1">网店商品推荐</div>
          <div class="line1" style="color: #f00;">现金支付赠送消费积分，可用抵扣券抵扣</div>
        </div>
        <router-link :to="'/promotion'" class="more"
          >更多<span class="iconfont icon-jiantou"></span
        ></router-link>
      </div>
      <div class="productList" ref="container">
	         <div class="list acea-row row-between-wrapper" :class="on" ref="container" style="margin-top:0px;">
			      <div @click="goDetail(item)" v-for="(item, index) in info.netGoodList" :key="index" class="item" :title="item.store_name">
				        <div class="pictrue">
				          <img :src="item.image"/> 
				        </div>
				        <div class="text">
				          <div class="name pline1">{{ item.store_name }}</div>
				          <div class="money font-color-red">
				                                   ￥<span class="num">{{ item.price }}</span>
				              <span class="shou">已售{{ item.sales }}{{ item.unit_name }}</span>
				          </div>
				          <div class="vip acea-row row-between-wrapper">
				            <div class="vip-money">
				                 <img src="@assets/images/give.png" />￥{{ item.pay_point }}消费积分
				            </div>
				          </div>
				        </div>
			      </div>
			</div>
	    </div>
    </div>
    
    <div class="wrapper">
      <div class="title acea-row row-between-wrapper">
        <div class="text">
          <div class="name line1">周边的店推荐</div>
          <div class="line1" style="color: #f00;">赠消费积分|购物积分支付|抵扣券抵扣</div>
        </div>
        <router-link :to="'/shop/storeList/'" class="more"
          >更多<span class="iconfont icon-jiantou"></span
        ></router-link>
      </div>
      <div class="goodList">
		    <div class="item acea-row row-between-wrapper shangjia" @click="goShop(item)" v-for="(item, index) in storeList" :key="index">
		      <div class="pictrue" style="width:2.0rem;">
		         <img :src="item.image" class="image">
		      </div>
		      <div class="shop_box" style="height:2.0rem">
		        <div class="text">
		          <div class="pline2">{{ item.name }}</div>
		          <div class="shoptip">ktv&nbsp;|&nbsp;{{ item.range }}km,，已消费{{ item.sales }}笔</div>
		          <div class="shoptip shopaddress">{{ item.address }}{{ ", " + item.detailed_address }}</div>
		          <div class="shoptip">购物积分支付比例：{{ item.give_rate }}%</div>
		        </div>
		      </div>
		    </div>
	 </div>
    </div>
   
    <div class="wrapper" v-if="info.nearGoodList">
	      <div class="title acea-row row-between-wrapper" style="border-top:none;">
	        <div class="text">
	          <div class="name line1">吃喝玩乐推荐</div>
	          <div class="line1" style="color: #f00;">赠消费积分|购物积分支付|抵扣券抵扣</div>
	        </div>
	        <router-link :to="{ path: '/hot_new_goods/' + 1 }" class="more"
	          >更多<span class="iconfont icon-jiantou"></span
	        ></router-link>
	      </div>
	      <div class="productList" ref="container">
	         <div class="list acea-row row-between-wrapper" :class="on" ref="container" style="margin-top:0px;">
			      <div @click="goDetail(item)" v-for="(item, index) in info.nearGoodList" :key="index" class="item" :title="item.store_name">
				        <div class="pictrue">
				          <img :src="item.image"/> 
				        </div>
				        <div class="text">
				          <div class="name pline1">{{ item.store_name }}</div>
				          <div class="money font-color-red">
				                                   ￥<span class="num">{{ item.price }}</span>
				              <span class="shou">已售{{ item.sales }}{{ item.unit_name }}</span>
				          </div>
				          <div class="vip acea-row row-between-wrapper">
				            <div class="vip-money">
				                 <img src="@assets/images/give.png" />￥{{ item.pay_point }}消费积分
				            </div>
				          </div>
				        </div>
			      </div>
			</div>
	    </div>
    </div>
    <Coupon-window
      :coupon-list="couponList"
      v-if="showCoupon"
      @checked="couponClose"
      @close="couponClose"
    ></Coupon-window>
    <div style="height:1.2rem;"></div>
    <div>
      <iframe
        v-if="mapKey && !isWeixin"
        ref="geoPage"
        width="0"
        height="0"
        frameborder="0"
        scrolling="no"
        :src="
          'https://apis.map.qq.com/tools/geolocation?key=' +
            mapKey +
            '&referer=myapp'
        "
      >
      </iframe>
    </div>
  </div>
</template>
<script>
import { swiper, swiperSlide } from "vue-awesome-swiper";
import "@assets/css/swiper.min.css";
import { storeListApi } from "@api/store";
import GoodList from "@components/GoodList";
import ShopGoodList from "@components/ShopGoodList";
import PromotionGood from "@components/PromotionGood";
import CouponWindow from "@components/CouponWindow";
import { getHomeData, getShare, follow } from "@api/public";
import cookie from "@utils/store/cookie";
import { openShareAll, wxShowLocation } from "@libs/wechat";
import { isWeixin, cdnZipImg } from "@utils/index";
import { mapGetters } from "vuex";
const HAS_COUPON_WINDOW = "has_coupon_window";
const LONGITUDE = "user_longitude";
const LATITUDE = "user_latitude";
const MAPKEY = "mapKey";
let vm = null;
export default {
  name: "Index",
  components: {
    swiper,
    swiperSlide,
    GoodList,
    ShopGoodList,
    PromotionGood,
    CouponWindow
  },
  props: {},
  computed: mapGetters(["isLogin"]),
  data: function() {
    return {
      newGoodsBananr: "",
      isWeixin: isWeixin(),
      followUrl: "",
      subscribe: false,
      followHid: false,
      followCode: false,
      showCoupon: false,
      logoUrl: "",
      banner: [],
      article: {},
      storeList: [],
      info: {
        fastList: [],
        bastList: [],
        netGoodList: [],
        nearGoodList: []
      },
      couponList: [],
      swiperOption: {
        pagination: {
          el: ".paginationBanner",
          clickable: true
        },
        autoplay: {
          disableOnInteraction: false,
          delay: 2000
        },
        loop: true,
        speed: 1000,
        observer: true,
        observeParents: true,
        on: {
          tap: function() {
            const realIndex = this.realIndex;
            vm.goUrl(realIndex);
          }
        }
      },
      dswiperOption: {
        pagination: {
          el: ".dpaginationBanner",
          clickable: true
        },
        autoplay:false,
        loop: true,
        speed: 1000,
        observer: true,
        observeParents: true
      },
      swiperRoll: {
        direction: "vertical",
        autoplay: {
          disableOnInteraction: false,
          delay: 2000
        },
        loop: true,
        speed: 1000,
        observer: true,
        observeParents: true
      },
      swiperScroll: {
        freeMode: true,
        freeModeMomentum: false,
        slidesPerView: "auto",
        observer: true,
        observeParents: true
      },
      swiperBoutique: {
        pagination: {
          el: ".paginationBoutique",
          clickable: true
        },
        autoplay: {
          disableOnInteraction: false,
          delay: 2000
        },
        loop: true,
        speed: 1000,
        observer: true,
        observeParents: true
      },
      swiperProducts: {
        freeMode: true,
        freeModeMomentum: false,
        slidesPerView: "auto",
        observer: true,
        observeParents: true
      },
      mapKey: ""
    };
  },
  created() {
    vm = this;
  },
  mounted: function() {
    this.getFollow();
    let that = this;
    getHomeData().then(res => {
      that.mapKey = res.data.tengxun_map_key;
      cookie.set(MAPKEY, that.mapKey);
      that.logoUrl = res.data.logoUrl;
      that.newGoodsBananr = res.data.newGoodsBananr;
      that.$set(that, "banner", res.data.banner);
      that.$set(that, "activity", res.data.activity);
      that.$set(that, "article", res.data.article);
     
      that.$set(that, "info", res.data.info);
  
      that.$set(that, "couponList", res.data.couponList);
      if (that.isLogin) {
        that.subscribe = res.data.subscribe;
        if (!that.subscribe && that.followUrl) {
          setTimeout(function() {
            that.followHid = true;
          }, 200);
        }
      } else {
        that.followHid = false;
      }
      if (res.data.site_name) document.title = res.data.site_name;
      that.setOpenShare();
      this.showCoupon = !cookie.has(HAS_COUPON_WINDOW) && res.data.couponList.some(coupon => coupon.is_use);
      if (!cookie.get(LATITUDE) && !cookie.get(LONGITUDE)) this.getWXLocation();
    });
    if (cookie.get(LONGITUDE) && cookie.get(LATITUDE)) {
      this.getList();
    }
  },
  methods: {
    // 轮播图跳转
    cdnZipImg,
    goUrl(index) {
      let url = this.banner[index].wap_url;
      let newStr = url.indexOf("http") === 0;
      if (newStr) {
        window.location.href = url;
      } else {
        this.$router.push({
          path: url
        });
      }
    },
    // 商品详情跳转
    goDetail(item) {
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
    getWXLocation() {
      if (isWeixin()) {
        wxShowLocation();
      } else {
        if (!this.mapKey)
          console.log("暂无法使用查看地图，请配置您的腾讯地图key");
        let loc;
        // if (_this.$route.params.mapKey) _this.locationShow = true;
        //监听定位组件的message事件
        window.addEventListener(
          "message",
          function(event) {
            loc = event.data; // 接收位置信息 LONGITUDE
            console.log("location", loc);
            if (loc && loc.module == "geolocation") {
              cookie.set(LATITUDE, loc.lat);
              cookie.set(LONGITUDE, loc.lng);
            } else {
              cookie.remove(LATITUDE);
              cookie.remove(LONGITUDE);
              //定位组件在定位失败后，也会触发message, event.data为null
              console.log("定位失败");
            }
          },
          false
        );
      }
    },
     // 获取门店列表数据
    getList: function() {
      let data = {
        latitude: cookie.get(LATITUDE) || "", //纬度
        longitude: cookie.get(LONGITUDE) || "", //经度
        page: 1,
        limit: 10
      };
      storeListApi(data)
        .then(res => {
          this.storeList.push.apply(this.storeList, res.data.list);
        })
        .catch(err => {
          this.$dialog.error(err.msg);
        });
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
    couponClose() {
      cookie.set(HAS_COUPON_WINDOW, 1);
    },
    getFollow() {
      follow()
        .then(res => {
          this.followUrl = res.data.path;
        })
        .catch(() => {});
    },
    setOpenShare() {
      if (isWeixin()) {
        getShare().then(res => {
          var data = res.data.data;
          var configAppMessage = {
            desc: data.synopsis,
            title: data.title,
            link: location.href,
            imgUrl: data.img
          };
          openShareAll(configAppMessage);
        });
      }
    }
  }
};
</script>
<style scoped>

.index .follow {
  z-index: 100000;
}
.marqstyle{
     margin-left:0.3rem;line-height:0.7rem;
}
.marqstyle a{color:#fff;}
.swiper-pagination-bullet-active{
    opacity: 1;
    background: #FFBA00;
}
.swiper-pagination-bullet {
    width: 18px;
    height: 5px;
    display: inline-block;
    border-radius: 0%; 
    background: #666;
    opacity: .2;
}
</style>
