<template>
  <div class="promoter-list" ref="container">
    <div class="header">
      <div class="promoterHeader bg-color-red">
        <div class="headerCon acea-row row-between-wrapper">
          <div>
            <div class="name">服务商户数</div>
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
      <div v-if="spreadList.length > 0">
        <div
          class="item acea-row row-between-wrapper"
          v-for="(val, index) in spreadList"
          :key="index"
        >
          <div class="picTxt acea-row row-between-wrapper">
            <div class="pictrue"><img :src="val.image" /></div>
            <div class="text">
              <div class="name line1">{{ val.mer_name }}</div>
              <div>任务周期: {{ val.date }}</div>
            </div>
          </div>
          <div class="right">
            <div>
              {{ val.ucnt ? val.ucnt : 0 }}/<span class="font-color-red">{{ val.rucnt ? val.rucnt : 0 }}</span> 人
            </div>
            <div>{{ val.ocnt ? val.ocnt : 0 }}/<span class="font-color-red">{{ val.rocnt ? val.rocnt : 0 }} </span>单</div>
            <div>{{ val.orderAmount}} 元</div>
          </div>
        </div>
      </div>
      <div v-else style="width: 100%;line-height: 1rem;text-align: center;">暂无数据</div>
    </div>
    <Loading :loaded="loaded" :loading="loading"></Loading>
  </div>
</template>
<script>
import { getMySpreadShop } from "../../../api/user";
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
      getMySpreadShop(screen).then(
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
