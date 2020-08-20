<template>
  <div class="promoter-list" ref="container">
    <div class="header">
      <div class="promoterHeader bg-color-red">
        <div class="headerCon acea-row row-between-wrapper">
          <div>
            <div class="name">推广商户数</div>
            <div>
              <span class="num">{{ first}}</span>户
            </div>
          </div>
          <div class="iconfont icon-tuandui"></div>
        </div>
      </div>
      
      <div class="search acea-row row-between-wrapper">
        <form @submit.prevent="submitForm">
          <div class="input">
            <input placeholder="点击商家名称" v-model="screen.keyword" />
            <span class="iconfont icon-guanbi"></span>
          </div>
        </form>
        <div class="iconfont icon-sousuo2"></div>
      </div>
    </div>
    <div class="list">
      <div
        class="sortNav acea-row row-middle"
        :class="fixedState === true ? 'on' : ''"
      >
        <div class="sortItem" @click="sort('numberCount')">
          金额排序
          <img src="@assets/images/sort1.png" v-if="numberCount == 1" />
          <img src="@assets/images/sort2.png" v-if="numberCount == 2" />
          <img src="@assets/images/sort3.png" v-if="numberCount == 3" />
        </div>
        <div class="sortItem" @click="sort('orderCount')">
          订单排序
          <img src="@assets/images/sort1.png" v-if="orderCount == 1" />
          <img src="@assets/images/sort2.png" v-if="orderCount == 2" />
          <img src="@assets/images/sort3.png" v-if="orderCount == 3" />
        </div>
      </div>
      <div :class="fixedState === true ? 'sortList' : ''">
        <div
          class="item acea-row row-between-wrapper"
          v-for="(val, index) in spreadList"
          :key="index"
        >
          <div class="picTxt acea-row row-between-wrapper">
            <div class="pictrue"><img :src="val.image" /></div>
            <div class="text">
              <div class="name line1">{{ val.mer_name }}</div>
              <div>加入时间: {{ val.time }}</div>
            </div>
          </div>
          <div class="right">
            <div>{{ val.orderCount }} 单</div>
            <div>{{ val.numberCount ? val.numberCount : 0 }} 元</div>
          </div>
        </div>
      </div>
    </div>
    <Loading :loaded="loaded" :loading="loading"></Loading>
  </div>
</template>
<script>
import { getSpreadShop } from "../../../api/user";
import Loading from "@components/Loading";
export default {
  name: "PromoterList",
  components: {
    Loading
  },
  props: {},
  data: function() {
    return {
      fixedState: false,
      screen: {
        page: 1,
        limit: 15,
        grade: 0,
        keyword: "",
        sort: ""
      },
      childCount: 2,
      numberCount: 2,
      orderCount: 2,
      loaded: false,
      loading: false,
      spreadList: [],
      loadTitle: "",
      first: 0,
      second: ""
    };
  },
  mounted: function() {
    this.getSpreadUsers();
    this.$scroll(this.$refs.container, () => {
      !this.loading && this.getSpreadUsers();
    });
  },
  watch: {
    "screen.sort": function() {
      this.screen.page = 0;
      this.loaded = false;
      this.loading = false;
      this.spreadList = [];
      this.getSpreadUsers();
    }
  },
  methods: {
    handleScroll: function() {
      var scrollTop =
        document.documentElement.scrollTop || document.body.scrollTop;
      var offsetTop = document.querySelector(".header").clientHeight;
      if (scrollTop >= offsetTop) {
        this.fixedState = true;
      } else {
        this.fixedState = false;
      }
    },
    submitForm: function() {
      this.screen.page = 0;
      this.loaded = false;
      this.loading = false;
      this.spreadList = [];
      this.getSpreadUsers();
    },
    getSpreadUsers: function() {
      let that = this,
        screen = that.screen;
      if (that.loaded || that.loading) return;
      that.loading = true;
      getSpreadShop(screen).then(
        res => {
          that.loading = false;
          that.spreadList.push.apply(that.spreadList, res.data.list);
          that.loaded = res.data.list.length < that.screen.limit; //判断所有数据是否加载完成；
          that.loadTitle = that.loaded ? "人家是有底线的" : "上拉加载更多";
          that.screen.page = that.screen.page + 1;
          that.first = res.data.total;
        },
        error => {
          that.$dialog.message(error.msg);
        },
        300
      );
    },
    sort: function(types) {
      let that = this;
      switch (types) {
        case "childCount":
          if (that.childCount == 2) {
            that.childCount = 1;
            that.orderCount = 2;
            that.numberCount = 2;
            that.screen.sort = "childCount DESC";
          } else if (that.childCount == 1) {
            that.childCount = 3;
            that.orderCount = 2;
            that.numberCount = 2;
            that.screen.sort = "childCount ASC";
          } else if (that.childCount == 3) {
            that.childCount = 2;
            that.orderCount = 2;
            that.numberCount = 2;
            that.screen.sort = "";
          }
          break;
        case "numberCount":
          if (that.numberCount == 2) {
            that.numberCount = 1;
            that.orderCount = 2;
            that.childCount = 2;
            that.screen.sort = "numberCount DESC";
          } else if (that.numberCount == 1) {
            that.numberCount = 3;
            that.orderCount = 2;
            that.childCount = 2;
            that.screen.sort = "numberCount ASC";
          } else if (that.numberCount == 3) {
            that.numberCount = 2;
            that.orderCount = 2;
            that.childCount = 2;
            that.screen.sort = "";
          }
          break;
        case "orderCount":
          if (that.orderCount == 2) {
            that.orderCount = 1;
            that.numberCount = 2;
            that.childCount = 2;
            that.screen.sort = "orderCount DESC";
          } else if (that.orderCount == 1) {
            that.orderCount = 3;
            that.numberCount = 2;
            that.childCount = 2;
            that.screen.sort = "orderCount ASC";
          } else if (that.orderCount == 3) {
            that.orderCount = 2;
            that.numberCount = 2;
            that.childCount = 2;
            that.screen.sort = "";
          }
          break;
        default:
          that.screen.sort = "";
      }
    }
  }
};
</script>
