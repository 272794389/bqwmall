<template>
  <div class="index" v-cloak style="background: #F5F5F5;">
    <div class="header acea-row row-center-wrapper" style="background:#fff;">
     
      <router-link :to="'/tsearch'" class="search acea-row row-middle" style="width:96%;">
        <span class="iconfont icon-xiazai5"></span>搜索特惠商品
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
     <swiper :options="dswiperOption" v-if="info.fastList.length > 0">
        <swiper-slide>
		      <router-link tag="a" target="_blank"
		        :to="{
	               path: '/tgoods_list',
	               query: { sid: item.id,cid:0, title: item.cate_name }
	             }"
		        class="item"
		        v-for="(item, index) in info.fastList"
		        :key="index"
		      >
		        <div class="pictrue"><img :src="item.pic" /></div>
		        <div>{{ item.cate_name }}</div>
		      </router-link>
		   </swiper-slide>
		   <swiper-slide v-if="info.sfastList.length > 0">
		      <router-link tag="a" target="_blank" 
		         :to="{
	               path: '/tgoods_list',
	               query: { sid: item.id,cid:0, title: item.cate_name }
	             }"
		        class="item"
		        v-for="(item, index) in info.sfastList"
		        :key="index"
		      >
		        <div class="pictrue"><img :src="item.pic" /></div>
		        <div>{{ item.cate_name }}</div>
		      </router-link>
		   </swiper-slide>
           <swiper-slide v-if="info.tfastList.length > 0">
		      <router-link tag="a" target="_blank" 
		        :to="{
	               path: '/tgoods_list',
	               query: { sid: item.id,cid:0, title: item.cate_name }
	             }"
		        class="item"
		        v-for="(item, index) in info.tfastList"
		        :key="index"
		      >
		        <div class="pictrue"><img :src="item.pic" /></div>
		        <div>{{ item.cate_name }}</div>
		      </router-link>
		   </swiper-slide>
           <swiper-slide v-if="info.ffastList.length > 0">
		      <router-link tag="a" target="_blank" 
		        :to="{
	               path: '/tgoods_list',
	               query: { sid: item.id,cid:0, title: item.cate_name }
	             }"
		        class="item"
		        v-for="(item, index) in info.ffastList"
		        :key="index"
		      >
		        <div class="pictrue"><img  :src="item.pic"/></div>
		        <div>{{ item.cate_name }}</div>
		      </router-link>
		   </swiper-slide>
		   <!--
		   <div class="swiper-pagination dpaginationBanner" style="margin-top:1.3rem;" slot="pagination"></div>
		   -->
       </swiper>
    </div>
    <div class="title acea-row row-between-wrapper" style="width:96%;margin-left:3%;">
        <div class="text">
          <div class="name line1 blabel">本地特惠推荐</div>
        </div>
    </div>
    <div class="wrapper" v-if="nearGoodList.length>0">
	      <div class="productList" ref="container">
	         <div class="list acea-row row-between-wrapper" :class="on" ref="container" style="margin-top:0px;">
			      <div @click="goDetail(item)" v-for="(item, index) in nearGoodList" :key="index" class="item" :title="item.store_name">
				        <div class="pictrue">
				          <img :src="item.image"/> 
				        </div>
				        <div class="text">
				          <div class="name pline1">{{ item.store_name }}</div>
				          <div class="money font-color-red">
				                                   ￥<span class="num">{{ item.price }}</span>
				              <span class="shou">原价{{ item.ot_price }}</span>
				          </div>
				          <div class="money">
				              <span class="activity" v-if="item.coupon_price>0">可用券抵扣{{ item.coupon_price }}元</span>
				              <span class="shou" style="margin-left:0px;" v-else>已售{{ item.sales }}{{ item.unit_name }}</span>
				          </div>
				        </div>
			      </div>
			</div>
	    </div>
	    <div class="morestyle" style="margin-bottom:0.8rem;"><router-link :to="'/tgoods_list/'" >去看更多本地优惠套餐&nbsp;></router-link></div>
    </div>
    <div class="noCommodity" v-else  v-cloak  style="background-color: #fff; margin-top: 0.3rem;padding-bottom: 2rem;">
      <div class="noPictrue">
        <img src="@assets/images/noGood.png" class="image" />
      </div>
    </div>
    <Coupon-window
      :coupon-list="couponList"
      v-if="showCoupon"
      @checked="couponClose"
      @close="couponClose"
    ></Coupon-window>
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
import Reta from "@components/Star";
import { getHomeData, getShare, follow,goodHuiListApi,getNearStoreData } from "@api/public";
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
    Reta,
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
      searchBarFixed:false,
      newGoodsBananr: "",
      isWeixin: isWeixin(),
      followUrl: "",
      subscribe: false,
      followHid: false,
      followCode: false,
      showCoupon: false,
      logoUrl: "",
      lat:"",
      lang:"",
      condition:3,
      banner: [],
      nearGoodList: [],
      info: {
        fastList: []
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
    this.getWXLocation();
    getHomeData().then(res => {
      that.mapKey = res.data.tengxun_map_key;
      cookie.set(MAPKEY, that.mapKey);
      that.logoUrl = res.data.logoUrl;
      that.newGoodsBananr = res.data.newGoodsBananr;
      that.$set(that, "info", res.data.info);
      that.$set(that, "banner", res.data.hbanner);
      that.$set(that, "couponList", res.data.couponList);
      if (res.data.site_name) document.title = res.data.site_name;
      that.setOpenShare();
      this.getList();
    }).catch(err => {
        getNearStoreData().then(res => {
        this.$set(this, "nearGoodList", res.data.nearGoodList);
       });
    });
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
    handleScroll () {
    /*
      var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop
      var offsetTop = document.querySelector('#searchBar').offsetTop
      if (scrollTop > offsetTop) {
        this.searchBarFixed = true
      } else {
        this.searchBarFixed = false
      }
      */
      // console.log(scrollTop,offsetTop)
    },
    //点击事件处理
    set_where: function(index) {
      let that = this;
      that.condition = index;
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
    goShop(item){
       this.$router.push({ path: "/sdetail/" + item.id });
    },
    destroyed () {
    window.removeEventListener('scroll', this.handleScroll)
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
      this.lat = cookie.get(LATITUDE);
      this.lang = cookie.get(LONGITUDE);
    },
   
     // 获取门店列表数据
    getList: function() {
       if (!this.lat && !this.lang){
          getNearStoreData().then(res => {
           this.$set(this, "storeList", res.data.storeList);
           this.$set(this, "nearGoodList", res.data.nearGoodList);
          });
       }else{
	      let data = {
	        latitude: this.lat, //纬度
	        longitude: this.lang, //经度
	        page: 1,
	        limit: 20
	      };
	       goodHuiListApi(data).then(res => {
              this.nearGoodList.push.apply(this.nearGoodList, res.data.list);
           }) .catch(err => {
               this.$dialog.error(err.msg);
           });
	  }
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
.isFixed{
    position:fixed;
    left: 0;
    top: 0;
    width: 100%;
    z-index: 11;
    background:#f5f5f5;
    padding-bottom: 0.2rem;
    padding-top: 0.2rem;
}
.star{margin-bottom:0.1rem;margin-top:0.1rem;}
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
.nav_box{width: 25%; float: left;}
.nav_title{width: 100%;float: left; text-align: center;font-size: 0.35rem;color: #000;}
.nav_title_font{width: 100%;float: left; text-align: center;font-size: 0.35rem;color: #f62c2c;}
.nav_desc{float: left; width: 80%;font-size: 0.25rem; text-align: center;color: #999; margin-top:0.1rem;margin-left:10%;}
.nav_on{background: linear-gradient(90deg,#f62c2c,#f96e29);color: #fff; border-radius: 7px;}
.index .wrapper .newProducts .swiper-slide{width:2.2rem;}
.index .wrapper .newProducts .swiper-slide .img-box{height:2.2rem;}
.index .wrapper .newProducts .swiper-slide .money{padding:0rem;}
.slider-banner{width: 96%;margin-left:2%;border-radius:0.2rem;}
.cate_style{color: #f96829; margin-right: 0.3rem;line-height: 0.5rem;}
.morestyle{width: 96%;margin-left: 2%; text-align: center;line-height: 1rem;background: #fff;margin-top: 0.2rem;}
.morestyle a{color: #14adfb;font-weight: 700;}
.activity {
    height: .4rem;
    padding: 0 .2rem;
    border: 1px solid #f2857b;
    color: #e93323;
    font-size: .24rem;
    line-height: .4rem;
    position: relative;
    margin: 0.1rem .15rem .1rem 0;
    float:left;
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
.title .text .name {
    color: #282828;
    font-size: .3rem;
    font-weight: 700;
    margin-bottom: .05rem;
    position: relative;
}
.title .more .iconfont {
    margin-left: .02rem;
    font-size: .26rem;
}
</style>
