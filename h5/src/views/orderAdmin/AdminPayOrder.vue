<template>
  <div class="order-index" ref="container">
    <div class="header acea-row"></div>
    <div class="wrapper" style="margin-top:-2.5rem;">
      <div class="title">
        <span class="iconfont icon-shujutongji"></span>数据统计({{ census.real_name }})
      </div>
      <div class="list acea-row">
        <router-link class="item" :to="'#'">
          <div class="num">{{ census.total_Price }}</div>
          <div>可退款金额</div>
        </router-link>
        <router-link class="item" :to="'#'">
          <div class="num">{{ census.total_count }}</div>
          <div>可退款笔数</div>
        </router-link>
        <router-link class="item" :to="'#'">
          <div class="num">{{ census.refund_Price }}</div>
          <div>已退款金额</div>
        </router-link>
      </div>
    </div>
    <div class="public-wrapper">
      <div class="title">
        <span class="iconfont icon-xiangxishuju"></span>交易详细数据
      </div>
      <div class="nav acea-row row-between-wrapper" >
        <div class="data">订单ID</div>
        <div class="browse">金额</div>
        <div class="turnover">操作</div>
      </div>
      <div class="conter" v-if="xlist.length > 0">
        <div
          class="item acea-row row-between-wrapper"
          v-for="(item, index) in xlist"
          :key="index"
        >
          <div class="data"><span class="timesytle">{{ item.order_id }}</span><span  class="timesytle">{{ item.time }}</span></div>
          <div class="browse">{{ item.total_amount }}</div>
          <div class="turnover" v-if="item.refund_status==0">
            <router-link :to="'/customer/refundorder/'+ item.id" style="color:#f00;">退款 </router-link>
          </div>
          <div class="turnover" v-else>已退款</div>
        </div>
      </div>
      <div class="noCommodity" style="padding-bottom: 0.3rem;" v-else>
          <div class="noPictrue">
	        <img src="@assets/images/noOrder.png" class="image" />
	      </div>
      </div>
    </div>
   
  </div>
</template>
<script>
import { getPayOrderStatistics, getMyPayOrderStatisticsMonth } from "../../api/admin";
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
        limit: 100
      },
      loaded: false,
      loading: false
    };
  },
  mounted: function() {
    this.check_id = this.$route.params.check_id;
    this.where.check_id = this.$route.params.check_id;
    this.getIndex();
    this.getxList();
  },
  methods: {
    getIndex: function() {
      var that = this;
      getPayOrderStatistics(that.check_id).then(res => {
          that.census = res.data;
        },
        err => {
          that.$dialog.message(err.msg);
        }
      );
    },
    getxList: function() {
      var that = this;
      getMyPayOrderStatisticsMonth(that.where).then(
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
<style scoped>
.timesytle{float:left;width:100%;}
.public-wrapper .browse {
    width: 0.92rem;
    text-align: right;
}
.public-wrapper .data {
    width: 3.1rem;
    text-align: left;
}
.public-wrapper .conter .item {
    border-bottom: 1px solid #f7f7f7;
    height: 1.0rem;
    font-size: .24rem;
}
</style>
