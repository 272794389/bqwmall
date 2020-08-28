<template>
  <div class="newsList" ref="container">
        <div class="list" v-for="(item, index) in articleList" :key="index">
          <router-link
            :to="{ path: '/news_detail/' + item.id }"
            class="item acea-row row-between-wrapper"
          >
            <div class="text acea-row row-column-between">
              <div class="name line2">{{ item.title }}</div>
            </div>
            <span class="iconfont icon-jiantou"></span>
          </router-link>
        </div>
        <Loading
          :loaded="loadend"
          :loading="loading"
          v-if="index > 0 && articleList.length > 0"
        ></Loading>
        <!--暂无新闻-->
        <div class="noCommodity" v-if="articleList.length === 0 && page > 1">
          <div class="noPictrue">
            <img src="@assets/images/noNews.png" class="image" />
          </div>
        </div>
  </div>
</template>
<script>
import { swiper, swiperSlide } from "vue-awesome-swiper";
import "@assets/css/swiper.min.css";
import {
  getArticleList
} from "@api/public";
import Loading from "@components/Loading";

export default {
  name: "NewsList",
  components: {
    swiper,
    swiperSlide,
    Loading
  },
  props: {},
  data: function() {
    const { cid=0 } = this.$route.query;
    return {
      page: 1,
      limit: 20,
      loadTitle: "",
      loading: false,
      loadend: false,
      articleList: [],
      active: 0,
      cid: 0,
      swiperNew: {
        pagination: {
          el: ".swiper-pagination",
          clickable: true
        },
        autoplay: {
          disableOnInteraction: false,
          delay: 2000
        },
        loop: true,
        speed: 1000,
        observer: true,
        observeParents: true
      }
    };
  },
  watch: {
    $route(to) {
      const { cid=0 } = this.$route.query;
      this.cid = cid;
     
    }
  },
  mounted: function() {
    const { cid=0 } = this.$route.query;
    this.cid = cid;
    this.getArticleLists();
    this.$scroll(this.$refs.container, () => {
      !this.loading && this.getArticleLists();
    });
  },
  methods: {
    getArticleLists: function() {
      let that = this;
      if (that.loading) return; //阻止下次请求（false可以进行请求）；
      if (that.loadend) return; //阻止结束当前请求（false可以进行请求）；
      that.loading = true;
      let q = {
        page: that.page,
        limit: that.limit
      };
      getArticleList(q, that.cid).then(res => {
        that.loading = false;
        //apply();js将一个数组插入另一个数组;
        that.articleList.push.apply(that.articleList, res.data);
        that.loadend = res.data.length < that.limit; //判断所有数据是否加载完成；
        that.page = that.page + 1;
      });
    }
  }
};
</script>
<style scoped>
.newsList .list .item{padding: .15rem 0;}
.newsList .list .item .text{height: 0.56rem;line-height: 0.56rem;width: 6.2rem;}
.subsc{float: right;line-height: 0.6rem;}
</style>
