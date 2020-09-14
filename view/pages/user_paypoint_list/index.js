// pages/bill-details/index.js
import { getPayGiveLog,getPayPointLog,getPayRepointLog } from '../../api/user.js';
const app=getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '账单明细',
      'color':true,
      'class':'0'
    },
    loadTitle:'加载更多',
    loading:false,
    loadend:false,
    page:1,
    limit:10,
    type:0,
    recodeType:0,
    userBillList:[],
  },

  /**
   * 授权回调
  */
  onLoadFun:function(){
    this.getUserBillList();
  },
  onShow: function () {
    var recodeType = this.data.recodeType;
    if (recodeType == 1) {
      this.setData({ 'parameter.title': '购物积分明细'});
    } else if (recodeType == 2) {
      this.setData({ 'parameter.title': '消费积分明细'});
    } else if (recodeType == 3) {
      this.setData({ 'parameter.title': '重消积分明细'});
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
      this.getUserBillList();
    }
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({ type: options.type || 0});
    this.setData({ recodeType: options.recodeType || 0});
  },

  /**
   * 获取账户明细
  */
  getUserBillList:function(){
    var that=this;
    if (that.data.loadend) return;
    if (that.data.loading) return;
    that.setData({ loading: true, loadTitle: "" });
    var data = {
      page: that.data.page,
      limit: that.data.limit
      }
    if(that.data.recodeType==1){
        getPayGiveLog(data, that.data.type).then(function(res){
          var list=res.data,loadend=list.length < that.data.limit;
          that.data.userBillList = app.SplitArray(list,that.data.userBillList);
          that.setData({
            userBillList:that.data.userBillList,
            loadend:loadend,
            loading:false,
            loadTitle:loadend ? "哼😕~我也是有底线的~": "加载更多",
            page:that.data.page+1,
          });
        },function(res){
          that.setData({loading:false,loadTitle:'加载更多'});
        });
    }else if(that.data.recodeType==2){
      getPayPointLog(data, that.data.type).then(function(res){
            var list=res.data,loadend=list.length < that.data.limit;
            that.data.userBillList = app.SplitArray(list,that.data.userBillList);
            that.setData({
              userBillList:that.data.userBillList,
              loadend:loadend,
              loading:false,
              loadTitle:loadend ? "哼😕~我也是有底线的~": "加载更多",
              page:that.data.page+1,
            });
          },function(res){
            that.setData({loading:false,loadTitle:'加载更多'});
          });
      }else if(that.data.recodeType==3){
        getPayRepointLog(data, that.data.type).then(function(res){
          var list=res.data,loadend=list.length < that.data.limit;
          that.data.userBillList = app.SplitArray(list,that.data.userBillList);
          that.setData({
            userBillList:that.data.userBillList,
            loadend:loadend,
            loading:false,
            loadTitle:loadend ? "哼😕~我也是有底线的~": "加载更多",
            page:that.data.page+1,
          });
        },function(res){
          that.setData({loading:false,loadTitle:'加载更多'});
        });
    }
  },
  /**
   * 切换导航
  */
  changeType:function(e){
    this.setData({ type: e.currentTarget.dataset.type,loadend:false,page:1,userBillList:[]});
    this.getUserBillList();
  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    this.getUserBillList();
  },

})