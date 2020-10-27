<template>
  <div class="order-submission">
    <div
      class="allAddress"
      :style="store_self_mention ? '' : 'padding-top: 0.2rem'"
    >
      <div class="nav acea-row">
        <div
          class="item font-color-red"
          :class="shipping_type === 0 ? 'on' : 'on2'"
          @click="addressType(0)"
          v-if="store_self_mention"
        ></div>
        <div
          class="item font-color-red"
          :class="shipping_type === 1 ? 'on' : 'on2'"
          @click="addressType(1)"
          v-if="store_self_mention"
        ></div>
      </div>
      <div
        class="address acea-row row-between-wrapper"
        v-if="shipping_type === 0"
        @click="addressTap"
      >
        <div class="addressCon" v-if="addressInfo.real_name">
          <div class="name">
            {{ addressInfo.real_name }}
            <span class="phone">{{ addressInfo.phone }}</span>
          </div>
          <div>
            <span class="default font-color-red" v-if="addressInfo.is_default"
              >[榛樿]</span
            >
            {{ addressInfo.province }}{{ addressInfo.city
            }}{{ addressInfo.district }}{{ addressInfo.detail }}
          </div>
        </div>
        <div class="addressCon" v-else>
          <div class="setaddress">璁剧疆鏀惰揣鍦板潃</div>
        </div>
        <div class="iconfont icon-jiantou"></div>
      </div>
      <div
        class="address acea-row row-between-wrapper" v-else>
        <div class="addressCon" v-if="storeItem">
          <div class="name">
            {{ system_store.name }}
            <span class="phone" v-text="system_store.phone"></span>
          </div>
          <div
            v-text="system_store.address + ',' + system_store.detailed_address"
            v-if="system_store.address && system_store.detailed_address"
          ></div>
        </div>
      </div>
      <div class="line">
        <img src="@assets/images/line.jpg" />
      </div>
    </div>
    <OrderGoods :evaluate="0" :cartInfo="orderGroupInfo.cartInfo"></OrderGoods>
    <div class="wrapper">
    <!--
      <div
        class="item acea-row row-between-wrapper"
        @click="couponTap"
        v-if="deduction === false"
      >
        <div>浼樻儬鍒�</div>
        <div class="discount">
          {{ usableCoupon.coupon_title || "璇烽�夋嫨" }}
          <span class="iconfont icon-jiantou"></span>
        </div>
      </div>
      -->
      <div class="item acea-row row-between-wrapper">
        <div>浣跨敤绉垎鎶垫墸</div>
        <div class="discount">
          <div class="select-btn">
            <div class="checkbox-wrapper">
              <label class="well-check">
                <input type="checkbox" v-model="useIntegral" />
                <i class="icon"></i>
                <span class="integral">
                  褰撳墠鍙姷鎵�
                  <span class="num font-color-red">
                    锟{ orderPrice.total_price-orderPrice.pay_price-orderPrice.coupon_price || 0 }}
                  </span>
                </span>
              </label>
            </div>
          </div>
        </div>
      </div>
      <div class="item acea-row row-between-wrapper">
        <div>鎶垫墸鍒告姷鎵�</div>
        <div class="discount">
          <div class="select-btn">
            <div class="checkbox-wrapper">
              <label class="well-check">
                <input type="checkbox" v-model="useCoupon" />
                <i class="icon"></i>
                <span class="integral">
                  褰撳墠鍙姷鎵�
                  <span class="num font-color-red">
                                                      锟{orderPrice.coupon_price || 0 }}
                  </span>
                </span>
              </label>
            </div>
          </div>
        </div>
      </div>
      <div
        class="item acea-row row-between-wrapper"
        v-if="
          orderGroupInfo.priceGroup.vipPrice > 0 &&
            userInfo.vip &&
            pinkId == 0 &&
            orderGroupInfo.bargain_id == 0 &&
            orderGroupInfo.combination_id == 0 &&
            orderGroupInfo.seckill_id == 0
        "
      >
        浼氬憳浼樻儬
        <div class="discount">-锟{ orderGroupInfo.priceGroup.vipPrice }}</div>
      </div>
      <div class="item acea-row row-between-wrapper" v-if="shipping_type === 0">
        <div>蹇�掕垂鐢�</div>
        <div class="discount">
          {{
            orderPrice.pay_postage > 0
              ? "锟�" + orderPrice.pay_postage
              : "鍏嶈繍璐�"
          }}
        </div>
      </div>
      <div v-else>
        <div class="item acea-row row-between-wrapper">
          <div>鑱旂郴浜�</div>
          <div class="discount">
            <input
              type="text"
              placeholder="璇峰～鍐欐偍鐨勮仈绯诲鍚�"
              v-model="contacts" 
            />
          </div>
        </div>
        <div class="item acea-row row-between-wrapper">
          <div>鑱旂郴鐢佃瘽</div>
          <div class="discount">
            <input
              type="text"
              placeholder="璇峰～鍐欐偍鐨勮仈绯荤數璇�"
              v-model="contactsTel"
            />
          </div>
        </div>
      </div>
      <div class="item">
        <div>澶囨敞淇℃伅</div>
        <textarea
          placeholder="璇锋坊鍔犲娉紙150瀛椾互鍐咃級"
          v-model="mark"
        ></textarea>
      </div>
    </div>
    <div class="wrapper">
      <div class="item">
        <div>鏀粯鏂瑰紡</div>
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
              寰俊鏀粯
            </div>
            <div class="tip">寰俊蹇嵎鏀粯</div>
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
              寰俊鏀粯
            </div>
            <div class="tip">寰俊蹇嵎鏀粯</div>
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
              浣欓鏀粯
            </div>
            <div class="tip">鍙敤浣欓锛歿{ userInfo.now_money || 0 }}</div>
          </div>
          <div
            class="payItem acea-row row-middle"
            :class="active === 'offline' ? 'on' : ''"
            @click="payItem('offline')"
            v-if="
              offlinePayStatus === 1 &&
                deduction === false &&
                shipping_type === 0
            "
          >
            <div class="name acea-row row-center-wrapper">
              <div
                class="iconfont icon-yinhangqia"
                :class="active === 'offline' ? 'bounceIn' : ''"
              ></div>
              绾夸笅鏀粯
            </div>
            <div class="tip">绾夸笅鏂逛究鏀粯</div>
          </div>
        </div>
      </div>
    </div>
    <div class="moneyList">
      <div
        class="item acea-row row-between-wrapper"
        v-if="orderPrice.total_price !== undefined"
      >
        <div>鍟嗗搧鎬讳环锛�</div>
        <div class="money">锟{ orderPrice.total_price }}</div>
      </div>
      <div
        class="item acea-row row-between-wrapper"
        v-if="orderPrice.give_rate !== undefined &&orderPrice.give_rate>0"
      >
        <div>璐墿绉垎鎶垫墸锛�</div>
        <div class="money">锟{ orderPrice.give_rate }}</div>
      </div>
      <div
        class="item acea-row row-between-wrapper"
        v-if="orderPrice.pay_paypoint !== undefined &&orderPrice.pay_paypoint>0"
      >
        <div>娑堣垂绉垎鎶垫墸锛�</div>
        <div class="money">锟{ orderPrice.pay_paypoint }}</div>
      </div>
      <div
        class="item acea-row row-between-wrapper"
        v-if="orderPrice.pay_repeatpoint !== undefined &&orderPrice.pay_repeatpoint>0"
      >
        <div>閲嶆秷绉垎鎶垫墸锛�</div>
        <div class="money">锟{ orderPrice.pay_repeatpoint }}</div>
      </div>
      <div
        class="item acea-row row-between-wrapper"
        v-if="orderPrice.give_point !== undefined &&orderPrice.give_point>0"
      >
        <div>璧犻�佽喘鐗╃Н鍒嗭細</div>
        <div class="money">锟{ orderPrice.give_point }}</div>
      </div>
      <div
        class="item acea-row row-between-wrapper"
        v-if="orderPrice.pay_point !== undefined &&orderPrice.pay_point>0"
      >
        <div>璧犻�佹秷璐圭Н鍒嗭細</div>
        <div class="money">锟{ orderPrice.pay_point }}</div>
      </div>
      <div
        class="item acea-row row-between-wrapper"
        v-if="orderPrice.pay_postage > 0"
      >
        <div>杩愯垂锛�</div>
        <div class="money">+锟{ orderPrice.pay_postage }}</div>
      </div>
      <div
        class="item acea-row row-between-wrapper"
        v-if="orderPrice.coupon_price > 0"
      >
        <div>浼樻儬鍒告姷鎵ｏ細</div>
        <div class="money">-锟{ orderPrice.coupon_price }}</div>
      </div>
      <div
        class="item acea-row row-between-wrapper"
        v-if="orderPrice.deduction_price > 0"
      >
        <div>绉垎鎶垫墸锛�</div>
        <div class="money">-锟{ orderPrice.deduction_price }}</div>
      </div>
    </div>

    <div style="height:1.2rem"></div>
    <div class="footer acea-row row-between-wrapper">
      <div>
        鍚堣:
        <span class="font-color-red">锟{ orderPrice.pay_price }}</span>
      </div>
      <div class="settlement" @click="createOrder">绔嬪嵆缁撶畻</div>
    </div>
    <CouponListWindow
      v-on:couponchange="changecoupon($event)"
      v-model="showCoupon"
      :price="orderPrice.total_price"
      :checked="usableCoupon.id"
      :cartid="cartid"
      @checked="changeCoupon"
    ></CouponListWindow>
    <AddressWindow
      @checked="changeAddress"
      @redirect="addressRedirect"
      v-model="showAddress"
      :checked="addressInfo.id"
      ref="mychild"
    ></AddressWindow>
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
.order-submission .allAddress .nav {
  width: 7.1rem;
  margin: 0 auto;
}
.order-submission .allAddress .nav .item {
  width: 3.55rem;
}
.order-submission .allAddress .nav .item.on {
  position: relative;
  width: 2.5rem;
}
.order-submission .allAddress .nav .item.on:before {
  position: absolute;
  bottom: 0;
  content: "蹇�掗厤閫�";
  font-size: 0.28rem;
  display: block;
  height: 0;
  width: 3.55rem;
  border-width: 0 0.2rem 0.8rem 0;
  border-style: none solid solid;
  border-color: transparent transparent #fff;
  z-index: 9;
  border-radius: 0.07rem 0.3rem 0 0;
  text-align: center;
  line-height: 0.8rem;
}
.order-submission .allAddress .nav .item:nth-of-type(2).on:before {
  content: "鍒板簵鑷彁";
  border-width: 0 0 0.8rem 0.2rem;
  border-radius: 0.3rem 0.07rem 0 0;
}
.order-submission .allAddress .nav .item.on2 {
  position: relative;
}
.order-submission .allAddress .nav .item.on2:before {
  position: absolute;
  bottom: 0;
  content: "鍒板簵鑷彁";
  font-size: 0.28rem;
  display: block;
  height: 0;
  width: 4.6rem;
  border-width: 0 0 0.6rem 0.6rem;
  border-style: none solid solid;
  border-color: transparent transparent #f7c1bd;
  border-radius: 0.4rem 0.06rem 0 0;
  text-align: center;
  line-height: 0.6rem;
}
.order-submission .allAddress .nav .item:nth-of-type(1).on2:before {
  content: "蹇�掗厤閫�";
  border-width: 0 0.6rem 0.6rem 0;
  border-radius: 0.06rem 0.4rem 0 0;
}
.order-submission .allAddress .address {
  width: 7.1rem;
  height: 1.5rem;
  margin: 0 auto;
}
.order-submission .allAddress .line {
  width: 7.1rem;
  margin: 0 auto;
}
.order-submission .wrapper .item .discount input::placeholder {
  color: #ccc;
}
</style>
<script>
import OrderGoods from "@components/OrderGoods";
import CouponListWindow from "@components/CouponListWindow";
import AddressWindow from "@components/AddressWindow";
import { postOrderConfirm, postOrderComputed, createOrder } from "@api/order";
import { storeListApi } from "@api/store";
import { getUser } from "@api/user";
import { pay } from "@libs/wechat";
import { isWeixin } from "@utils";
import { mapGetters } from "vuex";
import cookie from "@utils/store/cookie";
const NAME = "OrderSubmission",
  _isWeixin = isWeixin();
