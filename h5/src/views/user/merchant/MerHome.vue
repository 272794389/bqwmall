<template>
  <div class="my-promotion">
    <div class="header">
      <div class="name acea-row row-center-wrapper" style="width:96%;margin-left:2%;">
        <h3 v-if="status==0">当前还不是商家,立刻申请成为商家</h3>
        <h3 v-if="status==1">你申请的商家《{{mer.name}}》 正在审核中<br/>请耐心等待</h3>
        <h3 v-if="status==2"> {{mer.name}} 欢迎你</h3>
      </div>
    </div>
    <div v-if="status==0" class="bnt  bg-color-red" @click="toApply">立即申请</div>


    <div class="list acea-row row-between-wrapper" v-if="status==2">
      <router-link class="item acea-row row-center-wrapper row-column" :to="{ path: '/merchant/service/' + mer.id }" v-if="service.is_admin==1">
        <span class="iconfont icon-paihang1"></span>
        <div>客服管理</div>
      </router-link>

      <router-link
        class="item acea-row row-center-wrapper row-column"
        :to="'/merchant/plist'"  v-if="service.is_admin==1"
      >
        <span class="iconfont icon-paihang"></span>
        <div>商品管理</div>
      </router-link>
  
      <router-link
        class="item acea-row row-center-wrapper row-column"
        :to="'/customer/index'"  v-if="service.is_admin==1"
      >
        <span class="iconfont icon-dingdan"></span>
        <div>订单管理</div>
      </router-link>
      <router-link
        class="item acea-row row-center-wrapper row-column"
        :to="'/merchant/ermapay'"
      >
        <span class="iconfont icon-erweima"></span>
        <div>收款码</div>
      </router-link>
      <router-link
        class="item acea-row row-center-wrapper row-column"
        :to="'/customer/myorder/'+ service.uid" v-if="service.uid>0"
      >
        <span class="iconfont icon-xiaolian"></span>
        <div>我的业绩</div>
      </router-link>
      <router-link
        class="item acea-row row-center-wrapper row-column"
        :to="'/customer/payorder/'+ service.uid"
      >
        <span class="iconfont icon-tuikuanzhong"></span>
        <div>预收款管理</div>
      </router-link>
    </div>
  </div>
</template>
<script>
import { getMerHome } from "@api/merchant";
export default {
  name: "UserPromotion",
  components: {},
  props: {},
  data: function() {
    return {
      status:0,
      mer: {},
      service:{}
    };
  },
  watch: {
    $route(n) {
      if (n.name == "UserPromotion") this.getInfo();
    }
  },
  mounted: function() {
    this.getMerHome();
  },
  methods: {
    getMerHome: async function () {
      const {data} = await getMerHome();
      this.$data.status = data.status;
      this.$data.mer = data.mer;
      this.$data.service = data.service;
    },
    toApply: function() {
      this.$router.push({path: "/merchant/storeAdd/0"});
    }
  }
};
</script>
