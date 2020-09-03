<template>
  <div class="order-index" ref="container">
    <div class="header acea-row"></div>
    <div class="wrapper" style="margin-top:-2.5rem;">
      <div class="title">
        <span class="iconfont icon-shujutongji"></span>数据统计({{ census.real_name }})
      </div>
      <div class="list acea-row">
        <router-link class="item" :to="'/customer/statistics/price/today'">
          <div class="num">{{ census.todayPrice }}</div>
          <div>今日成交额</div>
        </router-link>
        <router-link class="item" :to="'/customer/statistics/price/yesterday'">
          <div class="num">{{ census.proPrice }}</div>
          <div>昨日成交额</div>
        </router-link>
        <router-link class="item" :to="'/customer/statistics/price/month'">
          <div class="num">{{ census.monthPrice }}</div>
          <div>本月成交额</div>
        </router-link>
        <router-link class="item" :to="'/customer/statistics/order/today'">
          <div class="num">{{ census.todayCount }}</div>
          <div>今日订单数</div>
        </router-link>
        <router-link class="item" :to="'/customer/statistics/order/yesterday'">
          <div class="num">{{ census.proCount }}</div>
          <div>昨日订单数</div>
        </router-link>
        <router-link class="item" :to="'/customer/statistics/order/month'">
          <div class="num">{{ census.monthCount }}</div>
          <div>本月订单数</div>
        </router-link>
      </div>
    </div>
    <div class="public-wrapper">
      <div class="title">
        <span class="iconfont icon-xiangxishuju"></span>近30天扫码订单详细数据
      </div>
      <div class="nav acea-row row-between-wrapper" >
        <div class="data">日期</div>
        <div class="browse">订单数</div>
        <div class="turnover">成交额</div>
      </div>
      <div class="conter" v-if="xlist.length > 0">
        <div
          class="item acea-row row-between-wrapper"
          v-for="(item, index) in xlist"
          :key="index"
        >
          <div class="data">{{ item.time }}</div>
          <div class="browse">{{ item.count }}</div>
          <div class="turnover">{{ item.price }}</div>
        </div>
      </div>
      <div class="noCommodity" style="padding-bottom: 0.3rem;" v-else>
          <div class="noPictrue">
	        <img src="@assets/images/noOrder.png" class="image" />
	      </div>
      </div>
    </div>
    <div class="public-wrapper">
      <div class="title">
        <span class="iconfont icon-xiangxishuju"></span>近30天订单核销详细数据
      </div>
      <div class="nav acea-row row-between-wrapper">
        <div class="data">日期</div>
        <div class="browse">订单数</div>
        <div class="turnover">成交额</div>
      </div>
      <div class="conter" v-if="list.length > 0">
        <div
          class="item acea-row row-between-wrapper"
          v-for="(item, index) in list"
          :key="index"
        >
          <div class="data">{{ item.time }}</div>
          <div class="browse">{{ item.count }}</div>
          <div class="turnover">{{ item.price }}</div>
        </div>
      </div>
      <div class="noCommodity" style="padding-bottom: 0.3rem;" v-else>
          <div class="noPictrue">
	        <img src="@assets/images/noOrder.png" class="image" />
	      </div>
      </div>
    </div>
    <Loading :loaded="loaded" :loading="loading"></Loading>
  </div>
</template>
<script>
import { getMyOrderStatistics, getMyStatisticsMonth,getMyPayStatisticsMonth } from "../../api/admin";
import Loading from "@components/Loading";
export default {
  name: "OrderIndex",
  components: {
    Loading
  },
  props: {},
  data: function() {
    return {
      census: {},
      list: [],
      xlist: [],
      check_id:0,
      where: {
        check_id: 0,
        page: 1,
        limit: 30
      },
      loaded: false,
      loading: false
    };
  },
  mounted: function() {
    this.check_id = this.$route.params.check_id;
    this.where.check_id = this.$route.params.check_id;
    this.getIndex();
    this.getList();
    this.getxList();
  },
  methods: {
    getIndex: function() {
      var that = this;
      getMyOrderStatistics(that.check_id).then(res => {
          that.census = res.data;
        },
        err => {
          that.$dialog.message(err.msg);
        }
      );
    },
    getList: function() {
      var that = this;
      if (that.loading || that.loaded) return;
      that.loading = true;
      getMyStatisticsMonth(that.where).then(
        res => {
          that.loading = false;
          that.loaded = res.data.length < that.where.limit;
          that.list.push.apply(that.list, res.data);
          that.where.page = that.where.page + 1;
        },
        error => {
          that.$dialog.message(error.msg);
        },
        300
      );
    },
    getxList: function() {
      var that = this;
      getMyPayStatisticsMonth(that.where).then(
        res => {
          that.xlist.push.apply(that.xlist, res.data);
        },
        error => {
          that.$dialog.message(error.msg);
        },
        300
      );
    }
  }
};
</script>
