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
          <div class="data">{{ item.time }}&nbsp;&nbsp;当前消费积分：{{item.dangqianpaypoint}}&nbsp;&nbsp;消费积分变化：{{item.paypointbianhua}}</div>
          <div class="listn" v-for="(val, key) in item.list" :key="key">
            <div class="itemn acea-row row-between-wrapper">
              <div>
                <div class="name line1">{{ val.mark }}</div>
                <div>{{ val.add_time }}</div>
              </div>
              <div class="num" :class="val.pay_point < 0 ? 'font-color-red' : ''">
                {{ val.pay_point > 0 ? "+" : "" }}{{ val.pay_point }}&nbsp;积分：{{val.dangqianpaypoint}}
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
import {getPayPointLog } from "../../api/user";
import Loading from "@components/Loading";
export default {
  name: "UserPayPointRecord",
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
      getPayPointLog(that.where, that.types).then(
        res => {
          that.loading = false;
          that.loaded = res.data.length < that.where.limit;
          that.where.page = that.where.page + 1;
          that.list.push.apply(that.list, res.data);
          //
          let tempyue = 0;
          let temparr = [];
          let last = 0;
          let temp = parseFloat(res.data[0].dangqianpaypoint);
          that.list.forEach((item, index) => {
            item.list.forEach((item1,index1)=>{
              console.log(item1,index1);
              
              if(index1>0)
              {
                temp -= parseFloat(last);
                item1.dangqianpaypoint = temp.toFixed(2);
                last = parseFloat(item1.pay_point);
                last = last.toFixed(2);
              }
              else
              {
                 if(tempyue>0)
                {
                  item1.dangqianpaypoint = parseFloat(temparr.dangqianpaypoint - temparr.pay_point).toFixed(2);
                  last = parseFloat(item1.pay_point);
                  last = last.toFixed(2);
                  temp -=  temparr.pay_point;

                }
                else
                {
                  item1.dangqianpaypoint = parseFloat(temp);
                  last = parseFloat(item1.pay_point);
                  last = last.toFixed(2);
                }
              }

              tempyue = tempyue + 1 ;
              temparr = item1;

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
