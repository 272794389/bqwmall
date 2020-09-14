// pages/my-account/index.js

import { getProductHot } from '../../api/store.js';
import { openRechargeSubscribe } from '../../utils/SubscribeMessage.js';
import { getUserInfo, getBalance } from '../../api/user.js';

const app=getApp();

Page({

  /**
   * 页面的初始数据
   */
  data: {
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '我的账户',
      'color': false,
    },
    type:0,
    now_money: 0,
    out_amount: 0,
    in_amount: 0,
    userInfo:{},
    host_product:[],
    isClose:false,
    recharge_switch:0,
  },

  /**
   * 登录回调
  */
  onLoadFun:function(){
    this.getUserInfo();
    this.get_host_product();
    this.getBalance();
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({ type: options.type });
  
  },

  openSubscribe:function(e){
    let page = e.currentTarget.dataset.url;
    wx.showLoading({
      title: '正在加载',
    })
    openRechargeSubscribe().then(res => {
      wx.hideLoading();
      wx.navigateTo({
        url: page,
      });
    }).catch(() => {
      wx.hideLoading();
    });
  },

  
   /**
   * 获取余额详情
  */
 getBalance:function(){
  let that = this;
  getBalance().then(res=>{
    if(that.data.type==1){//购物积分
      that.setData({ 
        now_money:res.data.give_point,
        in_amount:res.data.in_givepoint,
        out_amount:res.data.out_givepoint
      });
    }else if(that.data.type==2){//消费积分
      that.setData({ 
        now_money:res.data.pay_point,
        in_amount:res.data.in_paypoint,
        out_amount:res.data.out_paypoint
      });
    }else if(that.data.type==3){//重复消费积分
      that.setData({ 
        now_money:res.data.repeat_point,
        in_amount:res.data.in_repoint,
        out_amount:res.data.out_repoint
      });
    }
  });
},

  /**
   * 获取用户详情
  */
  getUserInfo:function(){
    let that = this;
    getUserInfo().then(res=>{
      that.setData({ 
        userInfo: res.data, 
        recharge_switch: res.data.recharge_switch
      });
    });
  },
  
  /**
   * 获取我的推荐
  */
  get_host_product:function(){
    var that=this;
    getProductHot().then(res=>{
      that.setData({ host_product: res.data });
    })
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    var type = this.data.type;
    if (type == 1) {
      this.setData({ 'parameter.title': '购物积分记录'});
    } else if (type == 2) {
      this.setData({ 'parameter.title': '消费积分记录'});
    } else if (type == 3) {
      this.setData({ 'parameter.title': '重消积分记录'});
    } else {
      wx.showToast({
        title: '参数错误',
        icon: 'none',
        duration: 1000,
        mask: true,
        success: function (res) { setTimeout(function () { wx.navigateBack({ delta: 1, }) }, 1200) },
      });
    }
    if (app.globalData.isLog && this.data.isClose) {
      this.getUserInfo();
      this.get_host_product();
    }
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    this.setData({ isClose: true });
  },
})