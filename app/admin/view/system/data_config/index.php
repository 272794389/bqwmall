<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link href="{__FRAME_PATH}css/font-awesome.min.css" rel="stylesheet">
    <link href="{__ADMIN_PATH}plug/umeditor/themes/default/css/umeditor.css" type="text/css" rel="stylesheet">
    <script type="text/javascript" src="{__ADMIN_PATH}plug/umeditor/third-party/jquery.min.js"></script>
    <script type="text/javascript" src="{__ADMIN_PATH}plug/umeditor/third-party/template.min.js"></script>
    <script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/umeditor/umeditor.config.js"></script>
    <script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/umeditor/umeditor.min.js"></script>
    <script type="text/javascript" src="{__ADMIN_PATH}plug/umeditor/lang/zh-cn/zh-cn.js"></script>
    <link rel="stylesheet" href="/static/plug/layui/css/layui.css">
    <script src="/static/plug/layui/layui.js"></script>
    <script src="{__PLUG_PATH}vue/dist/vue.min.js"></script>
    <script src="/static/plug/axios.min.js"></script>
    <script src="{__MODULE_PATH}widget/aliyun-oss-sdk-4.4.4.min.js"></script>
    <script src="{__MODULE_PATH}widget/cos-js-sdk-v5.min.js"></script>
    <script src="{__MODULE_PATH}widget/qiniu-js-sdk-2.5.5.js"></script>
    <script src="{__MODULE_PATH}widget/plupload.full.min.js"></script>
    <script src="{__MODULE_PATH}widget/videoUpload.js"></script>
    <style>
        .layui-form-item {
            margin-bottom: 0px;
        }

        .pictrueBox {
            display: inline-block !important;
        }

        .pictrue {
            width: 60px;
            height: 60px;
            border: 1px dotted rgba(0, 0, 0, 0.1);
            margin-right: 15px;
            display: inline-block;
            position: relative;
            cursor: pointer;
        }

        .pictrue img {
            width: 100%;
            height: 100%;
        }

        .upLoad {
            width: 58px;
            height: 58px;
            line-height: 58px;
            border: 1px dotted rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.02);
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .rulesBox {
            display: flex;
            flex-wrap: wrap;
            margin-left: 10px;
        }

        .layui-tab-content {
            margin-top: 15px;
        }

        .ml110 {
            margin: 18px 0 4px 110px;
        }

        .rules {
            display: flex;
        }

        .rules-btn-sm {
            height: 30px;
            line-height: 30px;
            font-size: 12px;
            width: 109px;
        }

        .rules-btn-sm input {
            width: 79% !important;
            height: 84% !important;
            padding: 0 10px;
        }

        .ml10 {
            margin-left: 10px !important;
        }

        .ml40 {
            margin-left: 40px !important;
        }

        .closes {
            position: absolute;
            left: 86%;
            top: -18%;
        }
        .red {
            color: red;
        }
        .layui-input-block .layui-video-box{
            width: 22%;
            height: 180px;
            border-radius: 10px;
            background-color: #707070;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        .layui-input-block .layui-video-box i{
            color: #fff;
            line-height: 180px;
            margin: 0 auto;
            width: 50px;
            height: 50px;
            display: inherit;
            font-size: 50px;
        }
        .layui-input-block .layui-video-box .mark{
            position: absolute;
            width: 100%;
            height: 30px;
            top: 0;
            background-color: rgba(0,0,0,.5);
            text-align: center;
        }
        .store_box{
            display: flex;
        }
        .info{
            color: #c9c9c9;
            padding-left: 10px;
            line-height: 30px;
        }
    </style>
