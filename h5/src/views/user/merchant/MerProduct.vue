<template>
  <div class="my-order" ref="container">
    <div class="header bg-color-red">
      <div class="picTxt acea-row row-between-wrapper">
        <div class="text">
          <div class="name">商品信息</div>
          <div>
            累计上架：{{ productData.pcount || 0 }} 件，下架：{{productData.xcount || 0}}件
          </div>
        </div>
        <div class="pictrue"><img src="@assets/images/orderTime.png" /></div>
      </div>
    </div>
    <div class="nav acea-row row-around">
      <div
        class="item"
        :class="{ on: type === 1 }"
        @click="$router.replace({ path: '/merchant/plist/1' })"
      >
        <div>销售中</div>
        <div class="num">{{ productData.pcount || 0 }}</div>
      </div>
      <div
        class="item"
        :class="{ on: type === 0 }"
        @click="$router.replace({ path: '/merchant/plist/0' })"
      >
        <div>已下架</div>
        <div class="num">{{ productData.xcount || 0 }}</div>
      </div>
    </div>
    <div class="list">
      <div class="item" v-for="productinfo in productList" :key="productinfo.id">
        <div @click="$router.push({ path: '/detail/' + productinfo.id })">
          <div class="item-info acea-row row-between row-top productsytle" >
            <div class="pictrue">
              <img
                :src="productinfo.image"
                @click.stop="$router.push({ path: '/detail/' + productinfo.id })"
              />
            </div>
            <div class="text acea-row row-between">
              <div class="name line2">
                {{ productinfo.store_name }}
              </div>
              <div class="money">
                <div>￥{{productinfo.price}}</div>
                <div v-if="productinfo.is_show ===1"  class="doxia" @click="cancelProduct(productinfo)">下架</div>
                <div v-else  class="doshang" @click="cancelProduct(productinfo)">上架</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="noCart" v-if="productList.length === 0 && page > 1">
      <div class="pictrue"><img src="@assets/images/noGood.png" /></div>
    </div>
    <Loading :loaded="loaded" :loading="loading"></Loading>
    <GeneralWindow
      :generalActive="generalActive"
      @closeGeneralWindow="closeGeneralWindow"
      :generalContent="generalContent"
    ></GeneralWindow>
  </div>
</template>
<script>
import { getProductData, getProductList } from "@api/merchant";
import {cancelProductHandle} from "@libs/order";
import Loading from "@components/Loading";
import { mapGetters } from "vuex";
import { isWeixin } from "@utils";
import GeneralWindow from "@components/GeneralWindow";

const STATUS = [
  "销售中",
  "已下架"
];

const NAME = "MyProduct";

export default {
  name: NAME,
  data() {
    return {
      offlinePayStatus: 2,
      productData: {},
      type: parseInt(this.$route.params.type) || 1,
      page: 1,
      limit: 20,
      loaded: false,
      loading: false,
      productList: [],
      generalActive: false,
      generalContent: {
        promoterNum: "",
        title: ""
      }
    };
  },
  components: {
    Loading,
    GeneralWindow
  },
  //computed: mapGetters(["userInfo"]),
  watch: {
   
    $route(n) {
        const type = parseInt(this.$route.params.type) || 0;
        if (this.type !== type) {
          this.changeType(type);
        }
        this.getProductData();
    /*
      if (n.name === NAME) {
        const type = parseInt(this.$route.params.type) || 0;
        if (this.type !== type) {
          this.changeType(type);
        }
        this.getProductData();
      }*/
    }
  },
  methods: {
    setOfflinePayStatus: function(status) {
      var that = this;
      that.offlinePayStatus = status;
      if (status === 1) {
        if (that.payType.indexOf("offline") < 0) {
          that.payType.push("offline");
        }
      }
    },
    getProductData() {
      getProductData().then(res => {
        this.productData = res.data.proinfo;
      });
    },
    closeGeneralWindow(msg) {
      this.generalActive = msg;
      this.reload();
      this.getProductList();
    },
    reload() {
      this.changeType(this.type);
    },
    changeType(type) {
      this.type = type;
      this.productList = [];
      this.page = 1;
      this.loaded = false;
      this.loading = false;
      this.getProductList();
    },
    getProductList() {
      if (this.loading || this.loaded) return;
      this.loading = true;
      const { page, limit, type } = this;
      getProductList({
        page,
        limit,
        type
      }).then(res => {
        this.productList = this.productList.concat(res.data);
        this.page++;
        this.loaded = res.data.length < this.limit;
        this.loading = false;
      });
    },
    getStatus(order) {
      return STATUS[order._status._type];
    },
    cancelProduct(productinfo) {
      cancelProductHandle(productinfo.id)
        .then(() => {
          this.getProductData();
          this.productList.splice(this.productList.indexOf(productinfo), 1);
        })
        .catch(() => {
          this.reload();
        });
    }
  },
  mounted() {
    this.getProductData();
    this.getProductList();
    this.$scroll(this.$refs.container, () => {
      !this.loading && this.productList();
    });
  }
};
</script>

<style scoped>
.noCart {
  margin-top: 0.17rem;
  padding-top: 0.1rem;
}

.noCart .pictrue {
  width: 4rem;
  height: 3rem;
  margin: 0.7rem auto 0.5rem auto;
}

.noCart .pictrue img {
  width: 100%;
  height: 100%;
}
</style>
