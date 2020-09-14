import { storeListApi,getDetailCategory } from '../../api/store.js';
import wxh from '../../utils/wxh.js';
import { getCity } from '../../api/api.js';


const app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    storeList:[],
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '商家列表',
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
      city: "",
      district: "",
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
    condition: 1,
    region: ['省', '市', '区'],
    valueRegion: [0, 0, 0],
    cityId:0,
    district:[],
    multiArray:[],
    multiIndex: [0, 0, 0],
    model2:'全国'
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
    this.getCityList();
    this.loadCategoryData();
    this.get_product_list();
  },

  getCityList:function(){
    let that = this;
    getCity().then(res=>{
      that.setData({ district:res.data});
      that.initialize();
    })
  },
  initialize:function(){
    let that = this, province = [], city = [], area = [];
    if (that.data.district.length) {
      let cityChildren = that.data.district[0].c || [];
      let areaChildren = cityChildren.length ? (cityChildren[0].c || []) : [];
      that.data.district.forEach(function (item) {
        province.push(item.n);
      });
      cityChildren.forEach(function (item) {
        city.push(item.n);
      });
      areaChildren.forEach(function (item) {
        area.push(item.n);
      });
      that.setData({
        multiArray: [province, city, area],
      });
    }
  },

  bindRegionChange: function (e) {
    let multiIndex = this.data.multiIndex, province = this.data.district[multiIndex[0]] || { c: [] }, city = province.c[multiIndex[1]] || { v: 0 }, multiArray = this.data.multiArray, value = e.detail.value;
    this.setData({
      region: [multiArray[0][value[0]], multiArray[1][value[1]], multiArray[2][value[2]]],
      cityId: city.v,
      storeList:[],
      loadend:false,
      loading:false,
      valueRegion: [0,0,0],
      ['where.page']: 1,
      ['where.city']: multiArray[1][value[1]] || '',
      ['where.district']: multiArray[2][value[2]] || '',
      model2:multiArray[2][value[2]] || '全国'
    });

    this.initialize();
    this.get_product_list();
  },
  bindMultiPickerColumnChange:function(e){
    let that = this, column = e.detail.column, value = e.detail.value, currentCity = this.data.district[value] || { c: [] }, multiArray = that.data.multiArray, multiIndex = that.data.multiIndex;
    multiIndex[column] = value;
    switch (column){
      case 0:
        let areaList = currentCity.c[0] || { c: [] };
        multiArray[1] = currentCity.c.map((item)=>{
          return item.n;
        });
        multiArray[2] = areaList.c.map((item)=>{
          return item.n;
        });
      break;
      case 1:
        let cityList = that.data.district[multiIndex[0]].c[multiIndex[1]].c || [];
        multiArray[2] = cityList.map((item)=>{
          return item.n;
        });
      break;
      case 2:
      break;
    }
    this.setData({ multiArray: multiArray, multiIndex: multiIndex});
  },

  asideTap:function(e) {
    let did = e.currentTarget.dataset.id;
    this.setData({ navActive: did,storeList:[], stock: 0 });
    if(this.data.where.sid>0){
      this.setData({ loadend: false, ['where.page']: 1, ['where.cid']: did,navActive: did });
    }else{
      this.setData({ loadend: false, ['where.page']: 1, ['where.sid']: did,navActive: did,category:[] });
      this.loadCategoryData();
    }

    this.get_product_list(true);
  },

   /**
   * 商品详情跳转
   */
  goShop: function (e) {
    let item = e.currentTarget.dataset.items;
    wx.navigateTo({ url: `/pages/shop_details/index?id=${item.id}` });
   },
  Changswitch:function(){
     var that = this;
     that.setData({
       is_switch: !this.data.is_switch
     })
  },
  searchSubmit: function (e) {
    var that = this;
    this.setData({ ['where.keyword']: e.detail.value, loadend: false, ['where.page']: 1,storeList:[] })
    this.get_product_list(true);
  },
  
  goStyle:function(){
    wx.navigateTo({ url: "/pages/sgoods_cate/goods_cate" });
  },
  //点击事件处理
  set_where: function (e) {
    var dataset = e.target.dataset;
    switch (dataset.type) {
      case '0':
        wx.navigateTo({ url: "/pages/sgoods_cate/goods_cate" });
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
    this.setData({ storeList: []});
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
    if (isPage === true) that.setData({ store_list: [] });
    that.setData({ loading: true, loadTitle: '' });
    wxh.selfLocation().then(res=>{
      that.data.where.latitude = res.latitude || ''; //纬度
      that.data.where.longitude = res.longitude || ''; //经度
      storeListApi(that.data.where).then(res=>{
        let list = res.data.list;
        let storeList = app.SplitArray(list, that.data.storeList);
        let loadend = list.length < that.data.where.limit;
        that.setData({
          loadend: loadend,
          loading: false,
          loadTitle: loadend ? '已全部加载' : '加载更多',
          storeList: storeList,
          ['where.page']: that.data.where.page + 1,
        });
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
    this.setData({['where.page']:1,loadend:false,storeList:[]});
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