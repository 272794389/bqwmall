import { getProductslist, getProductHot,getDetailCategory } from '../../api/store.js';
import wxh from '../../utils/wxh.js';
import { getCity } from '../../api/api.js';


const app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    productList:[],
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '商品列表',
      'color': true,
      'class': '0'
    },
    navH: "",
    is_switch:true,
    category: [],
    navActive: 0,
    where: {
      sid: 0,
      latitude:'',
      longitude:'',
      keyword: '',
      priceOrder: '',
      salesOrder: '',
      news: 0,
      page: 1,
      limit: 10,
      cid: 0,
    },
    price:0,
    stock:0,
    nows:false,
    loadend:false,
    loading:false,
    loadTitle:'加载更多',
    userInfo:{},
    condition: 1
  },
  onLoadFun: function (e) {
    this.setData({
      userInfo: e.detail
    })
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({ 
      ['where.sid']: options.sid || 0, 
      ['where.cid']: options.cid || 0,
      navActive:options.cid || 0,
      title: options.title || '', 
      ['where.keyword']: options.searchValue || '', 
      navH: app.globalData.navHeight
    });
    wxh.selfLocation().then(res=>{
      this.setData({
        ['where.latitude']: res.latitude || '', //纬度
        ['where.longitude']: res.longitude || '' //经度
      });
    });
    this.loadCategoryData();
    this.get_product_list();
    this.get_host_product();
    var that = this;
    if(that.data.condition==1&&that.data.productList.length==0){
      that.setData({ condition: 2,loadend:false, loading:false});
       this.get_product_list(true);
    }


  },

  asideTap:function(e) {
    let did = e.currentTarget.dataset.id;
    this.setData({ navActive: did, stock: 0 });
    if(this.data.where.sid>0){
      this.setData({ loadend: false, ['where.page']: 1, ['where.cid']: did,navActive: did });
    }else{
      this.setData({ loadend: false, ['where.page']: 1, ['where.sid']: did,navActive: did });
      this.loadCategoryData();
    }
    this.get_product_list(true);
  },

   /**
   * 商品详情跳转
   */
  goDetail: function (e) {
    let item = e.currentTarget.dataset.items;
   if (item.activity && item.activity.type === "1") {
     wx.navigateTo({
      url: `/pages/activity/goods_seckill_details/index?id=${item.activity.id}&time=${item.activity.time}&status=1`
     });
   } else if (item.activity && item.activity.type === "2") {
     wx.navigateTo({ url:  `/pages/activity/goods_bargain_details/index?id=${item.activity.id}&bargain=${this.data.userInfo.uid}`});
   } else if (item.activity && item.activity.type === "3") {
     wx.navigateTo({
       url: `/pages/activity/goods_combination_details/index?id=${item.activity.id}`
     });
   } else {
     wx.navigateTo({ url: `/pages/goods_details/index?id=${item.id}` });
   }
   },
  Changswitch:function(){
     var that = this;
     that.setData({
       is_switch: !this.data.is_switch
     })
  },
  searchSubmit: function (e) {
    var that = this;
    this.setData({ ['where.keyword']: e.detail.value, loadend: false, ['where.page']: 1 })
    this.get_product_list(true);
  },
  /**
  * 获取我的推荐
  */
  get_host_product: function () {
    var that = this;
    getProductHot().then(res=>{
      that.setData({ host_product: res.data });
    });
  },
  goStyle:function(e){
    wx.navigateTo({ url: "/pages/agoods_cate/goods_cate" });
  },
  //点击事件处理
  set_where: function (e) {
    var dataset = e.target.dataset;
    switch (dataset.type) {
      case '0':
        wx.navigateTo({ url: "/pages/agoods_cate/goods_cate" });
        break;
      case '1':
        this.setData({ condition: 1 });
        break;
      case '2':
        this.setData({ condition: 2 });
        break;
      case '3':
        if (this.data.stock == 0)
          this.data.stock = 1;
        else if (this.data.stock == 1)
          this.data.stock = 2;
        else if (this.data.stock == 2)
          this.data.stock = 0;
        this.setData({ stock: this.data.stock, price: 0 });
        break;
      case '4':
        this.setData({ nows: !this.data.nows });
        break;
    }
    this.setData({ loadend: false, ['where.page']: 1 });
    this.get_product_list(true);
  },
  //设置where条件
  setWhere: function () {
    if (this.data.price == 0)
      this.data.where.priceOrder = '';
    else if (this.data.price == 1)
      this.data.where.priceOrder = 'desc';
    else if (this.data.price == 2)
      this.data.where.priceOrder = 'asc';
    if (this.data.stock == 0)
      this.data.where.salesOrder = '';
    else if (this.data.stock == 1)
      this.data.where.salesOrder = 'desc';
    else if (this.data.stock == 2)
      this.data.where.salesOrder = 'asc';
    this.data.where.news = this.data.nows ? 1 : 0;
    this.data.where.condition = this.data.condition;
    this.setData({ where: this.data.where });
  },
  
  //获取分类
  loadCategoryData:function() {
    let that = this;
    getDetailCategory(that.data.where.sid).then(res => {
      let clist = res.data;
      let category = app.SplitArray(clist, that.data.category);
      that.setData({
        category : category
      })
    });
  },


  //查找产品
  get_product_list: function (isPage) {
    let that = this;
    this.setWhere();
    if (that.data.loadend) return;
    if (that.data.loading) return;
    if (isPage === true) that.setData({ productList: [] });
    that.setData({ loading: true, loadTitle: '' });
    getProductslist(that.data.where).then(res=>{
      let list = res.data;
      if(that.data.condition==1){
        list = res.data.list;
      }else{
        list = res.data;
      }
      let productList = app.SplitArray(list, that.data.productList);
      let loadend = list.length < that.data.where.limit;
      that.setData({
        loadend: loadend,
        loading: false,
        loadTitle: loadend ? '已全部加载' : '加载更多',
        productList: productList,
        ['where.page']: that.data.where.page + 1,
      });
    }).catch(err=>{
      that.setData({ loading: false, loadTitle: '加载更多' });
    });
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
    this.setData({['where.page']:1,loadend:false,productList:[]});
    this.get_product_list();
    wx.stopPullDownRefresh();
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    this.get_product_list();
  },
})