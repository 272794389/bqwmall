<template>
  <div class="paybox">
    <div class="pay_box">
       <div class="shop_name">商家：{{storeInfo.name}}</div>
       <div class="shop_amount"><span style="font-size:0.3rem; color:#999">￥</span><input type="text" placeholder="" v-model="amount" /></div>
       <div class="pay_btn" @click="confirm" type="text">立即付款</div>
    </div>
</template>
<script>
import { swiper, swiperSlide } from "vue-awesome-swiper";
import "@assets/css/swiper.min.css";
import ProductConSwiper from "@components/ProductConSwiper";
import UserEvaluation from "@components/UserEvaluation";
import ShareRedPackets from "@components/ShareRedPackets";
import CouponPop from "@components/CouponPop";
import ProductWindow from "@components/ProductWindow";
import StorePoster from "@components/StorePoster";
import ShareInfo from "@components/ShareInfo";
import debounce from "lodash.debounce";
import {
  getProductDetail,
  postCartAdd,
  getCartCount,
  getProductCode,
  storeListApi
} from "@api/store";
import {
  getCoupon,
  getCollectAdd,
  getCollectDel,
  getUserInfo
} from "@api/user";
import { isWeixin } from "@utils/index";
import { wechatEvevt } from "@libs/wechat";
import { imageBase64 } from "@api/public";
import { mapGetters } from "vuex";
import cookie from "@utils/store/cookie";
let NAME = "GoodsCon";
const LONGITUDE = "user_longitude";
const LATITUDE = "user_latitude";
export default {
  name: NAME,
  components: {
    swiper,
    swiperSlide,
    ProductConSwiper,
    UserEvaluation,
    ShareRedPackets,
    CouponPop,
    ProductWindow,
    StorePoster,
    ShareInfo
  },
  data: function() {
    return {
      shareInfoStatus: false,
      weixinStatus: false,
      mapShow: false,
      mapKey: "",
      posterData: {
        image: "",
        title: "",
        price: "",
        code: ""
      },
      posterImageStatus: false,
      animated: false,
      coupon: {
        coupon: false,
        list: []
      },
      attr: {
        cartAttr: false,
        productAttr: [],
        productSelect: {}
      },
      isOpen: false, //是否打开属性组件
      productValue: [],
      id: 0,
      storeInfo: {},
      couponList: [],
      attrTxt: "请选择",
      attrValue: "",
      cart_num: 1, //购买数量
      replyCount: "",
      replyChance: "",
      reply: [],
      priceName: 0,
      CartCount: 0,
      posters: false,
      banner: [{}, {}],
      swiperRecommend: {
        pagination: {
          el: ".swiper-pagination",
          clickable: true
        },
        autoplay: false,
        loop: false,
        speed: 1000,
        observer: true,
        observeParents: true
      },
      goodList: [],
      system_store: {},
      storeSelfMention: true,
      storeItems: {},
      activity: [],
      navList: [],
      lock: false,
      navActive: 0,
      opacity: 0
    };
  },
  computed: mapGetters(["isLogin"]),
  watch: {
    $route(n) {
      if (n.name === NAME) {
        this.id = n.params.id;
        this.storeInfo.slider_image = [];
        this.productCon();
      }
    }
  },
  updated() {
    // window.scroll(0, 0);
  },
  mounted: function() {
    document.addEventListener("scroll", this.onScroll, false);
    this.id = this.$route.params.id;
    this.storeInfo.slider_image = [];
    this.productCon();
    this.coupons();
    window.addEventListener("scroll", this.handleScroll);
    this.getList();
  },
  methods: {
    // 商品详情跳转
    goDetail(item) {
      if (item.type === "1") {
        this.$router.push({
          path: "/activity/seckill_detail/" + item.id + "/" + item.time + "/1"
        });
      } else if (item.type === "2") {
        this.$router.push({
          path: "/activity/dargain_detail/" + item.id
        });
      } else {
        this.$router.push({
          path: "/activity/group_detail/" + item.id
        });
      }
    },
    // 获取门店列表数据
    getList() {
      let data = {
        latitude: cookie.get(LATITUDE) || "", //纬度
        longitude: cookie.get(LONGITUDE) || "", //经度
        page: 1,
        limit: 10
      };
      storeListApi(data)
        .then(res => {
          this.storeItems = res.data.list[0];
        })
        .catch(err => {
          console.log(err);
        });
    },
    handleScroll() {
      let top = document.body.scrollTop || document.documentElement.scrollTop;
      let opacity = top / 350;
      opacity = opacity > 1 ? 1 : opacity;
      this.opacity = opacity;
    },
    asideTap(a) {
      this.$nextTick(() => {
        let index = a;
        this.navActive = index;
        if (!this.goodList.length && index === 2) {
          index = 3;
        }
        let element = document.querySelector("#title" + index);
        const top =
          element.offsetTop - this.$refs.header.offsetHeight - window.scrollY;
        this.lock = true;
        window.scrollBy({ top, left: 0, behavior: "smooth" });
      });
    },
    onScroll: debounce(function() {
      if (this.lock) {
        return;
      }
      const headerHeight = this.$refs.header.offsetHeight,
        { scrollY } = window,
        titles = [];
      titles.push(document.querySelector("#title0"));
      titles.push(document.querySelector("#title1"));
      if (this.goodList.length) {
        titles.push(document.querySelector("#title2"));
      }
      titles.push(document.querySelector("#title3"));
      titles.reduce((initial, title, index) => {
        if (initial) return initial;
        if (scrollY + headerHeight < title.offsetTop + title.offsetHeight) {
          initial = true;
          this.navActive = index;
        }
        return initial;
      }, false);
      this.lock = true;
    }, 500),
    showChang: function() {
      if (isWeixin()) {
        let config = {
          latitude: parseFloat(this.storeItems.latitude),
          longitude: parseFloat(this.storeItems.longitude),
          name: this.storeItems.name,
          address: this.storeItems.address + this.system_store.detailed_address
        };
        wechatEvevt("openLocation", config)
          .then(res => {
            console.log(res);
          })
          .catch(res => {
            if (res.is_ready) {
              res.wx.openLocation(config);
            }
          });
      } else {
        if (!this.mapKey)
          return this.$dialog.error(
            "暂无法使用查看地图，请配置您的腾讯地图key"
          );
        this.mapShow = true;
      }
    },
    updateTitle() {
      document.title = this.storeInfo.store_name || this.$route.meta.title;
    },
    // 调转到门店列表
    showStoreList() {
      this.$store.commit("GET_TO", "details");
      this.$router.push("/shop/storeList/details");
    },
    setOpenShare: function() {
      var data = this.storeInfo;
      var href = location.href;
      if (isWeixin()) {
        if (this.isLogin) {
          getUserInfo().then(res => {
            href =
              href.indexOf("?") === -1
                ? href + "?spread=" + res.data.uid
                : href + "&spread=" + res.data.uid;
            var configAppMessage = {
              desc: data.store_info,
              title: data.store_name,
              link: href,
              imgUrl: data.image
            };
            wechatEvevt(
              ["updateAppMessageShareData", "updateTimelineShareData"],
              configAppMessage
            )
              .then(res => {
                console.log(res);
              })
              .catch(res => {
                if (res.is_ready) {
                  res.wx.updateAppMessageShareData(configAppMessage);
                  res.wx.updateTimelineShareData(configAppMessage);
                }
              });
          });
        } else {
          var configAppMessage = {
            desc: data.store_info,
            title: data.store_name,
            link: href,
            imgUrl: data.image
          };
          wechatEvevt(
            ["updateAppMessageShareData", "updateTimelineShareData"],
            configAppMessage
          )
            .then(res => {
              console.log(res);
            })
            .catch(res => {
              if (res.is_ready) {
                res.wx.updateAppMessageShareData(configAppMessage);
                res.wx.updateTimelineShareData(configAppMessage);
              }
            });
        }
      }
    },
    setShareInfoStatus: function() {
      this.shareInfoStatus = !this.shareInfoStatus;
      this.posters = false;
    },
    shareCode: function(value) {
      var that = this;
      getProductCode(that.id).then(res => {
        if (res.data.code) that.posterData.code = res.data.code;
        value === false && that.listenerActionSheet();
      });
    },
    setPosterImageStatus: function() {
      var sTop = document.body || document.documentElement;
      sTop.scrollTop = 0;
      this.posterImageStatus = !this.posterImageStatus;
      this.posters = false;
    },
    //产品详情接口；
    productCon: function() {
      let that = this;
      getProductDetail(that.id)
        .then(res => {
          that.$set(that, "storeInfo", res.data.storeInfo);
          that.$set(that.attr, "productAttr", res.data.productAttr);
          that.$set(that, "productValue", res.data.productValue);
          that.$set(that, "replyCount", res.data.replyCount);
          that.$set(that, "replyChance", res.data.replyChance);
          that.reply = res.data.reply ? [res.data.reply] : [];
          that.$set(that, "reply", that.reply);
          that.$set(that, "priceName", res.data.priceName);
          that.posterData.image = that.storeInfo.image_base;
          that.activity = res.data.activity ? res.data.activity : [];
          if (that.storeInfo.store_name.length > 30) {
            that.posterData.title =
              that.storeInfo.store_name.substring(0, 30) + "...";
          } else {
            that.posterData.title = that.storeInfo.store_name;
          }
          that.storeSelfMention = res.data.store_self_mention ? true : false;
          that.posterData.price = that.storeInfo.price;
          that.posterData.code = that.storeInfo.code_base;
          that.system_store = res.data.system_store;
          let good_list = res.data.good_list || [];
          let goodArray = [];
          let count = Math.ceil(good_list.length / 6);
          for (let i = 0; i < count; i++) {
            var list = good_list.slice(i * 6, i * 6 + 6);
            if (list.length) goodArray.push({ list: list });
          }
          that.mapKey = res.data.mapKey;
          that.$set(that, "goodList", goodArray);
          let navList = ["商品", "评价", "详情"];
          if (goodArray.length) {
            navList.splice(2, 0, "推荐");
          }
          that.navList = navList;
          that.updateTitle();
          that.DefaultSelect();
          that.getCartCount();
          that.getImageBase64();
          that.setOpenShare();
        })
        .catch(res => {
          that.$dialog.error(res.msg);
          that.$router.go(-1);
        });
    },
    getImageBase64: function() {
      let that = this;
      imageBase64(this.posterData.image, that.posterData.code)
        .then(res => {
          that.posterData.image = res.data.image;
          that.posterData.code = res.data.code;
          that.isLogin && that.shareCode();
        })
        .catch(() => {
          that.isLogin && that.shareCode();
        });
    },
    //默认选中属性；
    DefaultSelect: function() {
      let productAttr = this.attr.productAttr,
        value = [];
      for (var key in this.productValue) {
        if (this.productValue[key].stock > 0) {
          value = this.attr.productAttr.length ? key.split(",") : [];
          break;
        }
      }
      for (let i = 0; i < productAttr.length; i++) {
        this.$set(productAttr[i], "index", value[i]);
      }
      //sort();排序函数:数字-英文-汉字；
      let productSelect = this.productValue[value.sort().join(",")];
      if (productSelect && productAttr.length) {
        this.$set(
          this.attr.productSelect,
          "store_name",
          this.storeInfo.store_name
        );
        this.$set(this.attr.productSelect, "image", productSelect.image);
        this.$set(this.attr.productSelect, "price", productSelect.price);
        this.$set(this.attr.productSelect, "stock", productSelect.stock);
        this.$set(this.attr.productSelect, "unique", productSelect.unique);
        this.$set(this.attr.productSelect, "cart_num", 1);
        this.$set(this, "attrValue", value.sort().join(","));
        this.$set(this, "attrTxt", "已选择");
      } else if (!productSelect && productAttr.length) {
        this.$set(
          this.attr.productSelect,
          "store_name",
          this.storeInfo.store_name
        );
        this.$set(this.attr.productSelect, "image", this.storeInfo.image);
        this.$set(this.attr.productSelect, "price", this.storeInfo.price);
        this.$set(this.attr.productSelect, "stock", 0);
        this.$set(this.attr.productSelect, "unique", "");
        this.$set(this.attr.productSelect, "cart_num", 0);
        this.$set(this, "attrValue", "");
        this.$set(this, "attrTxt", "请选择");
      } else if (!productSelect && !productAttr.length) {
        this.$set(
          this.attr.productSelect,
          "store_name",
          this.storeInfo.store_name
        );
        this.$set(this.attr.productSelect, "image", this.storeInfo.image);
        this.$set(this.attr.productSelect, "price", this.storeInfo.price);
        this.$set(this.attr.productSelect, "stock", this.storeInfo.stock);
        this.$set(
          this.attr.productSelect,
          "unique",
          this.storeInfo.unique || ""
        );
        this.$set(this.attr.productSelect, "cart_num", 1);
        this.$set(this, "attrValue", "");
        this.$set(this, "attrTxt", "请选择");
      }
    },
    //购物车；
    ChangeCartNum: function(changeValue) {
      //changeValue:是否 加|减
      //获取当前变动属性
      let productSelect = this.productValue[this.attrValue];
      //如果没有属性,赋值给商品默认库存
      if (productSelect === undefined && !this.attr.productAttr.length)
        productSelect = this.attr.productSelect;
      //无属性值即库存为0；不存在加减；
      if (productSelect === undefined) return;
      let stock = productSelect.stock || 0;
      let num = this.attr.productSelect;
      if (changeValue) {
        num.cart_num++;
        if (num.cart_num > stock) {
          this.$set(this.attr.productSelect, "cart_num", stock);
          this.$set(this, "cart_num", stock);
        }
      } else {
        num.cart_num--;
        if (num.cart_num < 1) {
          this.$set(this.attr.productSelect, "cart_num", 1);
          this.$set(this, "cart_num", 1);
        }
      }
    },
    //将父级向子集多次传送的函数合二为一；
    changeFun: function(opt) {
      if (typeof opt !== "object") opt = {};
      let action = opt.action || "";
      let value = opt.value === undefined ? "" : opt.value;
      this[action] && this[action](value);
    },
    //打开优惠券插件；
    couponTap: function() {
      let that = this;
      that.coupons();
      that.coupon.coupon = true;
    },
    changecoupon: function(msg) {
      this.coupon.coupon = msg;
      this.coupons();
    },
    currentcoupon: function(res) {
      let that = this;
      that.coupon.coupon = false;
      that.$set(that.coupon.list[res], "is_use", true);
    },
    //可领取优惠券接口；
    coupons: function() {
      let that = this,
        q = { page: 1, limit: 20, type: 1, product_id: that.id };
      getCoupon(q).then(res => {
        that.$set(that, "couponList", res.data || []);
        that.$set(that.coupon, "list", res.data);
      });
    },
    //打开属性插件；
    selecAttrTap: function() {
      this.attr.cartAttr = true;
      this.isOpen = true;
    },
    changeattr: function(msg) {
      this.attr.cartAttr = msg;
      this.isOpen = false;
    },
    //选择属性；
    ChangeAttr: function(res) {
      let productSelect = this.productValue[res];
      if (productSelect) {
        this.$set(this.attr.productSelect, "image", productSelect.image);
        this.$set(this.attr.productSelect, "price", productSelect.price);
        this.$set(this.attr.productSelect, "stock", productSelect.stock);
        this.$set(this.attr.productSelect, "unique", productSelect.unique);
        this.$set(this.attr.productSelect, "cart_num", 1);
        this.$set(this, "attrValue", res);
        this.$set(this, "attrTxt", "已选择");
      } else {
        this.$set(this.attr.productSelect, "image", this.storeInfo.image);
        this.$set(this.attr.productSelect, "price", this.storeInfo.price);
        this.$set(this.attr.productSelect, "stock", 0);
        this.$set(this.attr.productSelect, "unique", "");
        this.$set(this.attr.productSelect, "cart_num", 0);
        this.$set(this, "attrValue", "");
        this.$set(this, "attrTxt", "请选择");
      }
    },
    //收藏商品
    setCollect: function() {
      let that = this,
        id = that.storeInfo.id,
        category = "product";
      if (that.storeInfo.userCollect) {
        getCollectDel(id, category).then(function() {
          that.storeInfo.userCollect = !that.storeInfo.userCollect;
        });
      } else {
        getCollectAdd(id, category).then(function() {
          that.storeInfo.userCollect = !that.storeInfo.userCollect;
        });
      }
    },
    goGoods(item) {
      if (item.activity && item.activity.type === "1") {
        this.$router.push({
          path:
            "/activity/seckill_detail/" +
            item.activity.id +
            "/" +
            item.activity.time +
            "/1"
        });
      } else if (item.activity && item.activity.type === "2") {
        this.$router.push({
          path: "/activity/dargain_detail/" + item.activity.id
        });
      } else if (item.activity && item.activity.type === "3") {
        this.$router.push({
          path: "/activity/group_detail/" + item.activity.id
        });
      } else {
        this.$router.push({ path: "/detail/" + item.id });
      }
    },
    //  点击加入购物车按钮
    joinCart: function() {
      //0=加入购物车
      this.goCat(0);
    },
    // 加入购物车；
    goCat: function(news) {
      let that = this,
        productSelect = that.productValue[this.attrValue];
      //打开属性
      if (that.attrValue) {
        //默认选中了属性，但是没有打开过属性弹窗还是自动打开让用户查看默认选中的属性
        that.attr.cartAttr = !that.isOpen ? true : false;
      } else {
        if (that.isOpen) that.attr.cartAttr = true;
        else that.attr.cartAttr = !that.attr.cartAttr;
      }
      //只有关闭属性弹窗时进行加入购物车
      if (that.attr.cartAttr === true && that.isOpen === false)
        return (that.isOpen = true);
      if (
        !this.attr.productSelect.cart_num ||
        parseInt(this.attr.productSelect.cart_num) <= 0
      )
        return that.$dialog.toast({ mes: "请输入购买数量" });
      //如果有属性,没有选择,提示用户选择
      if (
        that.attr.productAttr.length &&
        productSelect === undefined &&
        that.isOpen === true
      )
        return that.$dialog.toast({ mes: "产品库存不足，请选择其它" });
      let q = {
        productId: that.id,
        cartNum: that.attr.productSelect.cart_num,
        new: news,
        uniqueId:
          that.attr.productSelect !== undefined
            ? that.attr.productSelect.unique
            : ""
      };
      postCartAdd(q)
        .then(function(res) {
          that.isOpen = false;
          that.attr.cartAttr = false;
          if (news) {
            that.$router.push({ path: "/order/submit/" + res.data.cartId });
          } else {
            that.$dialog.toast({
              mes: "添加购物车成功",
              callback: () => {
                that.getCartCount(true);
              }
            });
          }
        })
        .catch(res => {
          that.isOpen = false;
          return that.$dialog.toast({ mes: res.msg });
        });
    },
    //获取购物车数量
    getCartCount: function(isAnima) {
      let that = this;
      const isLogin = that.isLogin;
      if (isLogin) {
        getCartCount({ numType: 0 }).then(res => {
          that.CartCount = res.data.count;
          //加入购物车后重置属性
          if (isAnima) {
            that.animated = true;
            setTimeout(function() {
              that.animated = false;
            }, 500);
          }
        });
      }
    },
    //立即购买；
    tapBuy: function() {
      //  1=直接购买
      this.goCat(1);
    },
    listenerActionSheet: function() {
      if (isWeixin() === true) {
        this.weixinStatus = true;
      }
      this.posters = true;
    },
    listenerActionClose: function() {
      this.posters = false;
    }
  },
  beforeDestroy: function() {
    document.removeEventListener("scroll", this.onScroll, false);
    window.removeEventListener("scroll", this.handleScroll);
  }
};
</script>
<style scoped>
.paybox{background: #fff;width: 96%;margin-left: 2%; margin-top: 0.5rem;box-shadow: 2px 2px 5px #ccc;}
.pay_box{width: 90%; margin-left: 5%;}
.shop_name{line-height: 0.5rem;margin-top: 0.3rem;font-size: 0.35rem;}
.shop_amount{line-height: 1rem;border-bottom: 1px solid #efefef;font-size: 0.5rem;margin-top:0.2rem;margin-bottom:0.5rem;}
.pay_btn{width:100%;margin-left:0rem;}
.shop_amount span{width:10%;}
.shop_amount input{width:90%;}
.codeVal {
  width: 1.5rem;
  height: 0.5rem;
}
.codeVal img {
  width: 100%;
  height: 100%;
}
.checkbox-wrapper .icon {
    right: 0;
    left: unset;
}
.integral {
    margin-right: .4rem;
}
</style>
