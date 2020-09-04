const app = getApp();

import { getIndexData, getCoupons, getTemlIds, getLiveList} from '../../api/api.js';
import { CACHE_SUBSCRIBE_MESSAGE } from '../../config.js';
import { storeListApi,goodListApi,getNearStoreData } from '../../api/store.js';
import Util from '../../utils/util.js';
import wxh from '../../utils/wxh.js';
Page({
  /**
   * 页面的初始数据
   */
  data: {
    imgUrls: [],
    itemNew:[],
    activityList:[],
    bastBanner: [],
    bastInfo: '',
    bastList: [],
    fastInfo: '',
    hostList:[],
    fastList: [],
    sfastList: [],
    tfastList: [],
    ffastList: [],
    netGoodList:[],
    nearGoodList:[],
    storeList:[],
    firstInfo: '',
    firstList: [],
    salesInfo: '',
    likeInfo: [],
    lovelyBanner: {},
    benefit:[],
    indicatorDots: false,
    circular: true,
    autoplay: true,
    interval: 3000,
    duration: 500,
    parameter:{
      'navbar':'0',
      'return':'0'
    },
    window: false,
    iShidden:false,
    navH: "",
    newGoodsBananr:'',
    selfLongitude: '',
    selfLatitude: '',
    liveList: [],
    liveInfo:{},
    condition:3,
    searchBarFixed:0,
  },
  closeTip:function(){
    wx.setStorageSync('msg_key',true);
    this.setData({
      iShidden:true
    })
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.selfLocation();
    this.setData({
      navH: app.globalData.navHeight
    });
    if (options.spid) app.globalData.spid = options.spid;
    if (options.scene) app.globalData.code = decodeURIComponent(options.scene);
    if (wx.getStorageSync('msg_key')) this.setData({ iShidden:true});
    this.getTemlIds();
    this.getLiveList();
  },
  getLiveList:function(){
    getLiveList(1,20).then(res=>{
      if(res.data.length == 1){
        this.setData({liveInfo:res.data[0]});
      }else{
        this.setData({liveList:res.data});
      }
    }).catch(res=>{

    })
  },

/**
  * 获取本地特惠套餐及周边的店
  * 
  */
 selfLocation: function () {
  const that = this;
  wxh.selfLocation().then(res=>{
    let data={
      latitude: res.latitude || '', //纬度
      longitude: res.longitude || '', //经度
      page: 1,
      limit: 10
    }
    storeListApi(data).then(res => {
      let list = res.data.list || [];
      this.data.storeList = app.SplitArray(list, this.data.storeList);
      this.setData({
        storeList: this.data.storeList
      });
    }).catch(err => {
      wx.showToast({
        title: '网络连接失败，请检查网络！',
        icon: 'none',
        duration: 2000//持续的时间
      })
    })
    goodListApi(data).then(res => {
      let list = res.data.list || [];
      this.data.nearGoodList = app.SplitArray(list, this.data.nearGoodList);
      this.setData({
        nearGoodList: this.data.nearGoodList
      });
    }).catch(err => {
      wx.showToast({
        title: '网络连接失败，请检查网络0！',
        icon: 'none',
        duration: 2000//持续的时间
      })
    })
  }).catch(()=>{
    let data={
      page: 1,
      limit: 10
    }
    getNearStoreData(data).then(res => {
       let list = res.data.storeList || [];
       let list1 = res.data.nearGoodList || [];
       this.data.nearGoodList = app.SplitArray(list1, this.data.nearGoodList);
       this.data.storeList = app.SplitArray(list, this.data.storeList);
       this.setData({
        nearGoodList: this.data.nearGoodList,
        storeList: this.data.storeList
      });
    })
  });
},


  tap: function (e){
    var index = e.currentTarget.dataset.id;
    console.log(index)
    this.setData({
      condition: index
    });
    console.log(this.data.condition)
  },
  /**
   * 商品详情跳转
   */
   goDetail: function (e) {
     console.log(e)
     let item = e.currentTarget.dataset.items
     wx.navigateTo({ url: "/pages/goods_details/index?id=" + item.id });
  },
  getTemlIds(){
    let messageTmplIds = wx.getStorageSync(CACHE_SUBSCRIBE_MESSAGE);
    if (!messageTmplIds){
      getTemlIds().then(res=>{
        if (res.data) 
          wx.setStorageSync(CACHE_SUBSCRIBE_MESSAGE, JSON.stringify(res.data));
      })
    }
  },
  catchTouchMove: function (res) {
    return false
  },
  onColse:function(){
    this.setData({ window: false});
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
    this.getIndexConfig();
    if(app.globalData.isLog && app.globalData.token) this.get_issue_coupon_list();
  },
  get_issue_coupon_list:function(){
    var that = this;
    getCoupons({page:1,limit:3}).then(res=>{
      that.setData({ couponList: res.data });
      if (!res.data.length) that.setData({ window: false });
    });
  },
  getIndexConfig:function(){
    var that = this;
    getIndexData().then(res=>{
      that.setData({
        imgUrls: res.data.banner,
        itemNew: res.data.roll,
        activityList: res.data.activity,
        bastBanner: res.data.info.bastBanner,
        bastInfo: res.data.info.bastInfo,
        bastList: res.data.info.bastList,
        fastInfo: res.data.info.fastInfo,
        fastList: res.data.info.fastList,
        sfastList: res.data.info.sfastList,
        tfastList: res.data.info.tfastList,
        ffastList: res.data.info.ffastList,
        netGoodList: res.data.info.netGoodList,
        hostList:res.data.info.hostList,
        firstInfo: res.data.info.firstInfo,
        firstList: res.data.info.firstList,
        salesInfo: res.data.info.salesInfo,
        likeInfo: res.data.likeInfo,
        
        benefit: res.data.benefit,
        logoUrl: res.data.logoUrl,
        couponList: res.data.couponList,
        newGoodsBananr: res.data.newGoodsBananr
      });
      wx.getSetting({
        success(res) {
          if (!res.authSetting['scope.userInfo']) {
            that.setData({ window: that.data.couponList.length ? true : false });
          } else {
            that.setData({ window: false, iShidden: true});
          }
        }
      });
    })
  },
  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    this.setData({ window:false});
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
    this.getIndexConfig();
    if (app.globalData.isLog && app.globalData.token) this.get_issue_coupon_list();
    wx.stopPullDownRefresh();
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  onPageScroll: function (t) {
    var a = this;
     console.log(t.scrollTop)
    
      a.setData({
        searchBarFixed:t.scrollTop
        })
  },
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})