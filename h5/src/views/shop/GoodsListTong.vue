<template>
  <div class="productList" ref="container">
    <form @submit.prevent="submitForm">
      <div class="search bg-color-red acea-row row-between-wrapper">
        <div class="input acea-row row-between-wrapper">
          <span class="iconfont icon-sousuo"></span>
          <input placeholder="搜索商品信息" v-model="where.keyword" />
        </div>
        <div
          class="iconfont"
          :class="Switch === true ? 'icon-pailie' : 'icon-tupianpailie'"
          @click="switchTap"
        ></div>
      </div>
    </form>
    <div class="nav acea-row row-middle">
      <div
        class="item"
        :class="title ? 'font-color-red' : ''"
        @click="set_where(0)"
      >
        {{ title ? title : "默认" }}
      </div>
      <div class="item" @click="set_where(1)">
        价格
        <img src="@assets/images/horn.png" v-if="price === 0" />
        <img src="@assets/images/up.png" v-if="price === 1" />
        <img src="@assets/images/down.png" v-if="price === 2" />
      </div>
      <div class="item" @click="set_where(2)">
        销量
        <img src="@assets/images/horn.png" v-if="stock === 0" />
        <img src="@assets/images/up.png" v-if="stock === 1" />
        <img src="@assets/images/down.png" v-if="stock === 2" />
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
               <span class="shou">已售{{ item.sales }}{{ item.unit_name }}</span>
          </div>
          <!--
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
          -->
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
import Recommend from "@components/Recommend";
import { isWeixin } from "@utils/index";
import { wechatEvevt, wxShowLocation } from "@libs/wechat";
import debounce from "lodash.debounce";
import Loading from "@components/Loading";
import {goodListApi } from "@api/public";
import cookie from "@utils/store/cookie";
const LONGITUDE = "user_longitude";
const LATITUDE = "user_latitude";
const MAPKEY = "mapKey";
export default {
  name: "GoodsListTong",
  components: {
    Recommend,
    Loading
  },
  props: {},
  data: function() {
    const { s = "", id = 0, title = "" } = this.$route.query;

    return {
      hostProduct: [],
      productList: [],
      Switch: true,
      where: {
        page: 1,
        belong_t:2,
        latitude:"",
        longitude:"",
        limit: 8,
        keyword: s,
        sid: id, //二级分类id
        news: 0,
        priceOrder: "",
        salesOrder: ""
      },
      title: title && id ? title : "",
      loadTitle: "",
      loading: false,
      loadend: false,
      mapKey: cookie.get(MAPKEY),
      price: 0,
      stock: 0,
      nows: false,
      condition: 0
    };
  },
  watch: {
    title() {
      this.updateTitle();
    },
    $route(to) {
      if (to.name !== "GoodsList") return;
      const { s = "", id = 0, title = "" } = to.query;

      if (s !== this.where.keyword || id !== this.where.sid) {
        this.where.keyword = s;
        this.loadend = false;
        this.loading = false;
        this.where.page = 1;
        this.where.sid = id;
        this.title = title && id ? title : "";
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
    
    if (cookie.get(LONGITUDE) && cookie.get(LATITUDE)) {
      this.get_product_list();
    } else {
      this.selfLocation();
    }
    this.$scroll(this.$refs.container, () => {
      !this.loading && this.get_product_list();
    });
  },
  methods: {
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
      goodListApi(q).then(res => {
        that.loading = false;
        that.productList.push.apply(that.productList, res.data.list);
        that.loadend = res.data.length < that.where.limit; //判断所有数据是否加载完成；
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
          if (that.price === 0) that.price = 1;
          else if (that.price === 1) that.price = 2;
          else if (that.price === 2) that.price = 0;
          that.stock = 0;
          break;
        case 2:
          if (that.stock === 0) that.stock = 1;
          else if (that.stock === 1) that.stock = 2;
          else if (that.stock === 2) that.stock = 0;
          that.price = 0;
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
.noCommodity {
  border-top: 3px solid #f5f5f5;
  padding-bottom: 1px;
}
</style>
