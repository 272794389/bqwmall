<template>
  <div class="productList" ref="container">
    <form @submit.prevent="submitForm">
      <div class="search bg-color-red acea-row row-between-wrapper">
        <div class="samebox""><span @click="set_where(0)" class="on">选择分类</span></div>
        <div class="input acea-row row-between-wrapper"  style="width: 4.4rem;">
          <span class="iconfont icon-sousuo"></span>
          <input placeholder="搜索商品信息" v-model="where.keyword"  style="width: 3.48rem;"/>
        </div>
        <div
          class="iconfont"
          :class="Switch === true ? 'icon-pailie' : 'icon-tupianpailie'"
          @click="switchTap"
        ></div>
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
    <div
      class="list acea-row row-between-wrapper"
      :class="Switch === true ? '' : 'on'"
      ref="container"
      style="margin-top:1.86rem;"
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
  </div>
</template>

<script>
import Recommend from "@components/Recommend";
import { getGoodsProducts,getDetailCategory } from "@api/store";
import debounce from "lodash.debounce";
import Loading from "@components/Loading";

export default {
  name: "GoodsList",
  components: {
    Recommend,
    Loading
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
        limit: 8,
        keyword: s,
        sid: sid, //一级分类id
        cid: cid, //二级分类id
        news: 0,
        priceOrder: "",
        salesOrder: ""
      },
      title: title && cid ? title : "",
      loadTitle: "",
      loading: false,
      loadend: false,
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
    this.get_product_list();
    this.$scroll(this.$refs.container, () => {
      !this.loading && this.get_product_list();
    });
  },
  methods: {
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
      getGoodsProducts(q).then(res => {
        that.loading = false;
        that.productList.push.apply(that.productList, res.data);
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
          return that.$router.push({ path: "/gcategory" });
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

</style>
