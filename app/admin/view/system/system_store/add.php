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
                    <h5>商户设置设置</h5>
                </div>
                <div id="store-attr" class="mp-form" v-cloak="">
                    <i-Form :label-width="80" style="width: 100%">
                        <template>
                            <Alert type="warning">除商户简介外其他选项都是必填项</Alert>
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
                                        <span>商户标签：</span>
                                        <i-Input placeholder="多个标签用,隔开;如免费停车,无限wifi" v-model="form.label" style="width: 100%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>用户id：</span>
                                        <i-Input placeholder="用户id" v-model="form.user_id" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>推荐人id：</span>
                                        <i-Input placeholder="推荐人id" v-model="form.parent_id" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <span>所属分类：</span>
                                <i-select style="width: 80%" v-model="form.cat_id">
                                    <i-option v-for="cat in catList" :value="cat.id" >{{cat.cate_name}}</i-option>
                                </i-select>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>门店：</span>
                                        <i-Input placeholder="门店" v-model="form.name" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>门店简介：</span>
                                        <i-Input placeholder="门店简介" v-model="form.introduction" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>门店电话：</span>
                                        <i-Input placeholder="门店电话" v-model="form.phone" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>联系人：</span>
                                        <i-Input placeholder="联系人" v-model="form.link_name" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>联系电话：</span>
                                        <i-Input placeholder="联系电话" v-model="form.link_phone" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>门店地址：</span>
                                        <Cascader :data="addresData" :value.sync="form.address" @on-change="handleChange" style="width: 80%;display: inline-block;"></Cascader>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>详细地址：</span>
                                        <i-Input placeholder="详细地址" v-model="form.detailed_address" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>分成比例%：</span>
                                        <i-Input placeholder="分成比例%" v-model="form.sett_rate" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                             <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>赠消费积分比例%：</span>
                                        <i-Input placeholder="赠送消费积分比例%" v-model="form.pay_rate" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>购物积分支付比例%：</span>
                                        <i-Input placeholder="购物积分支付比例%" v-model="form.give_rate" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <span>商家类别：</span>
                                <i-select style="width: 80%" v-model="form.belong_t">
                                    <i-option value="0" >商品中心</i-option>
                                    <i-option value="1" >网店</i-option>
                                    <i-option value="2" >周边的店</i-option>
                                    <i-option value="3" >服务中心</i-option>
                                </i-select>
                            </Form-Item>
