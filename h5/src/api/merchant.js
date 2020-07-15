import request from "@utils/request";

// 门店管理相关的

/**
 * 申请商家
 */
export function postMerchantApply(data) {
    return request.post("/merchant/apply", data, { login: true });
}

/**
 * 取得商家当前的状态
 * @param data
 * @returns {*}
 */
export function getMerHome(data) {
    return request.get("/merchant/home", data, { login: true });
}

export function getServiceList(data) {
    return request.get("/merchant/service", data || {});
}

export function postServiceAdd(data) {
    return request.post("/merchant/serviceAdd", data || {});
}

/*
 * 删除地址
 * */
export function getServiceRemove(id) {
    return request.post("/merchant/serviceDel", { id: id });
}
export function setServiceAdmin(id,status) {
    return request.post("/merchant/serviceAdmin", {  id,status });
}
export function setServiceCheck(id,status) {
    return request.post("/merchant/serviceCheck", {  id,status });
}


export function getStoreList(data) {
    return request.get("/merchant/store", data || {});
}

export function getStoreInfo(data) {
    return request.get("/merchant/storeInfo", data || {});
}

export function postStoreAdd(data) {
    return request.post("/merchant/storeAdd", data || {});
}

/**
 * 商品统计数据
 * @returns {*}
 */
export function getProductData() {
  return request.get("/merchant/data");
}

/**
 * 订单列表
 * @returns {*}
 */
export function getProductList(data) {
  return request.get("/merchant/plist", data);
}



