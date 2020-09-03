export default [
  {
    path: "/customer/index",
    name: "OrderIndex",
    meta: {
      title: "订单首页",
      keepAlive: true,
      auth: true
    },
    component: () => import("@views/orderAdmin/OrderIndex.vue")
  },
  {
    path: "/customer/myorder/:check_id",
    name: "AdminMyOrder",
    meta: {
      title: "业务员订单统计",
      keepAlive: true,
      auth: true
    },
    component: () => import("@views/orderAdmin/AdminMyOrder.vue")
  },
  {
    path: "/customer/orders/:types?",
    name: "AdminOrderList",
    meta: {
      title: "订单列表",
      keepAlive: true,
      auth: true
    },
    component: () => import("@views/orderAdmin/AdminOrderList.vue")
  },
  {
    path: "/customer/delivery/:oid?",
    name: "GoodsDeliver",
    meta: {
      title: "订单发货",
      keepAlive: true,
      auth: true
    },
    component: () => import("@views/orderAdmin/GoodsDeliver.vue")
  },
  {
    path: "/customer/orderdetail/:oid?/:goname?",
    name: "AdminOrder",
    meta: {
      title: "订单详情",
      keepAlive: false,
      auth: true
    },
    component: () => import("@views/orderAdmin/AdminOrder.vue")
  },
  {
    path: "/customer/statistics/:type/:time?",
    name: "Statistics",
    meta: {
      title: "订单数据统计",
      keepAlive: true,
      auth: true
    },
    component: () => import("@views/orderAdmin/Statistics.vue")
  },
  {
    path: "/order/order_cancellation",
    name: "OrderCancellation",
    meta: {
      title: "订单核销",
      keepAlive: true,
      auth: true,
      backgroundColor: "#fff"
    },
    component: () => import("@views/orderAdmin/OrderCancellation.vue")
  },
  {
    path: "/merchant/apply",
    name: "MerchantApply",
    meta: {
      title: "商家申请",
      keepAlive: true,
      auth: true,
      backgroundColor: "#fff"
    },
    component: () => import("@views/user/merchant/MerApply.vue")
  },
  {
	    path: "/merchant/ermapay",
	    name: "ErmaPay",
	    meta: {
	      title: "收款二维码",
	      keepAlive: true,
	      auth: true
	    },
	    component: () => import("@views/user/merchant/MerMa.vue")
},
  {
    path: "/merchant/home",
    name: "MerchantHome",
    meta: {
      title: "商家首页",
      keepAlive: true,
      auth: true,
    },
    component: () => import("@views/user/merchant/MerHome.vue")
  },
  {
    path: "/merchant/service/:id",
    name: "MerchantService",
    meta: {
      title: "客服管理",
      keepAlive: true,
      auth: true,
    },
    component: () => import("@views/user/merchant/MerService.vue")
  },
  {
    path: "/merchant/store/:id",
    name: "MerchantStore",
    meta: {
      title: "门店管理",
      keepAlive: true,
      auth: true,
    },
    component: () => import("@views/user/merchant/MerStore.vue")
  },
  {
    path: "/merchant/storeAdd/:id",
    name: "MerchantStoreAdd",
    meta: {
      title: "门店新增",
      keepAlive: true,
      auth: true,
      backgroundColor: "#fff"
    },
    component: () => import("@views/user/merchant/AddStore.vue")
  },
  {
    path: "/merchant/serviceAdd/:id",
    name: "MerchantService",
    meta: {
      title: "客服管理",
      keepAlive: true,
      auth: true,
      backgroundColor: "#fff"
    },
    component: () => import("@views/user/merchant/AddService.vue")
  },
  {
	    path: "/merchant/plist/:type?",
	    name: "MerchantProduct",
	    meta: {
	      title: "商品管理",
	      keepAlive: false,
	      auth: true
	    },
	    component: () => import("@views/user/merchant/MerProduct.vue")
	  },
];
