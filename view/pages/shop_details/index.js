import { getStoreDetail} from '../../api/store.js';
import { getUserInfo, userShare } from '../../api/user.js';
import util from '../../utils/util.js';
import wxh from '../../utils/wxh.js';
import { CACHE_LONGITUDE, CACHE_LATITUDE } from '../../config.js';
const app = getApp();

Page({

  /**
   * 页面的初始数据
   */
  data: {
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '商家详情'
    },
    id: 0,//商家id
    uid: 0,//用户uid
    storeInfo: {},
    goodList: [],
    labelList: [],
    couponList:[],
    isAuto: false,//没有授权的不会自动授权
    iShidden: true,//是否隐藏授权
    isLog: app.globalData.isLog,//是否登录
  },
  returns: function () {
    wx.navigateBack();
  },
  /**
   * 登录后加载
   * 
  */
  onLoadFun: function (e) {
    this.setData({ isLog: true });
    this.getUserInfo();
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
      wx.navigateTo({ url:  `/pages/activity/goods_bargain_details/index?id=${item.activity.id}`});
    } else if (item.activity && item.activity.type === "3") {
      wx.navigateTo({
        url: `/pages/activity/goods_combination_details/index?id=${item.activity.id}`
      });
    } else {
      wx.navigateTo({ url: `/pages/goods_details/index?id=${item.id}` });
    }
  },
  
  /*
  * 获取用户信息
  */
  getUserInfo: function () {
    var that = this;
    getUserInfo().then(res => {
      that.setData({
        uid: res.data.uid
      });
    });
  },
  
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this;
    if (!options.id) return app.Tips({ title: '缺少参数无法查看商品' }, { tab: 3, url: 1 });
    this.setData({ id: options.id });
    this.getGoodsDetails();
  },

//获取所有优惠信息
 moreCoupon:function(e){
  let item = e.currentTarget.dataset.items;
  wx.navigateTo({ url: '/pages/more_coupon/index?id='+this.data.id });
},

  
  /**
   * 获取产品详情
   * 
  */
  getGoodsDetails: function () {
    var that = this;
    getStoreDetail(that.data.id).then(res => {
      var storeInfo = res.data.storeInfo;
      var goodList = res.data.good_list || [];
      var labelList = res.data.label_list || [];
      var couponList = res.data.coupon_list || [];
      that.setData({
        storeInfo: storeInfo,
        goodList: goodList,
        labelList: labelList,
        couponList: couponList
      });
    }).catch(err => {
      //状态异常返回上级页面
      return app.Tips({ title: err.toString() }, { tab: 3, url: 1 });
    })
  },
  goPages: function (e) {
    wx.navigateTo({ url: 'pages/goods_details/index?id=' + e.currentTarget.dataset.id });
  },
  /**
   * 拨打电话
  */
  makePhone: function () {
    wx.makePhoneCall({
      phoneNumber: this.data.storeInfo.phone
    })
  },
  /**
   * 打开地图
   * 
  */
  showMaoLocation: function () {
    if (!this.data.storeInfo.latitude || !this.data.storeInfo.longitude) return app.Tips({ title: '缺少经纬度信息无法查看地图！' });
    wx.openLocation({
      latitude: parseFloat(this.data.storeInfo.latitude),
      longitude: parseFloat(this.data.storeInfo.longitude),
      scale: 8,
      name: this.data.storeInfo.name,
      address: this.data.storeInfo.address + this.data.storeInfo.detailed_address,
      success: function () {

      },
    });
  },
  
})