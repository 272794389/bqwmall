<template>
  <div class="order-submission">
    <div class="wrapper">
      <div class="item">
        <div>支付方式</div>
        <div class="list">
          <div
            class="payItem acea-row row-middle"
            :class="active === 'weixin' ? 'on' : ''"
            @click="payItem('weixin')"
            v-show="isWeixin"
          >
            <div class="name acea-row row-center-wrapper">
              <div
                class="iconfont icon-weixin2"
                :class="active === 'weixin' ? 'bounceIn' : ''"
              ></div>
              微信支付
            </div>
            <div class="tip">微信快捷支付</div>
          </div>
          <div
            class="payItem acea-row row-middle"
            :class="active === 'weixin' ? 'on' : ''"
            @click="payItem('weixin')"
            v-show="!isWeixin"
          >
            <div class="name acea-row row-center-wrapper">
              <div
                class="iconfont icon-weixin2"
                :class="active === 'weixin' ? 'bounceIn' : ''"
              ></div>
              微信支付
            </div>
            <div class="tip">微信快捷支付</div>
          </div>
          <div
            class="payItem acea-row row-middle"
            :class="active === 'yue' ? 'on' : ''"
            @click="payItem('yue')"
          >
            <div class="name acea-row row-center-wrapper">
              <div
                class="iconfont icon-icon-test"
                :class="active === 'yue' ? 'bounceIn' : ''"
              ></div>
              余额支付
            </div>
            <div class="tip">可用余额：{{ userInfo.now_money || 0 }}</div>
          </div>
        </div>
      </div>
    </div>
    <div class="moneyList">
      <div  class="item acea-row row-between-wrapper">
        <div>消费总额：</div>
        <div class="money">￥{{ orderPrice.total_amount}}</div>
      </div>
      
      <div class="item acea-row row-between-wrapper" v-if="orderPrice.pay_give>0">
        <div>购物积分抵扣</div>
        <div class="discount">
          <div class="select-btn">
            <div class="checkbox-wrapper">
              <label class="well-check">
                <input type="checkbox" v-model="useIntegral" />
                <i class="icon" style="right: 0;left: unset;"></i>
                <span class="integral" style="margin-right:0.4rem;">
                  当前可抵扣
                  <span class="num font-color-red">
                    ￥{{ orderPrice.pay_give || 0 }}
                  </span>
                </span>
              </label>
            </div>
          </div>
        </div>
      </div>
      <div class="item acea-row row-between-wrapper" v-if="orderPrice.pay_point>0">
        <div>消费积分抵扣</div>
        <div class="discount">
          <div class="select-btn">
            <div class="checkbox-wrapper">
              <label class="well-check">
                <input type="checkbox" v-model="usePayIntegral" />
                <i class="icon" style="right: 0;left: unset;"></i>
                <span class="integral" style="margin-right:0.4rem;">
                  当前可抵扣
                  <span class="num font-color-red">
                    ￥{{ orderPrice.pay_point || 0 }}
                  </span>
                </span>
              </label>
            </div>
          </div>
        </div>
      </div>
      <div class="item acea-row row-between-wrapper" v-if="orderPrice.coupon_amount>0">
        <div>抵扣券抵扣</div>
        <div class="discount">
          <div class="select-btn">
            <div class="checkbox-wrapper">
              <label class="well-check">
                <input type="checkbox" v-model="useCoupon" />
                <i class="icon" style="right: 0;left: unset;"></i>
                <span class="integral" style="margin-right:0.4rem;">
                  当前可抵扣
                  <span class="num font-color-red">
                                                      ￥{{orderPrice.coupon_amount || 0 }}
                  </span>
                </span>
              </label>
            </div>
          </div>
        </div>
      </div>
      <div class="item acea-row row-between-wrapper" >
        <div>赠送消费积分：</div>
        <div class="money">{{ orderPrice.pay_pointer }}</div>
      </div>
    </div>
    <div style="height:1.2rem"></div>
    <div class="footer acea-row row-between-wrapper">
      <div>
        合计:
        <span class="font-color-red">￥{{ orderPrice.pay_amount }}</span>
      </div>
      <div class="settlement" @click="payOrder" :disabled="isDisable">立即支付</div>
    </div>
  </div>
