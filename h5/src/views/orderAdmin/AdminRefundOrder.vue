<template>
  <div>
    <div class="payment-top acea-row row-column row-center-wrapper">
      <span class="name">当前可退款</span>
      <div class="pic">
        ￥<span class="pic-font">{{ census.pay_amount || 0 }}</span>
      </div>
    </div>
    <div class="recharge">
      <div class="info-wrapper">
        <div>
          <div class="money">
            <span>￥</span>
            <input type="number" disabled="disabled" placeholder="0.00" v-model="money" />
          </div>
          <div class="tip-box">
            <span class="tip">提示：</span>
            <div class="tip-samll">
                                     用户昵称：
              <span class="font-color"
                >{{ census.nickname }}</span
              >
            </div>
            <div class="tip-samll">
                                     订单号：
              <span class="font-color"
                >{{ census.order_id }}</span
              >
            </div>
          </div>
        </div>
        <div class="pay-btn bg-color-red" @click="recharge">
          {{ "立即退款" }}
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { mapGetters } from "vuex";
import { pay } from "@libs/wechat";
import { isWeixin } from "@utils";
import { refundWechat, getRechargeApi } from "@api/user";
import { add, sub } from "@utils/bc";
import { getOrderDetail} from "../../api/admin";
export default {
  name: "AdminPayOrderDetail",
  props: {},
  data: function() {
    return {
      from: isWeixin() ? "weixin" : "weixinh5",
      money: "",
      order_id:-1,
      census: {},
      now_money: "",
      picList: [],
      activePic: 0,
      numberPic: "",
      paid_price: "",
      huokuan:0,
      rechargeAttention: []
    };
  },
  computed: mapGetters(["userInfo"]),
  mounted: function() {
    this.order_id = this.$route.params.order_id;
    this.getOrderDetail();
  },
  methods: {
  
    //获取订单基本信息
    getOrderDetail: function() {
      var that = this;
      getOrderDetail(that.order_id).then(res => {
          that.census = res.data;
          that.money = res.data.pay_amount;
        },
        err => {
          that.$dialog.message(err.msg);
        }
      );
    },
    recharge: function() {
        let that = this,
        price = Number(this.money);
        if (price === 0) {
          return that.$dialog.toast({ mes: "请输入您要退还的金额" });
        } else if (price < 0.01) {
          return that.$dialog.toast({ mes: "退还金额不能低于0.01" });
        }
        this.$dialog.confirm({
          mes: "退还金额将原路退回客户账户",
          title: "款项退回",
          opts: [
            {
              txt: "确认",
              color: false,
              callback: () => {
                refundWechat({ price: price, order_id: that.order_id})
                  .then(res => {
                    that.$dialog.toast({ mes: res.msg });
                    that.$router.go(-1);
                  })
                  .catch(res => {
                    that.$dialog.toast({ mes: res.msg });
                    that.$router.go(-1);
                  });
              }
            },
            {
              txt: "取消",
              color: false,
              callback: () => {
                return that.$dialog.toast({ mes: "已取消" });
              }
            }
          ]
        });
    }
  }
};
</script>
<style scoped>
#iframe {
  display: none;
}
.pic-box-color-active {
  background-color: #ec3323 !important;
  color: #fff !important;
}
.pic-box-active {
  width: 2.16rem;
  height: 1.2rem;
  background-color: #ec3323;
  border-radius: 0.2rem;
}
.picList {
  margin-bottom: 0.3rem;
  margin-top: 0.3rem;
}
.font-color {
  color: #e83323;
}
.recharge {
  border-radius: 0.1rem;
  width: 100%;
  background-color: #fff;
  margin: 0.2rem auto 0 auto;
  padding: 0.3rem;
  border-top-right-radius: 0.39rem;
  border-top-left-radius: 0.39rem;
  margin-top: -0.45rem;
  box-sizing: border-box;
}
.recharge .nav {
  height: 0.75rem;
  line-height: 0.75rem;
  padding: 0 1rem;
}
.recharge .nav .item {
  font-size: 0.3rem;
  color: #333;
}
.recharge .nav .item.on {
  font-weight: bold;
  border-bottom: 0.04rem solid #e83323;
}
.recharge .info-wrapper {
}
.recharge .info-wrapper .money {
  margin-top: 0.6rem;
  padding-bottom: 0.2rem;
  border-bottom: 1px dashed #ddd;
  text-align: center;
}
.recharge .info-wrapper .money span {
  font-size: 0.56rem;
  color: #333;
  font-weight: bold;
}
.recharge .info-wrapper .money input {
  display: inline-block;
  width: 3rem;
  font-size: 0.84rem;
  text-align: center;
  color: #282828;
  font-weight: bold;
  padding-right: 0.7rem;
}
.recharge .info-wrapper .money input::placeholder {
  color: #ddd;
}
.recharge .info-wrapper .money input::-webkit-input-placeholder {
  color: #ddd;
}
.recharge .info-wrapper .money input:-moz-placeholder {
  color: #ddd;
}
.recharge .info-wrapper .money input::-moz-placeholder {
  color: #ddd;
}
.recharge .info-wrapper .money input:-ms-input-placeholder {
  color: #ddd;
}
.tip {
  font-size: 0.28rem;
  color: #333333;
  font-weight: 800;
  margin-bottom: 0.14rem;
}
.tip-samll {
  font-size: 0.24rem;
  color: #333333;
  margin-bottom: 0.14rem;
}
.tip-box {
  margin-top: 0.3rem;
}
.recharge .info-wrapper .tips span {
  color: #ef4a49;
}
.recharge .info-wrapper .pay-btn {
  display: block;
  width: 100%;
  height: 0.86rem;
  margin: 0.5rem auto 0 auto;
  line-height: 0.86rem;
  text-align: center;
  color: #fff;
  border-radius: 0.5rem;
  font-size: 0.3rem;
  font-weight: bold;
}
.payment-top {
  width: 100%;
  height: 3.5rem;
  background-color: #e83323;
}
.payment-top .name {
  font-size: 0.26rem;
  color: rgba(255, 255, 255, 0.8);
  margin-top: -0.38rem;
  margin-bottom: 0.3rem;
}
.payment-top .pic {
  font-size: 0.32rem;
  color: #fff;
}
.payment-top .pic-font {
  font-size: 0.78rem;
  color: #fff;
}
.picList .pic-box {
  width: 32%;
  height: auto;
  border-radius: 0.2rem;
  margin-top: 0.21rem;
  padding: 0.2rem 0;
}
.pic-box-color {
  background-color: #f4f4f4;
  color: #656565;
}
.pic-number {
  font-size: 0.22rem;
}
.pic-number-pic {
  font-size: 0.38rem;
  margin-right: 0.1rem;
  text-align: center;
}
.pic-box-money {
  width: 100%;
  display: block;
}
</style>
