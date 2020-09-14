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
      'title': '商家优惠详情详情'
    },
    id: 0,//商家id
    uid: 0,//用户uid
    storeInfo: {},
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

  /**
   * 获取产品详情
   * 
  */
  getGoodsDetails: function () {
    var that = this;
    getStoreDetail(that.data.id).then(res => {
      var storeInfo = res.data.storeInfo;
      var couponList = res.data.acoupon_List || [];
      that.setData({
        storeInfo: storeInfo,
        couponList: couponList
      });
    }).catch(err => {
      //状态异常返回上级页面
      return app.Tips({ title: err.toString() }, { tab: 3, url: 1 });
    })
  },
})