</head>
<body>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15"  id="app" v-cloak="">
        <div class="layui-card">
            <div class="layui-card-body">
                <form class="layui-form" action="" v-cloak="">
                    <div class="layui-tab layui-tab-brief" lay-filter="docTabBrief">
                        <ul class="layui-tab-title">
                            <li class="layui-this" lay-id='1'>参数设置</li>
                        </ul>
                        <div class="layui-tab-content">
                            <div class="layui-tab-item layui-show">
                                <div class="layui-row layui-col-space15">
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">第一代推荐奖励比例%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="rec_f" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.rec_f">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">第二代推荐奖励比例%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="rec_s" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.rec_s">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">第三代推荐奖励比例%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="rec_t" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.rec_t">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">综合费率%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="fee_rate" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.fee_rate">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">重消费率%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="repeat_rate" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.repeat_rate">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">商家推荐人奖励比例%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="shop_rec" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.shop_rec">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">省级代理商奖励比例%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="agent_pro" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.agent_pro">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">城市代理商奖励比例%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="agent_city" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.agent_city">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">地区代理商奖励比例%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="agent_district" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.agent_district">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">省级总监奖励%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="inspect_pro" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.inspect_pro">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">城市总监奖励比例%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="inspect_city" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.inspect_city">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">地区总监奖励比例%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="inspect_district" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.inspect_district">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">社区服务中心%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="plat_rate" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" value="10">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">提现手续费费率%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="withdraw_fee" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.withdraw_fee">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label" style="width:180px;">进货成本比例%</label>
                                                <div class="layui-input-block">
                                                    <input style="width: 40%" type="text" name="plat_rate" lay-verify="title" autocomplete="off"
                                                           placeholder="请输入值" class="layui-input" v-model="formData.plat_rate">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="layui-tab-content">
                            <div class="layui-row layui-col-space15">
                                <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                    <div class="grid-demo grid-demo-bg1">
                                        <div class="layui-form-item" v-if="id">
                                            <button class="layui-btn layui-btn-primary" type="button" @click="handleSubmit()">保存</button>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    var id = {$id};
    new Vue({
        el: '#app',
        data: {
            id:id,
            formData: {
            	rec_f: 0,
            	rec_s: 0,
            	rec_t: 0,
            	fee_rate:0,
            	repeat_rate: 0,
            	shop_rec: 0,
            	agent_pro: 0,
            	agent_city: 0,
            	agent_district: 0,
            	inspect_pro: 0,
            	inspect_city: 0,
            	inspect_district: 0,
            	withdraw_fee: 0,
            	plat_rate: 0
            },
            
            form: null,//layui.form
            layTabId: 1,
            ruleBool: id ? true : false,
        },
        watch:{
           
        },
        methods: {
        	U: function (opt) {
                var m = opt.m || 'admin', c = opt.c || window.controlle || '', a = opt.a || 'index', q = opt.q || '',
                    p = opt.p || {};
                var params = Object.keys(p).map(function (key) {
                    return key + '/' + p[key];
                }).join('/');
                var gets = Object.keys(q).map(function (key) {
                    return key+'='+ q[key];
                }).join('&');

                return '/' + m + '/' + c + '/' + a + (params == '' ? '' : '/' + params) + (gets == '' ? '' : '?' + gets);
            },
            /**
             * 提示
             * */
            showMsg: function (msg, success) {
                layui.use(['layer'], function () {
                    layui.layer.msg(msg, success);
                });
            },
            /**
             * 获取商品信息
             * */
            getDataInfo: function () {
                var that = this;
                that.requestGet(that.U({c:"system.DataConfig",a:'get_dateconfig_info',q:{id:that.id}})).then(function (res) {
                    var dataInfo = res.data.dataInfo || {};
                    if(dataInfo.id && that.id){
                        that.$set(that,'formData',dataInfo);
                    }
                    that.init();
                }).catch(function (res) {
                    that.showMsg(res.msg);
                })
            },
            init: function () {
                var that = this;
                
            },
            requestPost: function (url, data) {
                return new Promise(function (resolve, reject) {
                    axios.post(url, data).then(function (res) {
                        if (res.status == 200 && res.data.code == 200) {
                            resolve(res.data)
                        } else {
                            reject(res.data);
                        }
                    }).catch(function (err) {
                        reject({msg:err})
                    });
                })
            },
            requestGet: function (url) {
                return new Promise(function (resolve, reject) {
                    axios.get(url).then(function (res) {
                        if (res.status == 200 && res.data.code == 200) {
                            resolve(res.data)
                        } else {
                            reject(res.data);
                        }
                    }).catch(function (err) {
                        reject({msg:err})
                    });
                })
            },
            handleSubmit:function () {
                var that = this;
                that.requestPost(that.U({c:'system.DataConfig',a:'save',p:{id:that.id}}),that.formData).then(function (res) {
                    that.confirm();
                }).catch(function (res) {
                    that.showMsg(res.msg);
                });
            },
            confirm: function(){
                var that = this;
                layui.use(['layer'], function () {
                    var layer = layui.layer;
                    layer.confirm(that.id ? '设置成功' : '设置成功', {
                        btn: ['确定'] //按钮
                    }, function(){
                      	 location.reload();
                    });
                });
            },
           
        },
        mounted: function () {
            var that = this;
            that.getDataInfo(); 
        }
    });
</script>
</body>
</html>