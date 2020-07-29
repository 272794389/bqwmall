<template>
  <div class="my-promotion" style="height:100%;background:#EFB600;">
    <div class="mabox">
         <div class="malogo"><img src="@assets/images/qiandai.png"/><span>二维码收款</span></div>
         <div class="erma_tip">扫二维码向我付款</div>
         <img :src="erurl" style="width:50%;margin-left:25%;margin-bottom:0.8rem;" />
    </div>
  </div>
</template>
<script>
import { getErMaUrl } from "@api/merchant";

export default {
  name: "UserPayerma",
  components: {},
  props: {},
  data: function() {
    return {
      erurl: ''
    };
  },
  watch: {
    $route(n) {
      if (n.name == "UserPayerma") this.getInfo();
    }
  },
  mounted: function() {
    this.getErMaUrl();
  },
  methods: {
    getErMaUrl:function () {
      let that = this;
       getErMaUrl().then(res => {
        that.erurl = res.data.ermaImg;
      });
    }
  }
};
</script>
