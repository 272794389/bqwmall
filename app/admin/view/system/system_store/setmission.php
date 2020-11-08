<!DOCTYPE html>
<html lang="zh-CN">
<head>
    {include file="public/head"}

    <link href="/system/frame/css/bootstrap.min.css?v=3.4.0" rel="stylesheet">
    <link href="/system/frame/css/style.min.css?v=3.0.0" rel="stylesheet">
    <title>{$title|default=''}</title>
    <style></style>
</head>
<body>
<div class="wrapper wrapper-content">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>商户任务设置</h5>
                </div>
                <div id="store-attr" class="mp-form" v-cloak="">
                    <i-Form :label-width="80" style="width: 100%">
                        <template>
                            <Alert type="warning">全部选项都为必填项</Alert>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>商户名称：</span>
                                        <i-Input placeholder="商户名称" v-model="form.mer_name" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>商户id：</span>
                                        <i-Input placeholder="商户id" v-model="form.store_id" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>任务月：</span>
                                        <i-Input placeholder="" v-model="form.date" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>任务单量：</span>
                                        <i-Input placeholder="有效订单量" v-model="form.ocnt" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>任务会员量：</span>
                                        <i-Input placeholder="有效客户数" v-model="form.ucnt" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>最低消费额：</span>
                                        <i-Input placeholder="最低消费额" v-model="form.minAmount" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                        </template>
                        <Form-Item>
                            <Row>
                                <i-Col span="8" offset="6">
                                    <i-Button type="primary" @click="submit">提交</i-Button>
                                </i-Col>
                            </Row>
                        </Form-Item>
                    </i-Form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__PLUG_PATH}city.js"></script>
<script>
    var storeData={:json_encode($store)};
    var minfo={:json_encode($minfo)};
    mpFrame.start(function(Vue) {
        new Vue({
            data:function() {
                return {
                    id:minfo.id || 0,
                    form:{
                    	mer_name:storeData.mer_name || '',
                    	date:minfo.dateTime || '',
                    	ocnt:minfo.ocnt || '',
                    	ucnt:minfo.ucnt || '',
                    	minAmount:minfo.minAmount || '',
                    	store_id:storeData.id || 0,
                    },
                    visible:false,
                    lvisible:false,
                    izvisible:false,
                    idvisible:false,
                    xuvisible:false,
                }
            },
            methods:{
                
                createFrame:function(title,src,opt){
                    opt === undefined && (opt = {});
                    var h = parent.document.body.clientHeight - 100;
                    return layer.open({
                        type: 2,
                        title:title,
                        area: [(opt.w || 700)+'px', (opt.h || h)+'px'],
                        fixed: false, //不固定
                        maxmin: true,
                        moveOut:false,//true  可以拖出窗外  false 只能在窗内拖
                        anim:5,//出场动画 isOutAnim bool 关闭动画
                        offset:'auto',//['100px','100px'],//'auto',//初始位置  ['100px','100px'] t[ 上 左]
                        shade:0,//遮罩
                        resize:true,//是否允许拉伸
                        content: src,//内容
                        move:'.layui-layer-title'
                    });
                },
                handleChange:function(value,selectedData){
                    var that = this;
                    that.form.address = [];
                    $.each(selectedData,function (key,item) {
                        that.form.address.push(item.label);
                    });
                    that.$set(that.form,'address',that.form.address);
                },
                openWindows:function(title,url,opt){
                    return this.createFrame(title,url,opt);
                },
                changeIMG:function(name,url){
                    this.form[name]=url;
                },
                isPhone:function(test){
                    var reg = /^1[3456789]\d{9}$/;
                    return reg.test(test);
                },
                submit:function () {
                    var that = this;
                    if(!that.form.store_id) return  $eb.message('error','请填写商户id');
                    if(!that.form.date) return  $eb.message('error','请填写任务周期');
                    if(!that.form.ocnt) return  $eb.message('error','请填写任务单量');
                    if(!that.form.ucnt) return  $eb.message('error','请填写任务会员量');
                    if(!that.form.minAmount) return  $eb.message('error','请填写最低消费金额');
                    var index = layer.load(1, {
                        shade: [0.5,'#fff']
                    });
                    $eb.axios.post('{:Url("insave")}'+(that.id ? '?id='+that.id : ''),that.form).then(function (res) {
                        layer.close(index);
                        layer.msg(res.data.msg);
                        if(res.data.data.id) that.id=res.data.data.id;
                    }).catch(function (err) {
                        console.log(err);
                        layer.close(index);
                    })
                },
                selectAdderss:function (data) {
                    //lat 纬度 lng 经度
                    this.form.latlng=data.latlng.lat+','+data.latlng.lng;
                }
            },
            mounted:function () {
                window.changeIMG=this.changeIMG;
                window.selectAdderss=this.selectAdderss;
            }
        }).$mount(document.getElementById('store-attr'))
    })
</script>