<!--                            <Form-Item>-->
<!--                                <Row>-->
<!--                                    <i-Col span="13">-->
<!--                                        <span>核销时效：</span>-->
<!--                                        <Date-picker type="daterange" @on-change="changeValidTime" placeholder="选择日期" :value="form.valid_time"></Date-picker>-->
<!--                                    </i-Col>-->
<!--                                </Row>-->
<!--                            </Form-Item>-->
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>营业周期：</span>
                                        <i-Input placeholder="营业周期" v-model="form.termDate" style="width: 80%" type="text"></i-Input>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>营业时间：</span>
                                        <Time-picker type="timerange" @on-change="changeDayTime" placement="bottom-end" :value="form.day_time" placeholder="选择时间"></Time-picker>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>门店logo：</span>
                                        <div class="demo-upload-list" v-if="form.image">
                                            <template>
                                                <img :src="form.image">
                                                <div class="demo-upload-list-cover">
                                                    <Icon type="ios-eye-outline" @click="visible = true "></Icon>
                                                    <Icon type="ios-trash-outline" @click="form.image=''"></Icon>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="ivu-upload" style="display: inline-block; width: 58px;" @click="openWindows('选择图片','{:Url('widget.images/index',['fodder'=>'image'])}',{w:900,h:550})" v-if="!form.image">
                                            <div class="ivu-upload ivu-upload-drag">
                                                <div style="width: 58px; height: 58px; line-height: 58px;">
                                                    <i class="ivu-icon ivu-icon-camera" style="font-size: 20px;"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <Modal title="查看图片" :visible.sync="visible">
                                            <img :src="form.image" v-if="visible" style="width: 100%">
                                        </Modal>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>营业执照：</span>
                                        <div class="demo-upload-list" v-if="form.license">
                                            <template>
                                                <img :src="form.license">
                                                <div class="demo-upload-list-cover">
                                                    <Icon type="ios-eye-outline" @click="lvisible = true "></Icon>
                                                    <Icon type="ios-trash-outline" @click="form.license=''"></Icon>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="ivu-upload" style="display: inline-block; width: 58px;" @click="openWindows('选择图片','{:Url('widget.images/index',['fodder'=>'license'])}',{w:900,h:550})" v-if="!form.license">
                                            <div class="ivu-upload ivu-upload-drag">
                                                <div style="width: 58px; height: 58px; line-height: 58px;">
                                                    <i class="ivu-icon ivu-icon-camera" style="font-size: 20px;"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <Modal title="查看图片" :visible.sync="lvisible">
                                            <img :src="form.license" v-if="lvisible" style="width: 100%">
                                        </Modal>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>身份证头像页：</span>
                                        <div class="demo-upload-list" v-if="form.idCardz">
                                            <template>
                                                <img :src="form.idCardz">
                                                <div class="demo-upload-list-cover">
                                                    <Icon type="ios-eye-outline" @click="izvisible = true "></Icon>
                                                    <Icon type="ios-trash-outline" @click="form.idCardz=''"></Icon>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="ivu-upload" style="display: inline-block; width: 58px;" @click="openWindows('选择图片','{:Url('widget.images/index',['fodder'=>'idCardz'])}',{w:900,h:550})" v-if="!form.idCardz">
                                            <div class="ivu-upload ivu-upload-drag">
                                                <div style="width: 58px; height: 58px; line-height: 58px;">
                                                    <i class="ivu-icon ivu-icon-camera" style="font-size: 20px;"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <Modal title="查看图片" :visible.sync="izvisible">
                                            <img :src="form.idCardz" v-if="izvisible" style="width: 100%">
                                        </Modal>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>身份证国徽页：</span>
                                        <div class="demo-upload-list" v-if="form.idCardf">
                                            <template>
                                                <img :src="form.idCardf">
                                                <div class="demo-upload-list-cover">
                                                    <Icon type="ios-eye-outline" @click="idvisible = true "></Icon>
                                                    <Icon type="ios-trash-outline" @click="form.idCardf=''"></Icon>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="ivu-upload" style="display: inline-block; width: 58px;" @click="openWindows('选择图片','{:Url('widget.images/index',['fodder'=>'idCardf'])}',{w:900,h:550})" v-if="!form.idCardf">
                                            <div class="ivu-upload ivu-upload-drag">
                                                <div style="width: 58px; height: 58px; line-height: 58px;">
                                                    <i class="ivu-icon ivu-icon-camera" style="font-size: 20px;"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <Modal title="查看图片" :visible.sync="idvisible">
                                            <img :src="form.idCardf" v-if="idvisible" style="width: 100%">
                                        </Modal>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span>经营许可证：</span>
                                        <div class="demo-upload-list" v-if="form.xukeImg">
                                            <template>
                                                <img :src="form.xukeImg">
                                                <div class="demo-upload-list-cover">
                                                    <Icon type="ios-eye-outline" @click="xuvisible = true "></Icon>
                                                    <Icon type="ios-trash-outline" @click="form.xukeImg=''"></Icon>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="ivu-upload" style="display: inline-block; width: 58px;" @click="openWindows('选择图片','{:Url('widget.images/index',['fodder'=>'xukeImg'])}',{w:900,h:550})" v-if="!form.xukeImg">
                                            <div class="ivu-upload ivu-upload-drag">
                                                <div style="width: 58px; height: 58px; line-height: 58px;">
                                                    <i class="ivu-icon ivu-icon-camera" style="font-size: 20px;"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <Modal title="查看图片" :visible.sync="xuvisible">
                                            <img :src="form.xukeImg" v-if="xuvisible" style="width: 100%">
                                        </Modal>
                                    </i-Col>
                                </Row>
                            </Form-Item>
                            <Form-Item>
                                <Row>
                                    <i-Col span="13">
                                        <span style="float: left">经纬度：</span>
                                        <Tooltip content="请点击查找位置进行选择位置">
                                            <i-Input placeholder="经纬度" v-model="form.latlng" :readonly="true" style="width: 80%" >
                                                <span slot="append" @click="openWindows('查找位置','{:Url('select_address')}',{w:400})" style="cursor:pointer">查找位置</span>
                                            </i-Input>
                                        </Tooltip>
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
    var catList={:json_encode($catList)};
    mpFrame.start(function(Vue) {
        $.each(city,function (key,item) {
            city[key].value = item.label;
            if(item.children && item.children.length){
                $.each(item.children,function (i,v) {
                    city[key].children[i].value=v.label;
                    if(v.children && v.children.length){
                        $.each(v.children,function (k,val) {
                            city[key].children[i].children[k].value=val.label;
                        });
                    }
                });
            }
        });
        new Vue({
            data:function() {
                return {
                    id:storeData.id || 0,
                    addresData:city,
                    catList:catList,
                    form:{
                    	mer_name:storeData.mer_name || '',
                    	label:storeData.label || '',
                        name:storeData.name || '',
                        user_id:storeData.user_id || '',
                        parent_id:storeData.parent_id || '',
                        cat_id:storeData.cat_id || 0,
                        introduction:storeData.introduction || '',
                        phone:storeData.phone || '',
                        link_name:storeData.link_name || '',
                        link_phone:storeData.link_phone || '',
                        sett_rate:storeData.sett_rate || 20,
                        give_rate:storeData.give_rate || 10,
                        pay_rate:storeData.pay_rate || 20,
                        address:storeData.address || [],
                        image:storeData.image || '',
                        license:storeData.license || '',
                        idCardz:storeData.idCardz || '',
                        idCardf:storeData.idCardf || '',
                        xukeImg:storeData.xukeImg || '',
                        belong_t:storeData.belong_t || 0,
                        detailed_address:storeData.detailed_address || '',
                        latlng:storeData.latlng || '',
                        valid_time:storeData.valid_time || [],
                        day_time:storeData.day_time || [],
                        termDate:storeData.termDate || '周一至周日',
                    },
                    visible:false,
                    lvisible:false,
                    izvisible:false,
                    idvisible:false,
                    xuvisible:false,
                }
            },
            methods:{
                changeDayTime:function(date){
                    this.$set(this.form,'day_time',date);
                },
                changeValidTime:function(date){
                    this.$set(this.form,'valid_time',date);
                },
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
                    if(!that.form.name) return  $eb.message('error','请填写门店行名称');
                    if(!that.form.cat_id) return  $eb.message('error','请选择商家所属分类');
                    if(!that.form.phone) return  $eb.message('error','请输入手机号码');
                    if(!that.isPhone(that.form.link_phone)) return  $eb.message('error','请输入正确的手机号码');
                    if(!that.form.address) return  $eb.message('error','请选择门店地址');
                    if(!that.form.detailed_address) return  $eb.message('error','请填写门店详细地址');
                    if(!that.form.image) return  $eb.message('error','请选择门店logo');
                    if(!that.form.license) return  $eb.message('error','请上传营业执照');
                    if(!that.form.idCardz) return  $eb.message('error','请上传身份证头像页');
                    if(!that.form.idCardf) return  $eb.message('error','请上传身份证国徽页');
                    if(!that.form.valid_time) return  $eb.message('error','请选择核销时效');
                    if(!that.form.termDate) return  $eb.message('error','请填写营业周期');
                    if(!that.form.day_time) return  $eb.message('error','请选择门店营业时间');
                    if(!that.form.latlng) return  $eb.message('error','请选择门店经纬度！');
                    var index = layer.load(1, {
                        shade: [0.5,'#fff']
                    });
                    $eb.axios.post('{:Url("save")}'+(that.id ? '?id='+that.id : ''),that.form).then(function (res) {
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