</template>
<style scoped>
.order-submission .wrapper .shipping select {
  color: #999;
  padding-right: 0.15rem;
}
.order-submission .wrapper .shipping .iconfont {
  font-size: 0.3rem;
  color: #515151;
}
.order-submission .allAddress {
  width: 100%;
  background-image: linear-gradient(to bottom, #e93323 0%, #f5f5f5 100%);
  background-image: -webkit-linear-gradient(
    to bottom,
    #e93323 0%,
    #f5f5f5 100%
  );
  background-image: -moz-linear-gradient(to bottom, #e93323 0%, #f5f5f5 100%);
  padding-top: 1rem;
}
.order-submission .wrapper .item .discount input::placeholder {
  color: #ccc;
}
</style>
<script>
import { getOrder,payOrder,postPayOrderComputed } from "@api/store";
import { getUser } from "@api/user";
import { pay } from "@libs/wechat";
import { isWeixin } from "@utils";
const NAME = "OrderPaySubmission",
  _isWeixin = isWeixin();
export default {
  name: NAME,
  data: function() {
    return {
      offlinePayStatus: 2,
      from: _isWeixin ? "weixin" : "weixinh5",
      deduction: true,
      useIntegral: false,
      useCoupon: false,
      usePayIntegral:false,
      isWeixin: _isWeixin,
      active: _isWeixin ? "weixin" : "yue",
      isDisable: false,
      orderPrice: {
        pay_price: "计算中"
      },
      id: 0,
      userInfo: {}
    };
  },
  watch: {
    $route(n) {
      if (n.name === NAME) {
        this.id = n.params.id;
        this.getUserInfo();
      }
    },
    useIntegral() {
     if(this.useIntegral==true){
        this.useCoupon=false;
        this.usePayIntegral=false;
      }
      this.computedPrice();
    },
    useCoupon() {
      if(this.useCoupon==true){
        this.useIntegral=false;
        this.usePayIntegral=false;
      }
      this.computedPrice();
    },
    usePayIntegral() {
      if(this.usePayIntegral==true){
        this.useIntegral=false;
        this.useCoupon=false;
      }
      this.computedPrice();
    },
    $route(n) {
      if (n.name === NAME) {
        this.getUserInfo();
        this.getCartInfo();
      }
    },
    shipping_type() {
      this.computedPrice();
    }
  },
  mounted: function() {
    let that = this;
    this.id = this.$route.params.id;
    that.getUserInfo();
    this.getOrderInfo();
  },
  methods: {
    getUserInfo() {
      getUser()
        .then(res => {
          this.userInfo = res.data;
        })
        .catch(() => {});
    },
   getOrderInfo(){
     let that = this;
     getOrder(that.id).then(res =>{
       that.$set(that, "orderPrice", res.data.orderinfo);
     }).catch(res => {
          that.$dialog.error(res.msg);
          that.$router.go(-1);
        });
   },
   computedPrice() {
      let that = this;
      postPayOrderComputed({
        orderid: this.id ,
        useIntegral: this.useIntegral ? 1 : 0,
        useCoupon: this.useCoupon ? 1 : 0,
        usePayIntegral: this.usePayIntegral ? 1 : 0
      }).then(res => {
          that.$set(that, "orderPrice", res.data.orderinfo);
        }).catch(res => {
          this.$dialog.error(res.msg);
        });
    },
    payItem: function(index) {
      this.active = index;
    },
    
    disabel:function(){
    //点击按钮之后禁用按钮
    this.isDisable = true
    //处理
    ......
    //
    setTimeout(() => {
        this.isDisable = false
    }, 2000)
    },
    
    payOrder() {
      if (!this.active) return this.$dialog.toast({ mes: "请选择支付方式" });
      this.isDisable = true;
     
      this.$dialog.loading.open("订单提交中");
      this.submitDisabled = true;
      payOrder({
        payType: this.active,
        from: this.from,
        order_id:this.id
      }).then(res => {
          this.$dialog.loading.close();
          const data = res.data;
          let url = "/user";
          switch (data.status) {
            case "ORDER_EXIST":
            case "EXTEND_ORDER":
            case "PAY_ERROR":
              this.$dialog.toast({ mes: res.msg });
              break;
            case "PAY_DEFICIENCY":
              this.$dialog.toast({ mes: '余额不足' });
              break;
            case "SUCCESS":
              this.$dialog.success(res.msg);
              setTimeout(() => {
                 this.$router.replace({
	                path: url
	              });
              }, 100);
             
              break;
            case "WECHAT_H5_PAY":
              this.$router.replace({
                path: url
              });
              setTimeout(() => {
                location.href = data.result.jsConfig.mweb_url;
              }, 100);
              break;
            case "WECHAT_PAY":
              pay(data.result.jsConfig).finally(() => {
                this.$router.replace({
                  path:url
                });
              });
          }
        }).catch(err => {
          console.log(err);
          this.$dialog.loading.close();
          this.$dialog.error(err.msg || "创建订单失败");
        });
    }
  }
};
</script>
