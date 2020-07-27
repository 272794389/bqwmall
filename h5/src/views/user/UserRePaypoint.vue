<template>
  <div class="my-account">
    <div class="wrapper">
      <div class="header">
        <div class="headerCon">
          <div class="account acea-row row-top row-between">
            <div class="assets">
              <div>重消积分余额</div>
              <div class="money">{{ now_money }}</div>
            </div>
          </div>
          <div class="cumulative acea-row row-top">
            <div class="item">
              <div>累计收入</div>
              <div class="money">{{ in_amount }}</div>
            </div>
            <div class="item">
              <div>累计支出</div>
              <div class="money">{{ out_amount }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="nav acea-row row-middle">
        <router-link class="item" :to="'/user/re_repay/0'">
          <div class="pictrue"><img src="@assets/images/record1.png" /></div>
          <div>全部记录</div>
        </router-link>
        <router-link class="item" :to="'/user/re_repay/1'">
          <div class="pictrue"><img src="@assets/images/record2.png" /></div>
          <div>支出记录</div>
        </router-link>
        <router-link class="item" :to="'/user/re_repay/2'">
          <div class="pictrue"><img src="@assets/images/record3.png" /></div>
          <div>收入记录</div>
        </router-link>
      </div>
    </div>
    <Recommend></Recommend>
  </div>
</template>
<script>
import Recommend from "@components/Recommend";
import { getActivityStatus, getBalance } from "../../api/user";
export default {
  name: "UserGive",
  components: {
    Recommend
  },
  props: {},
  data: function() {
    return {
      now_money: 0,
      out_amount: 0,
      in_amount: 0,
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
          that.now_money = res.data.repeat_point;
          that.in_amount = res.data.in_repoint;
          that.out_amount = res.data.out_repoint;
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
