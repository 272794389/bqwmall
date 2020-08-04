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
            <div class="layui-card-header">
                <span class="">编辑商家详情</span>
                <button style="margin-left: 20px" type="button" class="layui-btn layui-btn-primary layui-btn-xs" @click="goBack">返回列表</button>
            </div>
            <div class="layui-card-body">
                <form class="layui-form" action="" v-cloak="">
                    <div class="layui-tab layui-tab-brief" lay-filter="docTabBrief">
                        <ul class="layui-tab-title">
                            <li class="layui-this" lay-id='1'>基础信息</li>
                            <li lay-id='2'>商品详情</li>
                        </ul>
                        <div class="layui-tab-content">
                            <div class="layui-tab-item layui-show">
                                <div class="layui-row layui-col-space15">
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">商品封面图<i class="red">*</i></label>
                                                <div class="pictrueBox">
                                                    <div class="pictrue" v-if="formData.image" @click="uploadImage('image')">
                                                        <img :src="formData.image"></div>
                                                    <div class="upLoad" @click="uploadImage('image')" v-else>
                                                        <i class="layui-icon layui-icon-camera" class="iconfont"
                                                           style="font-size: 26px;"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                        <div class="grid-demo grid-demo-bg1">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">商品轮播图<i class="red">*</i></label>
                                                <div class="pictrueBox pictrue" v-for="(item,index) in formData.slider_image">
                                                    <img :src="item">
                                                    <i class="layui-icon closes" @click="deleteImage('slider_image',index)">&#x1007</i>
                                                </div>
                                                <div class="pictrueBox">
                                                    <div class="upLoad" @click="uploadImage('slider_image')"
                                                         v-if="formData.slider_image.length <= rule.slider_image.maxLength">
                                                        <i class="layui-icon layui-icon-camera" class="iconfont"
                                                           style="font-size: 26px;"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <div class="layui-row layui-col-space15">
                                    <textarea type="text/plain" name="introduction" id="myEditor" style="width:100%;">{{formData.introduction}}</textarea>
                                </div>
                            </div>
                            
                        <div class="layui-tab-content">
                            <div class="layui-row layui-col-space15">
                                <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
                                    <div class="grid-demo grid-demo-bg1">
                                        <div class="layui-form-item" v-if="id">
                                            <button class="layui-btn layui-btn-primary" type="button" @click="handleSubmit()">保存</button>
                                            <button class="layui-btn layui-btn-primary" type="button" @click="back" v-if="layTabId != 1">上一步</button>
                                        </div>
                                        <div class="layui-form-item" v-else>
                                            <button class="layui-btn layui-btn-primary" type="button" @click="back" v-if="layTabId != 1">上一步</button>
                                            <button class="layui-btn layui-btn-normal" type="button" v-if="layTabId == 3" @click="handleSubmit()">提交</button>
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
            upload:{
                videoIng:false
            },
            formData: {
                image: '',
                slider_image: [],
                introduction: '',
            },
            videoLink:'',
           
            //多属性header头
            formHeader:[],
            rule: { //多图选择规则
                slider_image: {
                    maxLength: 5
                }
            },
            ruleList:[],
            ruleIndex:-1,
            progress: 0,
            um: null,//编译器实例化
            form: null,//layui.form
            layTabId: 1,
            ruleBool: id ? true : false,
        },
        watch:{
            'formData.is_sub':function (n) {
                if (n == 1) {
                    this.formHeader.push({title:'一级返佣(元)'});
                    this.formHeader.push({title:'二级级返佣(元)'});
                } else {
                    this.formHeader.pop();
                    this.formHeader.pop();
                }
            },
            'formData.spec_type':function (n) {
                if (n) {
                    this.render();
                }
            },
            'formData.image':function (n) {
                
            }
        },
        methods: {
            back:function(){
                var that = this;
                layui.use(['element'], function () {
                    layui.element.tabChange('docTabBrief', that.layTabId == 1 ? 1 : parseInt(that.layTabId) - 1);
                });
            },
            next:function(){
                var that = this;
                layui.use(['element'], function () {
                    layui.element.tabChange('docTabBrief', that.layTabId == 3 ? 3 : parseInt(that.layTabId) + 1);
                });
            },
            goBack:function(){
                location.href = this.U({c:'system.SystemStore',a:'index'});
            },
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
            getProductInfo: function () {
                var that = this;
                that.requestGet(that.U({c:"system.SystemStore",a:'get_store_info',q:{id:that.id}})).then(function (res) {
                   
                    var productInfo = res.data.storeInfo || {};
                    if(productInfo.id && that.id){
                        that.$set(that,'formData',productInfo);
                        that.generate();
                    }
                    that.getRuleList();
                    that.init();
                }).catch(function (res) {
                    that.showMsg(res.msg);
                })
            },
            /**
             * 给某个属性添加属性值
             * @param item
             * */
            addDetail: function (item) {
                if (!item.detailValue) return false;
                if (item.detail.find(function (val) {
                    if(item.detailValue == val){
                        return true;
                    }
                })) {
                    return this.showMsg('添加的属性值重复');
                }
                item.detail.push(item.detailValue);
                item.detailValue = '';
            },
            /**
             * 删除某个属性值
             * @param item 父级循环集合
             * @param inx 子集index
             * */
            deleteValue: function (item, inx) {
                if (item.detail.length > 1) {
                    item.detail.splice(inx, 1);
                } else {
                    return this.showMsg('请设置至少一个属性');
                }
            },
            /**
             * 删除某条属性
             * @param index
             * */
            deleteItem: function (index) {
                this.formData.items.splice(index, 1);
            },
            /**
             * 删除某条属性
             * @param index
             * */
            deleteAttrs: function (index) {
                var that = this;
                if(that.id > 0){
                    that.requestGet(that.U({c:"store.StoreProduct",a:'check_activity',q:{id:that.id}})).then(function (res) {
                        that.showMsg(res.msg);
                    }).catch(function (res) {
                        if (that.formData.attrs.length > 1) {
                            that.formData.attrs.splice(index, 1);
                        } else {
                            return that.showMsg('请设置至少一个规则');
                        }
                    })
                }else{
                    if (that.formData.attrs.length > 1) {
                        that.formData.attrs.splice(index, 1);
                    } else {
                        return that.showMsg('请设置至少一个规则');
                    }
                }
            },
            /**
             * 创建属性
             * */
            createAttrName: function () {
                if (this.formDynamic.attrsName && this.formDynamic.attrsVal) {
                    if (this.formData.items.find(function (val) {
                        if (val.value == this.formDynamic.attrsName) {
                            return true;
                        }
                    }.bind(this))) {
                        return this.showMsg('添加的属性重复');
                    }
                    this.formData.items.push({
                        value: this.formDynamic.attrsName,
                        detailValue: '',
                        attrHidden: false,
                        detail: [this.formDynamic.attrsVal]
                    });
                    this.formDynamic.attrsName = '';
                    this.formDynamic.attrsVal = '';
                    this.newRule = false;
                } else {
                    return this.showMsg('请添加完整的规格!');
                }
            },
            /**
             * 删除图片
             * */
            deleteImage: function (key, index) {
                var that = this;
                if (index != undefined) {
                    that.formData[key].splice(index, 1);
                    that.$set(that.formData, key, that.formData[key]);
                } else {
                    that.$set(that.formData, key, '');
                }
            },
            createFrame: function (title, src, opt) {
                opt === undefined && (opt = {});
                var h = 0;
                if (window.innerHeight < 800 && window.innerHeight >= 700) {
                    h = window.innerHeight - 50;
                } else if (window.innerHeight < 900 && window.innerHeight >= 800) {
                    h = window.innerHeight - 100;
                } else if (window.innerHeight < 1000 && window.innerHeight >= 900) {
                    h = window.innerHeight - 150;
                } else if (window.innerHeight >= 1000) {
                    h = window.innerHeight - 200;
                } else {
                    h = window.innerHeight;
                }
                var area = [(opt.w || window.innerWidth / 2) + 'px', (!opt.h || opt.h > h ? h : opt.h) + 'px'];
                layui.use('layer',function () {
                    return layer.open({
                        type: 2,
                        title: title,
                        area: area,
                        fixed: false, //不固定
                        maxmin: true,
                        moveOut: false,//true  可以拖出窗外  false 只能在窗内拖
                        anim: 5,//出场动画 isOutAnim bool 关闭动画
                        offset: 'auto',//['100px','100px'],//'auto',//初始位置  ['100px','100px'] t[ 上 左]
                        shade: 0,//遮罩
                        resize: true,//是否允许拉伸
                        content: src,//内容
                        move: '.layui-layer-title'
                    });
                });
            },
            changeIMG: function (name, value) {
                if (this.getRule(name).maxLength !== undefined) {
                    var that = this;
                    value.map(function (v) {
                        that.formData[name].push(v);
                    });
                    this.$set(this.formData, name, this.formData[name]);
                } else {
                    if(name == 'batchAttr.pic'){
                        this.batchAttr.pic = value;
                    } else {
                        if (name.indexOf('.') !== -1) {
                            var key = name.split('.');
                            if (key.length == 2){
                                this.formData[key[0]][key[1]] = value;
                            } else if(key.length == 3){
                                this.formData[key[0]][key[1]][key[2]] = value;
                            } else if(key.length == 4){
                                this.$set(this.formData[key[0]][key[1]][key[2]],key[3],value)
                            }
                        } else {
                            this.formData[name] = value;
                        }
                    }
                }
            },
            getRule: function (name) {
                return this.rule[name] || {};
            },
            uploadImage: function (name) {
                return this.createFrame('选择图片',this.U({c:"widget.images",a:'index',p:{fodder:name}}),{h:545,w:900});
            },
            uploadVideo: function () {
                if (this.videoLink) {
                    this.formData.video_link = this.videoLink;
                } else {
                    $(this.$refs.filElem).click();
                }
            },
            delVideo: function () {
                var that = this;
                that.$set(that.formData, 'video_link', '');
            },
            insertEditor: function (list) {
                this.um.execCommand('insertimage', list);
            },
            insertEditorVideo: function (src) {
                this.um.setContent('<div><video style="width: 99%" src="'+src+'" class="video-ue" controls="controls" width="100"><source src="'+src+'"></source></video></div><br>',true);
            },
            getContent: function () {
                return this.um.getContent();
            },
            
            init: function () {
                var that = this;
                window.UMEDITOR_CONFIG.toolbar = [
                    // 加入一个 test
                    'source | undo redo | bold italic underline strikethrough | superscript subscript | forecolor backcolor | removeformat |',
                    'insertorderedlist insertunorderedlist | selectall cleardoc paragraph | fontfamily fontsize',
                    '| justifyleft justifycenter justifyright justifyjustify |',
                    'link unlink | emotion selectimgs video  | map',
                    '| horizontal print preview fullscreen', 'drafts', 'formula'
                ];
                UM.registerUI('selectimgs', function (name) {
                    var me = this;
                    var $btn = $.eduibutton({
                        icon: 'image',
                        click: function () {
                            that.createFrame('选择图片', "{:Url('widget.images/index',['fodder'=>'editor'])}");
                        },
                        title: '选择图片'
                    });

                    this.addListener('selectionchange', function () {
                        //切换为不可编辑时，把自己变灰
                        var state = this.queryCommandState(name);
                        $btn.edui().disabled(state == -1).active(state == 1)
                    });
                    return $btn;

                });
                UM.registerUI('video', function (name) {
                    var me = this;
                    var $btn = $.eduibutton({
                        icon: 'video',
                        click: function () {
                            that.createFrame('选择视频', "{:Url('widget.video/index',['fodder'=>'video'])}");
                        },
                        title: '选择视频'
                    });

                    this.addListener('selectionchange', function () {
                        //切换为不可编辑时，把自己变灰
                        var state = this.queryCommandState(name);
                        $btn.edui().disabled(state == -1).active(state == 1)
                    });
                    return $btn;

                });
                //实例化编辑器
                this.um = UM.getEditor('myEditor', {initialFrameWidth: '99%', initialFrameHeight: 400});
                this.um.setContent(that.formData.introduction);
                that.$nextTick(function () {
                    layui.use(['form','element'], function () {
                        that.form = layui.form;
                        that.form.render();
                        that.form.on('select(temp_id)', function (data) {
                            that.$set(that.formData, 'temp_id', data.value);
                        });
                        that.form.on('select(store_id)', function (data) {
                            that.$set(that.formData, 'store_id', data.value);
                        });
                        that.form.on('select(rule_index)', function (data) {
                            that.ruleIndex = data.value;
                        });
                        layui.element.on('tab(docTabBrief)', function(){
                            that.layTabId = this.getAttribute('lay-id');
                        });
                    });
                })
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
            generate: function () {
                var that = this;
                this.requestPost(that.U({c:"store.StoreProduct",a:'is_format_attr',p:{id:that.id}}), {attrs:this.formData.items}).then(function (res) {
                    that.$set(that.formData, 'attrs', res.data.value);
                    that.$set(that, 'formHeader', res.data.header);
                    if (that.id && that.formData.is_sub == 1 && that.formData.spec_type == 1) {
                        that.formHeader.push({title:'一级返佣(元)'});
                        that.formHeader.push({title:'二级级返佣(元)'});
                    }
                }).catch(function (res) {
                    return that.showMsg(res.msg);
                });
            },
            handleSubmit:function () {
                var that = this;
                that.formData.introduction = that.getContent();
                that.requestPost(that.U({c:'system.SystemStore',a:'saveImg',p:{id:that.id}}),that.formData).then(function (res) {
                    that.confirm();
                }).catch(function (res) {
                    that.showMsg(res.msg);
                });
            },
            confirm: function(){
                var that = this;
                layui.use(['layer'], function () {
                    var layer = layui.layer;
                    layer.confirm(that.id ? '修改成功是否商家列表' : '添加成功是否返回商家列表', {
                        btn: ['返回列表',that.id ? '继续修改' : '继续添加'] //按钮
                    }, function(){
                        location.href = that.U({c:'system.SystemStore',a:'index'});
                    }, function(){
                        if (that.id == 0) {
                            location.reload();
                        }
                    });
                });
            },
            render:function(){
                this.$nextTick(function(){
                    layui.use(['form'], function () {
                        layui.form.render('select');
                    });
                })
            },
            // 移动
            handleDragStart (e, item) {
                this.dragging = item;
            },
            handleDragEnd (e, item) {
                this.dragging = null
            },
            handleDragOver (e) {
                e.dataTransfer.dropEffect = 'move'
            },
            handleDragEnter (e, item) {
                e.dataTransfer.effectAllowed = 'move'
                if (item === this.dragging) {
                    return
                }
                var newItems = [...this.formData.activity];
                var src = newItems.indexOf(this.dragging);
                var dst = newItems.indexOf(item);
                newItems.splice(dst, 0, ...newItems.splice(src, 1))
                this.formData.activity = newItems;
            },
            getRuleList:function (type) {
                var that = this;
                that.requestGet(that.U({c:'store.StoreProduct',a:'get_rule'})).then(function (res) {
                    that.$set(that,'ruleList',res.data);
                    if(type !== undefined){
                        that.render();
                    }
                });
            },
            addRule:function(){
                return this.createFrame('添加商品规则',this.U({c:'store.StoreProductRule',a:'create'}));
            },
            allRule:function () {
                if (this.ruleIndex != -1) {
                    var rule = this.ruleList[this.ruleIndex];
                    if (rule) {
                        this.ruleBool = true;
                        var rule_value = rule.rule_value.map(function (item) {
                            return item;
                        });
                        this.$set(this.formData,'items',rule_value);
                        this.$set(this.formData,'attrs',[]);
                        this.$set(this,'formHeader',[]);
                        return true;
                    }
                }
                this.showMsg('选择的属性无效');
            }
        },
        mounted: function () {
            var that = this;
            that.getProductInfo();
            window.$vm = that;
            window.changeIMG = that.changeIMG;
            window.insertEditor = that.insertEditor;
            window.insertEditorVideo = that.insertEditorVideo;
            window.successFun = function(){
                that.getRuleList(1);
            }
            $(that.$refs.filElem).change(function () {
                var inputFile = this.files[0];
                that.requestPost(that.U({c:"widget.video",a:'get_signature'})).then(function (res) {
                    AdminUpload.upload(res.data.uploadType,{
                        token: res.data.uploadToken || '',
                        file: inputFile,
                        accessKeyId: res.data.accessKey || '',
                        accessKeySecret: res.data.secretKey || '',
                        bucketName: res.data.storageName || '',
                        region: res.data.storageRegion || '',
                        domain: res.data.domain || '',
                        uploadIng:function (progress) {
                            that.upload.videoIng = true;
                            that.progress = progress;
                        }
                    }).then(function (res) {
                        //成功
                        that.$set(that.formData, 'video_link', res.url);
                        that.progress = 0;
                        that.upload.videoIng = false;
                        return that.showMsg('上传成功');
                    }).catch(function (err) {
                        //失败
                        console.info(err);
                        return that.showMsg('上传错误请检查您的配置');
                    });
                }).catch(function (res) {
                    return that.showMsg('获取密钥失败,请检查您的配置');
                });
            })
        }
    });
</script>
</body>
</html>