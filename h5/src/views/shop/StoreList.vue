<template>
  <div>
    <div class="storeBox productList" ref="container" style="margin-top:2.6rem;">
     <form @submit.prevent="submitForm">
      <div class="search bg-color-red acea-row row-between-wrapper">
        <div class="samebox""><span @click="set_where(0)" class="on">选择分类</span></div>
        <div class="input acea-row row-between-wrapper"  style="width: 4.4rem;">
          <span class="iconfont icon-sousuo"></span>
          <input placeholder="搜索商品信息" v-model="where.keyword"  style="width: 3.48rem;"/>
        </div>
      </div>
    </form>
    <div class="aside">
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
    <!--
    <div class="nav acea-row row-middle">
      <div class="condition" @click="set_where(4)" :class="condition==1 ? 'font-color-red' : ''">
                 全城
      </div>
      <div class="condition" @click="set_where(5)" :class="condition==2 ? 'font-color-red' : ''">
        1km
      </div>
      <div class="condition" @click="set_where(6)" :class="condition==3 ? 'font-color-red' : ''">
        5km
      </div>
      <div  class="condition" @click="set_where(7)" :class="condition==4 ? 'font-color-red' : ''">
        10km
      </div>
      <div  class="condition" @click="set_where(8)" :class="condition==5 ? 'font-color-red' : ''">
        20km
      </div>
    </div>
    <div class="nav acea-row row-middle" style="margin-top:0.82rem;">
      <div
        class="item"
        :class="title ? 'font-color-red' : ''"
        @click="set_where(0)"
      >
        {{ title ? title : "默认" }}
      </div>
      <div class="item" @click="set_where(2)">
                  消费笔数
        <img src="@assets/images/horn.png" v-if="stock === 0" />
        <img src="@assets/images/up.png" v-if="stock === 1" />
        <img src="@assets/images/down.png" v-if="stock === 2" />
      </div>
    </div>
    -->
    <!--
      <div
        class="storeBox-box"
        v-for="(item, index) in storeList"
        :key="index"
        @click="goDetail(item)"
      >
      
        <div class="store-img"><img :src="item.image" lazy-load="true" /></div>
        <div class="store-cent-left" style="width:5">
          <div class="store-name">{{ item.name }}</div>
          <Reta :size="48" :score="4.5"></Reta>
          <div class="store-address line1">
                               已消费{{ item.sales }}笔
          </div>
         
          <span style="color:#1495E7;margin-left:0.2rem;">营业：{{ item.day_time }}</span>
          <div class="store-address line1">
            {{item.detailed_address }}
          </div>
        </div>
        <div class="row-right">
          <div>
            <a class="store-phone" :href="'tel:' + item.phone"
              ><span class="iconfont icon-dadianhua01"></span
            ></a>
          </div>
          <div class="store-distance" @click.stop="showMaoLocation(item)">
            <span class="addressTxt" v-if="item.range"
              >距{{ item.range }}KM</span
            >
            <span class="addressTxt" v-else>查看地图</span>
            <span class="iconfont icon-youjian"></span>
          </div>
        </div>
        
	 </div>
	 -->
	  <div class="wrapper" v-if="storeList.length>0" style="margin-top:-0.6rem;">
        <div class="goodList">
		    <div class="item acea-row row-between-wrapper shangjia" @click="goDetail(item)" v-for="(item, index) in storeList" :key="index">
		      <div class="pictrue" style="width:2.0rem;">
		         <img :src="item.image" class="image">
		      </div>
		      <div class="shop_box" style="height:2.2rem">
		        <div class="text">
		          <div class="pline2" style="margin-bottom:0.1rem;">{{ item.name }}</div>
		          <!--<Reta :size="48" :score="4.5"></Reta>-->
		          <div class="shoptip"><span class="cate_style">{{ item.cate_name }}</span><span  class="cate_style">{{ item.range }}km</span></div>
		          <!--
		          <div class="shoptip gui" style="margin-bottom:0.1rem;margin-top:-0.1rem;">{{ item.cate_name }}<span style="float: right;margin-right: 0.2rem;">{{ item.range }}km</span></div>
		          -->
		          <div class="shoptip shopaddress ktime" style="margin-bottom:0.1rem;">营业：{{ item.termDate }}&nbsp;{{ item.day_time }}</div>
		          <div class="shoptip shopaddress addressUlr">{{item.detailed_address }}</div>
		        </div>
		      </div>
		    </div>
		</div>
        
      </div>
      <Loading :loaded="loaded" :loading="loading"></Loading>
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
import Loading from "@components/Loading";
import { storeListApi,getDetailCategory } from "@api/store";
import { isWeixin } from "@utils/index";
import Reta from "@components/Star";
import { wechatEvevt, wxShowLocation } from "@libs/wechat";
import { mapGetters } from "vuex";
import cookie from "@utils/store/cookie";
const LONGITUDE = "user_longitude";
const LATITUDE = "user_latitude";
const MAPKEY = "mapKey";
export default {
  name: "storeList",
  components: { Loading ,Reta},
  computed: mapGetters(["goName"]),
  data() {
   const { s = "", sid = 0,cid=0, title = "" } = this.$route.query;
    return {
      where: {
        page: 1,
        limit: 8,
        latitude:"",
        longitude:"",
        keyword: s,
        sid: sid, //一级分类id
        cid: cid, //二级分类id
        salesOrder: ""
      },
      title: title && cid ? title : "",
      loaded: false,
      stock: 0,
      loading: false,
      storeList: [],
      category: [],
      navActive: cid,
      mapShow: false,
      system_store: {},
      mapKey: cookie.get(MAPKEY),
      locationShow: false,
      condition: 1
    };
  },
  watch: {
    $route(to) {
      if (to.name !== "storeList") return;
      const { s = "", sid = 0,cid=0, title = "" } = this.$route.query;

      if (s !== this.where.keyword || cid !== this.where.cid) {
        this.where.keyword = s;
        this.loaded = false;
        this.loading = false;
        this.where.page = 1;
        this.where.sid = id;
        this.title = title && cid ? title : "";
        this.condition = 1;
        this.$set(this, "storeList", []);
        this.getList();
      }
    }
  },
  mounted() {
     this.loadCategoryData();
    if (cookie.get(LONGITUDE) && cookie.get(LATITUDE)) {
      this.getList();
    } else {
      this.selfLocation();
    }
    this.$scroll(this.$refs.container, () => {
      !this.loading && this.getList();
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
        that.$set(that, "category", []);
        this.loadCategoryData();
      }
      that.$set(that, "storeList", []);
      that.where.page = 1;
      that.loaded = false;
      that.getList();
    },
    goDetail(item) {
        this.$router.push({ path: "/sdetail/" + item.id });
    },
    // 选中门店
    checked(e) {
      if (this.goName === "orders") {
        this.$router.go(-1); //返回上一层
        this.$store.commit("GET_STORE", e);
      }
    },
    // 获取门店列表数据
    getList: function() {
      if (this.loading || this.loaded) return;
      this.loading = true;
      this.setWhere();
      let q = this.where;
      /*
      let data = {
        latitude: cookie.get(LATITUDE) || "", //纬度
        longitude: cookie.get(LONGITUDE) || "", //经度
        page: this.page,
        limit: this.limit
      };*/
      storeListApi(q).then(res => {
          this.loading = false;
          this.loaded = res.data.list.length < this.where.limit;
          this.storeList.push.apply(this.storeList, res.data.list);
          this.where.page = this.where.page + 1;
        }).catch(err => {
          this.$dialog.error(err.msg);
        });
    },
    
    submitForm: function() {
      this.$set(this, "storeList", []);
      this.where.page = 1;
      this.loaded = false;
      this.loading = false;
      this.getList();
    },
    //点击事件处理
    set_where: function(index) {
      let that = this;
      switch (index) {
        case 0:
          return that.$router.push({ path: "/spcategory" });
        case 2:
          if (that.stock === 0) that.stock = 1;
          else if (that.stock === 1) that.stock = 2;
          else if (that.stock === 2) that.stock = 0;
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
        case 8:
          that.condition = 5;
          break;
        default:
          break;
      }
      that.$set(that, "storeList", []);
      that.where.page = 1;
      that.loaded = false;
      that.getList();
    },
    //设置where条件
    setWhere: function() {
      let that = this;
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
    },
  }
};
</script>

<style scoped>
.samebox{width: 2rem;height: 0.6rem;line-height: 0.6rem;}
.samebox span{float: left; width: 1.6rem; color: #fff;  text-align: center; height: 0.4rem;line-height: 0.4rem; margin-top: 0.1rem; margin-right: 0.2rem;}
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
}
.storeBox-box {
  width: 100%;
  height: auto;
  margin-top:-0.6rem;
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
  width: 50%;
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
  text-overflow: ellipsis;display: -webkit-box;-webkit-box-orient: vertical;overflow: hidden;-webkit-line-clamp: 1;
}
.store-address {
  color: #666666;
  font-size: 0.24rem;
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
.cate_style{color: #f00; margin-right: 0.3rem;line-height: 0.5rem;}
</style>
