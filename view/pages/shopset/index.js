import { postPayOrderComputed, getOrder,payOrder} from '../../api/order.js';
import { openPaySubscribe } from '../../utils/SubscribeMessage.js';
import { getUserInfo} from '../../api/user.js';
import { CACHE_LONGITUDE, CACHE_LATITUDE } from '../../config.js';
const app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    textareaStatus:true,
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '提交订单',
      'color': true,
      'class': '0'
    },
    //支付方式
    cartArr: [
      { "name": "微信支付", "icon": "icon-weixin2", value: 'weixin', title: '微信快捷支付' },
      { "name": "余额支付", "icon": "icon-icon-test", value: 'yue',title:'可用余额:'},
    ],
    id:0,
    payType:'weixin',//支付方式
    openType:1,//优惠券打开方式 1=使用
    active:0,//支付方式切换
    userInfo:{},//用户信息
    coupon_price:0,//优惠券抵扣金额
    useIntegral:false,//是否使用积分
    useCoupon:false,//是否使用抵扣券
    usePayIntegral:false,//是否使用消费积分
    isClose:false,
    toPay:false,//修复进入支付时页面隐藏从新刷新页面
    orderPrice: {}
  },
  /**
   * 授权回调事件
   * 
  */
  onLoadFun:function(){
    this.getUserInfo();
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    let pages = getCurrentPages();
    let currPage = pages[pages.length - 1]; //当前页面
    if (currPage.data.storeItem){
      let json = currPage.data.storeItem;
    }
  },
  
  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    this.setData({ isClose: true });
  },
  /**
   * 使用购物积分抵扣
  */
  ChangeIntegral:function(){
    this.setData({useIntegral:!this.data.useIntegral,usePayIntegral:false,useCoupon:false});
    this.computedPrice();
  },
  /**
   * 使用抵扣券抵扣
  */
 ChangeCoupon:function(){
  this.setData({useCoupon:!this.data.useCoupon,usePayIntegral:false,useIntegral:false});
  this.computedPrice();
},

/**
   * 使用消费积分抵扣
  */
 ChangePayIntegral:function(){
  this.setData({usePayIntegral:!this.data.usePayIntegral,useCoupon:false,useIntegral:false});
  this.computedPrice();
},
  
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    if (!options.id) return app.Tips({ title:'获取订单失败'},{tab:3,url:1});
    this.setData({ 
      id: options.id || 0
    });
    this.getOrderInfo();
    this.getUserInfo();
  },

  /*
  * 获取用户信息
  */
 getUserInfo: function () {
  var that = this;
  getUserInfo().then(res => {
    that.setData({
      userInfo: res.data
    });
  });
},

getOrderInfo:function(){
  var that = this;
  getOrder(that.data.id).then(res => {
    that.setData({
      orderPrice: res.data.orderinfo
    });
  });
},

  /**
   * 获取当前订单详细信息
   * 
  */
 computedPrice:function(){
    var that = this, data={};
    data={
      orderid:that.data.id,
      useIntegral: that.data.useIntegral ? 1 : 0,
      useCoupon: that.data.useCoupon ? 1 : 0,
      usePayIntegral: that.data.usePayIntegral ? 1 : 0
    }
    postPayOrderComputed(data).then(res=>{
      that.setData({
        orderPrice: res.data.orderinfo
      });
    }).catch(err=>{
      return app.Tips({ title: err }, { tab: 3, url: 1 });
    });
  },
  
  payItem:function(e){
    var that = this;
    var active = e.currentTarget.dataset.index;
    that.setData({
      active: active,
      animated: true,
      payType: that.data.cartArr[active].value,
    });
    that.computedPrice();
  },
  
  SubOrder:function(e){
    var that = this, data={};
    if (!this.data.payType) return app.Tips({title:'请选择支付方式'});
    data={
      payType: that.data.payType,
      'from':'routine',
      order_id:that.data.id
    };
    if (data.payType == 'yue' && parseFloat(that.data.userInfo.now_money) < parseFloat(that.data.orderPrice.pay_amount)) return app.Tips({title:'余额不足！'});
    wx.showLoading({ title: '订单支付中'});
    openPaySubscribe().then(()=>{
      payOrder(data).then(res=>{
        var status = res.data.status,  jsConfig = res.data.result.jsConfig,
          goPages = '/pages/payorder_list/index';
        switch (status) {
          case 'ORDER_EXIST': case 'EXTEND_ORDER': case 'PAY_ERROR':
            wx.hideLoading();
            return app.Tips({ title: res.msg });
            break;
          case 'SUCCESS':
            wx.hideLoading();
            return app.Tips({ title: res.msg, icon: 'success' }, { tab: 5, url: goPages });
            break;
          case 'WECHAT_PAY':
            that.setData({ toPay: true });
            wx.requestPayment({
              timeStamp: jsConfig.timestamp,
              nonceStr: jsConfig.nonceStr,
              package: jsConfig.package,
              signType: jsConfig.signType,
              paySign: jsConfig.paySign,
              success: function (res) {
                wx.hideLoading();
                return app.Tips({ title: '支付成功', icon: 'success' }, { tab: 5, url: goPages });
              },
              fail: function (e) {
                wx.hideLoading();
                return app.Tips({ title: '取消支付' });
              },
              complete: function (e) {
                wx.hideLoading();
                //关闭当前页面跳转至订单状态
                if (res.errMsg == 'requestPayment:cancel') return app.Tips({ title: '取消支付' });
              },
            })
            break;
          case 'PAY_DEFICIENCY':
            wx.hideLoading();
            //余额不足
            return app.Tips({ title: '余额不足' });
            break;
        }
      }).catch(err=>{
        wx.hideLoading();
        return app.Tips({title:err});
      });
    });
  }
})