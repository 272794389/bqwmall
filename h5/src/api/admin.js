import request from "@utils/request";

/**
 * 统计数据
 */
export function getStatisticsInfo() {
  return request.get("/admin/order/statistics", {}, { login: true });
}

/*
 * 业务员订单统计
 * */
export function getMyOrderStatistics(id) {
  return request.get("/admin/order/mystaticstics/" + id, {}, { login: false });
}

/**
 * 订单月统计
 */
export function getStatisticsMonth(where) {
  return request.get("/admin/order/data", where, { login: true });
}

/**
 * 订单月统计
 */
export function getMyStatisticsMonth(where) {
  return request.get("/admin/order/mydata", where, { login: true });
}

export function getMyPayStatisticsMonth(where) {
	  return request.get("/admin/order/mypaydata", where, { login: true });
	}


/**
 * 订单月统计
 */
export function getAdminOrderList(where) {
  return request.get("/admin/order/list", where, { login: true });
}
/**
 * 订单改价
 */
export function setAdminOrderPrice(data) {
  return request.post("/admin/order/price", data, { login: true });
}
/**
 * 订单备注
 */
export function setAdminOrderRemark(data) {
  return request.post("/admin/order/remark", data, { login: true });
}
/**
 * 订单详情
 */
export function getAdminOrderDetail(orderId) {
  return request.get("/admin/order/detail/" + orderId, {}, { login: true });
}
/**
 * 订单发货信息获取
 */
export function getAdminOrderDelivery(orderId) {
  return request.get(
    "/admin/order/delivery/gain/" + orderId,
    {},
    { login: true }
  );
}

/**
 * 订单发货保存
 */
export function setAdminOrderDelivery(data) {
  return request.post("/admin/order/delivery/keep", data, { login: true });
}
/**
 * 订单统计图
 */
export function getStatisticsTime(data) {
  return request.get("/admin/order/time", data, { login: true });
}
/**
 * 线下付款订单确认付款
 */
export function setOfflinePay(data) {
  return request.post("/admin/order/offline", data, { login: true });
}
/**
 * 订单确认退款
 */
export function setOrderRefund(data) {
  return request.post("/admin/order/refund", data, { login: true });
}
