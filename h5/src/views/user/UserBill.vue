<template>
  <div class="bill-details" ref="container">
    <div class="nav acea-row">
      <div class="item" :class="types == 0 ? 'on' : ''" @click="changeTypes(0)">
        全部
      </div>
      <div class="item" :class="types == 1 ? 'on' : ''" @click="changeTypes(1)">
       支出
      </div>
      <div class="item" :class="types == 2 ? 'on' : ''" @click="changeTypes(2)">
        收入
      </div>
    </div>
    <div class="sign-record">
      <div class="list">
        <div class="item" v-for="(item, index) in list" :key="index">
          <div class="data">{{ item.time }}&nbsp;&nbsp;当前余额：{{item.yue}}&nbsp;&nbsp;余额变化：{{item.yuebianhua}}</div>
          <div class="listn" v-for="(val, key) in item.list" :key="key">
            <div class="itemn acea-row row-between-wrapper">
              <div>
                <div class="name line1">{{ val.mark }}<span style="color:#f00;" v-if="val.use_money>0">(-{{ val.fee}}费用,+{{ val.repeat_point}}重消)</span></div>
                <div>{{ val.add_time }} </div>
              </div>
              <div class="num" :class="val.use_money < 0 ? 'font-color-red' : ''">
                {{ val.use_money > 0 ? "+" : "" }}{{ val.use_money }}&nbsp;&nbsp;余额：{{val.yue}}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <Loading :loaded="loaded" :loading="loading"></Loading>
  </div>
</template>
<script>
import {getPayLog } from "../../api/user";
import Loading from "@components/Loading";
export default {
  name: "UserBill",
  components: {
    Loading
  },
  props: {},
  data: function() {
    return {
      types: "",
      where: {
        page: 1,
        limit: 5
      },
      list: [],
      loaded: false,
      loading: false
    };
  },
  watch: {
    "$route.params.types": function(newVal) {
      let that = this;
      if (newVal != undefined) {
        that.types = newVal;
        that.list = [];
        that.where.page = 1;
        that.loaded = false;
        that.loading = false;
        that.getIndex();
      }
    },
    types: function() {
      this.getIndex();
    }
  },
  mounted: function() {
    let that = this;
    that.types = that.$route.params.types;
    that.getIndex();
    that.$scroll(that.$refs.container, () => {
      !that.loading && that.getIndex();
    });
  },
  methods: {
    code: function() {
      this.sendCode();
    },
    changeTypes: function(val) {
      if (val != this.types) {
        this.types = val;
        this.list = [];
        this.where.page = 1;
        this.loaded = false;
        this.loading = false;
      }
    },
    getIndex: function() {
      let that = this;
      if (that.loaded == true || that.loading == true) return;
      that.loading = true;
      getPayLog(that.where, that.types).then(
        res => {
          let temp = parseFloat(res.data[0].yue);
          let last = 0;
          that.loading = false;
          that.loaded = res.data.length < that.where.limit;
          that.where.page = that.where.page + 1;
          that.list.push.apply(that.list, res.data);
          that.list.forEach((item, index) => {
            item.list.forEach((item1,index1)=>{
              console.log(item1,index1);
              
              if(index1>0)
              {
                temp -= parseFloat(last);
                item1.yue = temp.toFixed(2);
                last = parseFloat(item1.use_money);
                last = last.toFixed(2);
              }
              else
              {
                item1.yue = parseFloat(temp);
                last = parseFloat(item1.use_money);
                last = last.toFixed(2);
              }

            });
          });

          

        },
        error => {
          that.$dialog.message(error.msg);
        }
      );
    }
  }
};
</script>
