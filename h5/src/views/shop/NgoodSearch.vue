<template>
  <div>
    <div class="searchGood">
      <div class="search acea-row row-between-wrapper">
        <div class="input acea-row row-between-wrapper">
          <span class="iconfont icon-sousuo2"></span>
          <form @submit.prevent="submit"></form>
          <input type="text" placeholder="点击搜索商品信息" v-model="search" />
        </div>
        <div class="bnt" @click="submit">搜索</div>
      </div>
      <div v-if="keywords.length">
        <div class="title">热门搜索</div>
        <div class="list acea-row">
          <div class="item"  @click="toSearch('普安红')">
                                       普安红
          </div>
          <div class="item"  @click="toSearch('香菇')">
                                       香菇
          </div>
          <div class="item"  @click="toSearch('薏仁')">
                                        薏仁
          </div>
          <div class="item"  @click="toSearch('板栗')">
                                        板栗
          </div>
          <div class="item"  @click="toSearch('休闲')">
                                        休闲
          </div>
        </div>
      </div>
      <div class="line"></div>
      <!--      <GoodList></GoodList>-->
    </div>
    <!--<div class="noCommodity">-->
    <!--<div class="noPictrue">-->
    <!--<img src="@assets/images/noSearch.png" class="image" />-->
    <!--</div>-->
    <!--<recommend></recommend>-->
    <!--</div>-->
  </div>
</template>
<script>
// import GoodList from "@components/GoodList";
import { getSearchKeyword } from "@api/store";
import { trim } from "@utils";
// import Recommend from "@components/Recommend";
export default {
  name: "GoodSearch",
  components: {
    // Recommend,
    // GoodList
  },
  props: {},
  data: function() {
    return {
      keywords: [],
      search: ""
    };
  },
  mounted: function() {
    this.getData();
  },
  methods: {
    submit() {
      const search = trim(this.search) || "";
      if (!search) return;
      this.toSearch(search);
    },
    toSearch(s) {
      this.$router.push({ path: "/wgoods_list", query: { s } });
    },
    getData() {
      getSearchKeyword().then(res => {
        this.keywords = res.data;
      });
    }
  }
};
</script>
<style scoped>
.noCommodity {
  border-top: 0.05rem solid #f5f5f5;
}
</style>
