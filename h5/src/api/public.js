import request from "@utils/request";

/**
 * 首页
 * @returns {*}
 */
export function getHomeData() {
  return request.get("index", {}, { login: false });
}

/**
 * 首页周边的店铺
 * @returns {*}
 */
export function getNearStoreData() {
  return request.get("getNearStoreData", {}, { login: false });
}

/**
 * 同城推荐商品
 */
export function goodHuiListApi(data) {
  return request.get("thgoods_list", data, { login: false });
}

/**
 * 同城商品
 */
export function goodListApi(data) {
  return request.get("tgoods_list", data, { login: false });
}


/**
 * 同城商品
 */
export function goodIndexListApi(data) {
  return request.get("txgoods_list", data, { login: false });
}

/**
 * 文章 轮播列表
 * @returns {*}
 */
export function getArticleBanner() {
  return request.get("/article/banner/list", {}, { login: false });
}

/**
 * 文章分类列表
 * @returns {*}
 */
export function getArticleCategory() {
  return request.get("/article/category/list", {}, { login: false });
}

/**
 * 文章 热门列表
 * @returns {*}
 */
export function getArticleHotList() {
  return request.get("/article/hot/list", {}, { login: false });
}

/**
 * 文章列表
 * @returns {*}
 */
export function getArticleList(q, cid) {
  return request.get("/article/list/" + cid, q, { login: false });
}

/**
 * 分享
 * @returns {*}
 */
export function getShare() {
  return request.get("/share", {}, { login: false });
}

/**
 * 文章详情
 * @returns {*}
 */
export function getArticleDetails(id) {
  return request.get("/article/details/" + id, {}, { login: false });
}

/**
 * 获取微信sdk配置
 * @returns {*}
 */
export function getWechatConfig() {
  return request.get(
    "/wechat/config",
    { url: document.location.href },
    { login: false }
  );
}

/**
 * 获取微信sdk配置
 * @returns {*}
 */
export function wechatAuth(code, spread, login_type) {
  return request.get(
    "/wechat/auth",
    { code, spread, login_type },
    { login: false }
  );
}
/**
 * 获取快递公司
 * @returns {*}
 */
export function getLogistics() {
  return request.get("/logistics", {}, { login: false });
}

/**
 * 获取登陆logo
 * @returns {*}
 */
export function getLogo(type) {
  return request.get("/wechat/get_logo", { type: type }, { login: false });
}

/**
 * 获取图片base64
 * @retins {*}
 * */
export function imageBase64(image, code) {
  return request.post(
    "/image_base64",
    { image: image, code: code },
    { login: false }
  );
}

/**
 * 获取关注海报
 * @returns {*}
 */
export function follow() {
  return request.get("/wechat/follow", {}, { login: false });
}

/**
 * 获取城市列表
 * @returns {*}
 */
export function getCity() {
  return request.get("/city_list", {}, { login: false });
}
