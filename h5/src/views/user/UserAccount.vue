<template>
  <div class="my-account">
    <div class="wrapper">
      <div class="header">
        <div class="headerCon">
          <div class="account acea-row row-top row-between">
            <div class="assets">
              <div>可用余额(元)</div>
              <div class="money">{{ now_money }}</div>
            </div>
            <div style="width:1.5rem;">
	            <router-link :to="'/user/Recharge'" style="margin-bottom: 0.2rem;float:left;" class="recharge font-color-red"
	              >充值</router-link
	            >
	            <router-link :to="'/user/user_cash'" style="float:left;" class="recharge font-color-red"
	              >提现</router-link
	            >
            </div>
          </div>
          <div class="cumulative acea-row row-top">
            <div class="item">
              <div>累计充值(元)</div>
              <div class="money">{{ recharge }}</div>
            </div>
            <div class="item">
              <div>累计消费(元)</div>
              <div class="money">{{ orderStatusSum }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="nav acea-row row-middle">
        <router-link class="item" :to="'/user/bill/0'">
          <div class="pictrue"><img src="@assets/images/record1.png" /></div>
          <div>全部记录</div>
        </router-link>
        <router-link class="item" :to="'/user/bill/1'">
          <div class="pictrue"><img src="@assets/images/record2.png" /></div>
          <div>支出记录</div>
        </router-link>
        <router-link class="item" :to="'/user/bill/2'">
          <div class="pictrue"><img src="@assets/images/record3.png" /></div>
          <div>收入记录</div>
        </router-link>
      </div>
      
      <!--
      <div class="list">
        <div class="item acea-row row-between-wrapper">
          <div class="picTxt acea-row row-between-wrapper">
            <div class="iconfont icon-hebingxingzhuang"></div>
            <div class="text">
              <div class="line1">最新拼团活动</div>
              <div class="infor line1">最新的优惠商品上架拼团</div>
            </div>
          </div>
          <router-link
            class="bnt"
            :to="'/activity/group'"
            v-if="activity.is_pink"
            >立即参与</router-link
          >
          <div class="bnt end" v-else>已结束</div>
        </div>
        <div class="item acea-row row-between-wrapper">
          <div class="picTxt acea-row row-between-wrapper">
            <div class="iconfont icon-miaosha yellow"></div>
            <div class="text">
              <div class="line1">当前限时秒杀</div>
              <div class="infor line1">最新商品秒杀进行中</div>
            </div>
          </div>
          <router-link
            class="bnt"
            :to="'/activity/goods_seckill'"
            v-if="activity.is_seckill"
            >立即参与</router-link
          >
          <div class="bnt end" v-else>已结束</div>
        </div>
        <div class="item acea-row row-between-wrapper">
          <div class="picTxt acea-row row-between-wrapper">
            <div class="iconfont icon-kanjia1 green"></div>
            <div class="text">
              <div class="line1">砍价活动</div>
              <div class="infor line1">呼朋唤友来砍价</div>
            </div>
          </div>
          <router-link
            class="bnt"
            :to="'/activity/bargain'"
            v-if="activity.is_bargin"
            >立即参与</router-link
          >
          <div class="bnt end" v-else>已结束</div>
        </div>
      </div>
      -->
    </div>
    <Recommend></Recommend>
  </div>
</template>
<script>
import Recommend from "@components/Recommend";
import { getActivityStatus, getBalance } from "../../api/user";
export default {
  name: "UserAccount",
  components: {
    Recommend
  },
  props: {},
  data: function() {
    return {
      now_money: 0,
      orderStatusSum: 0,
      recharge: 0,
      activity: {
        is_bargin: false,
        is_pink: false,
        is_seckill: false
      }
    };
  },
  mounted: function() {
    this.getIndex();
    this.getActivity();
  },
  methods: {
    getIndex: function() {
      let that = this;
      getBalance().then(
        res => {
          that.now_money = res.data.now_money;
          that.orderStatusSum = res.data.orderStatusSum;
          that.recharge = res.data.recharge;
        },
        err => {
          that.$dialog.message(err.msg);
        }
      );
    },
    getActivity: function() {
      let that = this;
      getActivityStatus().then(
        res => {
          that.activity.is_bargin = res.data.is_bargin;
          that.activity.is_pink = res.data.is_pink;
          that.activity.is_seckill = res.data.is_seckill;
        },
        error => {
          that.$dialog.message(error.msg);
        }
      );
    }
  }
};
</script>