const LONGITUDE = "user_longitude";
const LATITUDE = "user_latitude";
export default {
  name: NAME,
  components: {
    OrderGoods,
    CouponListWindow,
    AddressWindow
  },
  props: {},
  computed: {
    ...mapGetters(["storeItems"]),
    storeItem: function() {
      if (JSON.stringify(this.storeItems) == "{}") {
        return this.storeList;
      } else {
        return this.storeItems;
      }
    }
  },
  data: function() {
    return {
      cartid: "",
      offlinePayStatus: 2,
      from: _isWeixin ? "weixin" : "weixinh5",
      deduction: true,
      isWeixin: _isWeixin,
      pinkId: 0,
      active: _isWeixin ? "weixin" : "yue",
      showCoupon: false,
      showAddress: false,
      addressInfo: {},
      couponId: 0,
      orderGroupInfo: {
        priceGroup: {}
      },
      usableCoupon: {},
      addressLoaded: false,
      useIntegral: true,
      useCoupon: true,
      orderPrice: {
        pay_price: "璁＄畻涓�"
      },
      mark: "",
      system_store: {},
      shipping_type: 0,
      contacts: "",
      contactsTel: "",
      store_self_mention: 0,
      userInfo: {},
      storeList: {}
    };
  },
  watch: {
    useIntegral() {
      this.computedPrice();
    },
    useCoupon() {
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
    that.getUserInfo();
    that.getCartInfo();
    that.getList();
    if (that.$route.query.pinkid !== undefined)
      that.pinkId = that.$route.query.pinkid;
    if (that.$route.params.id !== undefined)
      that.cartid = that.$route.params.id;
  },
  methods: {
    // 鑾峰彇闂ㄥ簵鍒楄〃鏁版嵁
    getList: function() {
      let data = {
        latitude: cookie.get(LATITUDE) || "", //绾害
        longitude: cookie.get(LONGITUDE) || "", //缁忓害
        page: 1,
        limit: 10
      };
      storeListApi(data)
        .then(res => {
          this.storeList = res.data.list[0];
        })
        .catch(err => {
          this.$dialog.error(err.msg);
        });
    },
    // 璺宠浆鍒伴棬搴楀垪琛�
    showStoreList() {
      this.$store.commit("GET_TO", "orders");
      this.$router.push("/shop/storeList/orders");
    },
    getUserInfo() {
      getUser()
        .then(res => {
          this.userInfo = res.data;
          console.log(this.userInfo);
          this.contacts = this.userInfo.real_name;
          this.contactsTel =  this.userInfo.phone;
        })
        .catch(() => {});
    },
   
    addressType: function(index) {
      if (index && !this.system_store.id)
        return this.$dialog.error("鏆傛棤闂ㄥ簵淇℃伅锛屾偍鏃犳硶閫夋嫨鍒板簵鑷彁锛�");
      if (this.isHex == 1 && index!=1)
        return this.$dialog.error("璇ュ晢鍝佸彧鑳藉埌搴楄嚜鎻愶紒");
        this.shipping_type = index;
    },
    computedPrice() {
      let shipping_type = this.shipping_type;
      postOrderComputed(this.orderGroupInfo.orderKey, {
        addressId: this.addressInfo.id,
        useIntegral: this.useIntegral ? 1 : 0,
        useCoupon: this.useCoupon ? 1 : 0,
        couponId: this.usableCoupon.id || 0,
        shipping_type: parseInt(shipping_type) + 1,
        payType: this.active,
        cartIds: this.$route.params.id
      })
        .then(res => {
          const data = res.data;
          if (data.status === "EXTEND_ORDER") {
            this.$router.replace({
              path: "/order/detail/" + data.result.orderId
            });
          } else {
            this.orderPrice = data.result;
            this.orderGroupInfo.orderKey = data.result.key;
          }
        })
        .catch(res => {
          this.$dialog.error(res.msg);
        });
    },
    getCartInfo() {
      const cartIds = this.$route.params.id;
      if (!cartIds) {
        this.$dialog.error("鍙傛暟鏈夎");
        return this.$router.go(-1);
      }
      postOrderConfirm(cartIds)
        .then(res => {
          this.offlinePayStatus = res.data.offline_pay_status;
          this.orderGroupInfo = res.data;
          this.deduction = res.data.deduction;
          this.usableCoupon = res.data.usableCoupon || {};
          this.addressInfo = res.data.addressInfo || {};
          this.system_store = res.data.system_store || {};
          this.store_self_mention = res.data.store_self_mention;
          this.isHex = res.data.isHex;
          if (res.data.isHex == 1){
            this.shipping_type =1;
          }
          this.computedPrice();
        })
        .catch(() => {
          this.$dialog.error("鍔犺浇璁㈠崟鏁版嵁澶辫触");
        });
    },
    addressTap: function() {
      this.showAddress = true;
      if (!this.addressLoaded) {
        this.addressLoaded = true;
        this.$refs.mychild.getAddressList();
      }
    },
    addressRedirect() {
      this.addressLoaded = false;
      this.showAddress = false;
    },
    couponTap: function() {
      this.showCoupon = true;
    },
    changeCoupon: function(coupon) {
      if (!coupon) {
        this.usableCoupon = { coupon_title: "涓嶄娇鐢ㄤ紭鎯犲埜", id: 0 };
      } else {
        this.usableCoupon = coupon;
      }
      this.computedPrice();
    },
    payItem: function(index) {
      this.active = index;
      this.computedPrice();
    },
    changeAddress(addressInfo) {
      this.addressInfo = addressInfo;
      this.computedPrice();
    },
    createOrder() {
      let shipping_type = this.shipping_type;
      if (!this.active) return this.$dialog.toast({ mes: "璇烽�夋嫨鏀粯鏂瑰紡" });
      if (!this.addressInfo.id && !this.shipping_type)
        return this.$dialog.toast({ mes: "璇烽�夋嫨鏀惰揣鍦板潃" });
      if (this.shipping_type) {
        if (
          (this.contacts === "" || this.contactsTel === "") &&
          this.shipping_type
        )
          return this.$dialog.toast({ mes: "璇峰～鍐欒仈绯讳汉鎴栬仈绯讳汉鐢佃瘽" });
        if (!/^1(3|4|5|7|8|9|6)\d{9}$/.test(this.contactsTel)) {
          return this.$dialog.toast({ mes: "璇峰～鍐欐纭殑鎵嬫満鍙�" });
        }
        if (!/^[\u4e00-\u9fa5\w]{2,16}$/.test(this.contacts)) {
          return this.$dialog.toast({ mes: "璇峰～鍐欐偍鐨勭湡瀹炲鍚�" });
        }
      }
      this.$dialog.loading.open("鐢熸垚璁㈠崟涓�");
      createOrder(this.orderGroupInfo.orderKey, {
        real_name: this.contacts,
        phone: this.contactsTel,
        addressId: this.addressInfo.id,
        useIntegral: this.useIntegral ? 1 : 0,
        useCoupon: this.useCoupon ? 1 : 0,
        couponId: this.usableCoupon.id || 0,
        payType: this.active,
        pinkId: this.pinkId,
        seckill_id: this.orderGroupInfo.seckill_id,
        combinationId: this.orderGroupInfo.combination_id,
        bargainId: this.orderGroupInfo.bargain_id,
        from: this.from,
        mark: this.mark || "",
        shipping_type: parseInt(shipping_type) + 1,
        store_id: this.storeItem ? this.storeItem.id : 0
      })
        .then(res => {
          this.$dialog.loading.close();
          const data = res.data;
          let url = "/order/status/" + data.result.orderId;
          switch (data.status) {
            case "ORDER_EXIST":
            case "EXTEND_ORDER":
            case "PAY_DEFICIENCY":
            case "PAY_ERROR":
              this.$dialog.toast({ mes: res.msg });
              this.$router.replace({
                path: url + "/0?msg=" + res.msg
              });
              break;
            case "SUCCESS":
              this.$dialog.success(res.msg);
              this.$router.replace({
                path: url + "/1"
              });
              break;
            case "WECHAT_H5_PAY":
              this.$router.replace({
                path: url + "/2"
              });
              setTimeout(() => {
                location.href = data.result.jsConfig.mweb_url;
              }, 100);
              break;
            case "WECHAT_PAY":
              pay(data.result.jsConfig).finally(() => {
                this.$router.replace({
                  path: url + "/4"
                });
              });
          }
        })
        .catch(err => {
          console.log(err);
          this.$dialog.error(err.msg || "鍒涘缓璁㈠崟澶辫触");
          this.$dialog.loading.close();
          this.$router.go(-1);
        });
    }
  }
};
</script>
