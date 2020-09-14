import { getStoreDetail,shopPay} from '../../api/store.js';
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
      'title': '向商家付款'
    },
    id: 0,//商家id
    uid: 0,//用户uid
    storeInfo: {},
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
   //扫码携带参数处理
    if (options.scene) {
      var value = util.getUrlParams(decodeURIComponent(options.scene));
      if (value.id) options.id = value.id;
      //记录推广人uid
      if (value.pid) app.globalData.spid = value.pid;
    }
    if (!options.id) return app.Tips({ title: '缺少参数进行支付' }, { tab: 3, url: 1 });
    this.setData({ id: options.id });
    //记录推广人uid
    if (options.spid) app.globalData.spid = options.spid;
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
      that.setData({
        storeInfo: storeInfo
      });
    }).catch(err => {
      //状态异常返回上级页面
      return app.Tips({ title: err.toString() }, { tab: 3, url: 1 });
    })
  },
  /**
   * 提交用户添加地址
   * 
  */
 formSubmit:function(e){
  var that = this, value = e.detail.value;
  if (value.amount<0.1) return app.Tips({title:'请填写消费金额'});
  value.store_id=that.data.id;
  shopPay(value).then(res=>{
    if (res.data !== undefined&&res.data.order_id>0) {
      setTimeout(function () {
        wx.navigateTo({
          url: '/pages/shopset/index?id=' + res.data.order_id
        });
      }, 1000);
    } else {
      app.Tips({ title: '操作失败'});
    }
  }).catch(err=>{
    console.log('fuck you lalala')
    return app.Tips({title:err});
  })
},
})