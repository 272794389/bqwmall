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
    now_money: 0,
    orderStatusSum: 0,
    recharge: 0,
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
    that.setData({ 
      now_money: res.data.now_money, 
      orderStatusSum: res.data.orderStatusSum, 
      recharge: res.data.recharge
    });
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
   * 获取活动可参与否
  */
  get_activity:function(){
    var that=this;
    userActivity().then(res=>{
      that.setData({ activity: res.data });
    })
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
    if (app.globalData.isLog && this.data.isClose) {
      this.getUserInfo();
      this.get_host_product();
      this.get_activity();
    }
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    this.setData({ isClose: true });
  },
})