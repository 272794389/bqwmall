// pages/commission-details/index.js

import { getPayOrderInfo, getPayInfo} from '../../api/user.js';

Page({

  /**
   * 页面的初始数据
   */
  data: {
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '消费记录',
      'color': true,
      'class': '0'
    },
    name:'',
    types: 3,
    page:0,
    limit:8,
    recordList:[],
    recordCount:0,
    status:false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
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
    this.getRecordList();
    this.getRecordListCount();
  },
  /**
   * 获取余额使用记录
   */
  getRecordList: function () {
    var that = this;
    var page = that.data.page;
    var limit = that.data.limit;
    var types = that.data.types;
    var status = that.data.status;
    var recordList = that.data.recordList;
    var recordListNew = [];
    if (status == true) return ;
    getPayOrderInfo(types,{page:page,limit:limit}).then(res=>{
      var len = res.data.length;
      var recordListData = res.data;
      recordListNew = recordList.concat(recordListData);
      that.setData({ status: limit > len, page: limit + page, recordList: recordListNew });
    });
  },
  getRecordListCount:function(){
    var that = this;
    getPayInfo().then(res=>{
      that.setData({ recordCount: res.data.commissionCount });
    });
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

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    this.getRecordList();
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})