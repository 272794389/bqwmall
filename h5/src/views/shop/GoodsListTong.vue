<template>
  <div class="productList" ref="container">
    <form @submit.prevent="submitForm">
      <div class="search bg-color-red acea-row row-between-wrapper">
        <div class="samebox"">
           <!--<span @click="set_where(0)">分类</span>-->
           <div class="font-img" @click="set_where(0)"></div>
           <span @click="set_where(1)" :class="condition==1 ? 'on' : ''">同城</span>
           <span @click="set_where(2)" :class="condition==3 ? 'on' : ''" style="width:" style="width: 1.0rem;overflow: hidden;padding-left:0.1rem;padding-right:0.1rem;">{{model2}}</span>
           <CitySelect
              ref="cityselect"
              v-model="show2"
              :callback="result2"
              :items="district"
              :ready="ready"
              provance=""
              city=""
              area=""
            ></CitySelect>
        </div>
        <div class="input acea-row row-between-wrapper"  style="width: 3.5rem;margin-right:0.5rem;">
          <span class="iconfont icon-sousuo"></span>
          <input placeholder="搜索商品信息" v-model="where.keyword"  style="width: 2.5rem;"/>
        </div>
      </div>
    </form>
    <div class="aside">
      <div class="item acea-row row-center-wrapper" @click="asideTap(0)" :class="0 === navActive ? 'on' : ''">
        <span>全部</span>
      </div>
      <div
        class="item acea-row row-center-wrapper"
        :class="item.id === navActive ? 'on' : ''"
        v-for="(item, index) in category"
        :key="index"
        @click="asideTap(item.id)"
      >
        <span>{{ item.cate_name }}</span>
      </div>
    </div>
   
    <div
      class="list acea-row row-between-wrapper"
      :class="Switch === true ? '' : 'on'"
      ref="container"
    >
      <div
        @click="goDetail(item)"
        v-for="(item, index) in productList"
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
          <div class="money font-color-red" :class="Switch === true ? '' : 'on'">
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
    <Loading :loaded="loadend" :loading="loading"></Loading>
    <div
      class="noCommodity"
      v-cloak
      style="background-color: #fff;"
      v-if="productList.length === 0 && where.page > 1"
    >
      <div class="noPictrue">
        <img src="@assets/images/noGood.png" class="image" />
      </div>
    </div>
    <Recommend v-if="productList.length === 0 && where.page > 1"></Recommend>
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
import { CitySelect } from "vue-ydui/dist/lib.rem/cityselect";
import { getCity } from "@api/public";
import Recommend from "@components/Recommend";
import { isWeixin } from "@utils/index";
import { wechatEvevt, wxShowLocation } from "@libs/wechat";
import debounce from "lodash.debounce";
import Loading from "@components/Loading";
import { getProducts,getDetailCategory } from "@api/store";
import cookie from "@utils/store/cookie";
const LONGITUDE = "user_longitude";
const LATITUDE = "user_latitude";
const MAPKEY = "mapKey";
export default {
  name: "GoodsListTong",
  components: {
    Recommend,
    Loading,CitySelect
  },
  props: {},
  data: function() {
    const { s = "", sid = 0,cid=0, title = "" } = this.$route.query;
    return {
      hostProduct: [],
      productList: [],
      category: [],
      navActive: cid,
      Switch: true,
      where: {
        page: 1,
        belong_t:2,
        latitude:"",
        longitude:"",
        city: "",
        district: "",
        limit: 8,
        keyword: s,
        sid: sid, //一级分类id
        cid: cid, //二级分类id
        news: 0,
        priceOrder: "",
        salesOrder: ""
      },
      show2: false,
      district: [],
      ready: false,
      title: title && cid ? title : "",
      loadTitle: "",
      loading: false,
      loadend: false,
      mapKey: cookie.get(MAPKEY),
      price: 0,
      stock: 0,
      nows: false,
      condition: 1,
      model2: "全国"
    };
  },
  watch: {
    title() {
      this.updateTitle();
    },
    $route(to) {
      if (to.name !== "GoodsList") return;
      const { s = "", sid = 0,cid=0, title = "" } = this.$route.query;

      if (s !== this.where.keyword || sid !== this.where.sid) {
        this.where.keyword = s;
        this.loadend = false;
        this.loading = false;
        this.where.page = 1;
        this.where.sid = sid;
        this.where.cid = cid;
        this.title = title && cid ? title : "";
        this.nows = false;
        this.condition = 0;
        this.$set(this, "productList", []);
        this.price = 0;
        this.stock = 0;
        this.get_product_list();
      }
    }
  },
  mounted: function() {
    this.updateTitle();
    this.loadCategoryData();
    if (cookie.get(LONGITUDE) && cookie.get(LATITUDE)) {
      this.get_product_list();
    } else {
      this.selfLocation();
    }
    this.getCityList();
    this.$scroll(this.$refs.container, () => {
      !this.loading && this.get_product_list();
    });
  },
  methods: {
   result2(ret) {
      this.model2 = ret.itemName3;
      this.where.city = ret.itemName2;
      this.where.district = ret.itemName3;
      this.$set(this, "productList", []);
      this.where.page = 1;
      this.loaded = false;
      this.loadend = false;
      this.get_product_list();
    },
   getCityList: function() {
      let that = this;
      getCity().then(res => {
        that.district = res.data;
        that.ready = true;
      });
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
    loadCategoryData() {
      getDetailCategory(this.where.sid).then(res => {
        this.category = res.data;
      });
    },
    asideTap(index) {
      let that = this;
      this.navActive = index;
      if(this.where.sid>0){
        this.where.cid=index;
      }else{
        this.where.sid=index;
      }
      that.$set(that, "category", []);
      this.loadCategoryData();
      that.$set(that, "productList", []);
      that.where.page = 1;
      that.loadend = false;
      
      that.get_product_list();
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
    updateTitle() {
      document.title = this.title || this.$route.meta.title;
    },
    get_product_list: debounce(function() {
      var that = this;
      if (that.loading) return; //阻止下次请求（false可以进行请求）；
      if (that.loadend) return; //阻止结束当前请求（false可以进行请求）；
      that.loading = true;
      this.setWhere();
      let q = that.where;
      getProducts(q).then(res => {
        that.loading = false;
        if(that.condition==1){
           that.productList.push.apply(that.productList, res.data.list);
           that.loadend = res.data.list.length < that.where.limit; //判断所有数据是否加载完成；
        }else{
           that.productList.push.apply(that.productList, res.data); 
           that.loadend = res.data.length < that.where.limit; //判断所有数据是否加载完成；
        }
        that.where.page = that.where.page + 1;
      });
    }, 300),
    submitForm: function() {
      this.$set(this, "productList", []);
      this.where.page = 1;
      this.loadend = false;
      this.loading = false;
      this.get_product_list();
    },
    //点击事件处理
    set_where: function(index) {
      let that = this;
      switch (index) {
        case 0:
          return that.$router.push({ path: "/tcategory" });
        case 1:
          that.condition = 1;
          break;
       case 2:
          that.condition = 3;
          that.show2=true;
          break;
        case 3:
          that.nows = !that.nows;
          break;
        case 4:
          that.condition = 1;
          break;
        case 5:
          that.condition = 2;
          break;
        case 6:
          that.condition = 3;
          break;  
        case 7:
          that.condition = 4;
          break;  
        default:
          break;
      }
      that.$set(that, "productList", []);
      that.where.page = 1;
      that.loadend = false;
      
      that.get_product_list();
    },
    //设置where条件
    setWhere: function() {
      let that = this;
      if (that.price === 0) {
        that.where.priceOrder = "";
      } else if (that.price === 1) {
        that.where.priceOrder = "asc";
      } else if (that.price === 2) {
        that.where.priceOrder = "desc";
      }
      if (that.stock === 0) {
        that.where.salesOrder = "";
      } else if (that.stock === 1) {
        that.where.salesOrder = "asc";
      } else if (that.stock === 2) {
        that.where.salesOrder = "desc";
      }
      that.where.latitude = cookie.get(LATITUDE) || "";
      that.where.longitude = cookie.get(LONGITUDE) || "";
      that.where.condition = that.condition;
      that.where.news = that.nows ? "1" : "0";
    },
    switchTap: function() {
      let that = this;
      that.Switch = !that.Switch;
    }
  }
};
</script>
<style scoped>
.samebox{width: 3.0rem;height: 0.6rem;line-height: 0.6rem;}
.samebox span{float: left; width: 0.9rem; color: #fff;  text-align: center; height: 0.4rem;line-height: 0.4rem; margin-top: 0.1rem; margin-right: 0.2rem;}
.samebox .on{border: 1px solid #fff;border-radius: 0.1rem;}
.noCommodity {
  border-top: 3px solid #f5f5f5;
  padding-bottom: 1px;
}
.aside {
    position: fixed;
    width: 100%;
    left: 0;
    height: 1rem;
    top: .86rem;
    bottom: 1rem;
    background-color: #fff;
    overflow-y: hidden;
    overflow-x: scroll;
    -webkit-overflow-scrolling: auto;
    overflow-scrolling: touch;
    white-space: nowrap;
    display: flex;
    z-index: 99;
    }
.aside .item {
    float: left;
    height: 1rem;
    line-height:1rem;
    font-size: .26rem;
    margin-right: 0.3rem;
    padding-left: 0.1rem;
}
.aside .on {
    text-align: center;
    color: #e93323;
    font-weight: 700;
    border-bottom: 1px solid #e93323;
    }
</style>
