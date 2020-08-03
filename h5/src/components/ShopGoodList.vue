<template>
  <div class="goodList">
    <div
      @click="goDetail(item)"
      v-for="(item, index) in goodList"
      :key="index"
      class="item acea-row row-between-wrapper"
    >
      <div class="pictrue">
        <img :src="item.image" class="image" />
        <!--<img-->
        <!--src="@assets/images/one.png"-->
        <!--class="numPic"-->
        <!--v-if="isSort === true && index === 0"-->
        <!--/>-->
        <!--<img-->
        <!--src="@assets/images/two.png"-->
        <!--class="numPic"-->
        <!--v-if="isSort === true && index === 1"-->
        <!--/>-->
        <!--<img-->
        <!--src="@assets/images/three.png"-->
        <!--class="numPic"-->
        <!--v-if="isSort === true && index === 2"-->
        <!--/>-->
      </div>
      <div class="underline">
        <div class="text">
          <div class="pline1">{{ item.store_name }}</div>
          <div class="money font-color-red">
            ￥<span class="num">{{ item.price }}</span>
           
          </div>
          <div class="vip-money acea-row row-middle">
            <span class="shou" style="margin-left:0rem;">已售{{ item.sales }}{{ item.unit_name }}</span>
           <!--
            <div class="vip" v-if="item.pay_paypoint > 0">
               <img src="@assets/images/fu.png" class="image" />{{ item.pay_paypoint || 0}}个消费积分+￥{{ item.pay_amount || 0}}
            </div>
            <div class="vip" v-if="item.pay_repeatpoint > 0">
               <img src="@assets/images/fu.png" class="image" />{{ item.pay_repeatpoint || 0}}个重消积分+￥{{ item.pay_amount || 0}}
            </div>
            <div class="vip" v-if="item.pay_repeatpoint ==0&&item.pay_paypoint==0">
               <img src="@assets/images/fu.png" class="image" />￥{{ item.pay_amount || 0}}
            </div>
            -->
          </div>
        </div>
      </div>
      <div
        class="iconfont icon-gouwuche cart-color acea-row row-center-wrapper"
      ></div>
    </div>
  </div>
</template>
<script>
export default {
  name: "GoodList",
  props: {
    goodList: {
      type: Array,
      default: () => []
    },
    isSort: {
      type: Boolean,
      default: true
    }
  },
  data: function() {
    return {};
  },
  methods: {
    // 商品详情跳转
    goDetail(item) {
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
    }
  }
};
</script>
