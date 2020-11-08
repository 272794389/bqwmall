{extend name="public/container"}
{block name="head_top"}
<script src="{__PLUG_PATH}city.js"></script>
<style>
    .layui-btn-xs{margin-left: 0px !important;}
    legend{
        width: auto;
        border: none;
        font-weight: 700 !important;
    }
    .site-demo-button{
        padding-bottom: 20px;
        padding-left: 10px;
    }
    .layui-form-label{
        width: auto;
    }
    .layui-input-block input{
        width: 50%;
        height: 34px;
    }
    .layui-form-item{
        margin-bottom: 0;
    }
    .layui-input-block .time-w{
        width: 200px;
    }
    .layui-table-body{overflow-x: hidden;}
    .layui-btn-group button i{
        line-height: 30px;
        margin-right: 3px;
        vertical-align: bottom;
    }
    .back-f8{
        background-color: #F8F8F8;
    }
    .layui-input-block button{
        border: 1px solid #e5e5e5;
    }
    .avatar{width: 50px;height: 50px;}
    .layui-table-body{
        overflow-x: unset;
    }
</style>
{/block}
{block name="content"}
<div class="row">
    <div class="col-sm-12">
        <div class="ibox float-e-margins">
            <div class="ibox-content" style="display: block;">
                <form class="layui-form">
                    <div class="layui-form-item">
                        <div class="layui-inline">
                            <label class="layui-form-label">推荐人id：</label>
                            <div class="layui-input-inline">
                                <input type="text" name="parent_id" lay-verify="parent_id" style="width: 100%" autocomplete="off" placeholder="请输入推荐人id" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">商户名称：</label>
                            <div class="layui-input-inline">
                                <input type="text" name="shopname" lay-verify="shopname" style="width: 100%" autocomplete="off" placeholder="请输入商户名称" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">月份选择：</label>
                            <div class="layui-input-inline">
                                <select name="dtime" lay-verify="dtime">
                                    <option value="0">全部</option>
                                    <option value="1">本月</option>
                                    <option value="2">上月</option>
                                </select>
                            </div>
                        </div>
                        
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="" lay-filter="search" >
                                <i class="layui-icon layui-icon-search layuiadmin-button-btn"></i>搜索</button>
                        </label>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="ibox">
            <div class="ibox-content">
                <div class="table-responsive">
                    <table class="layui-hide" id="userList" lay-filter="userList"></table>
                    <script type="text/html" id="real_name">
                        {{d.real_name}}
                        <p style="color:#dab176">{{d.parent_id}}</p>
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
<script src="{__FRAME_PATH}js/content.min.js?v=1.0.0"></script>
{/block}
{block name="script"}
<script>
    layList.form.render();
    layList.tableList('userList',"{:Url('get_shop_list')}",function () {
        return [
            {field: 'id', title: '编号',width:'6%',align:'center'},
            {field: 'name', title: '商户名称', width: '14%',align:'center'},
            {field: 'real_name', title: '推荐人',width: '10%',templet:'#real_name',align:'center'},
            {field: 'ocnt', title: '业务订单数',width:'10%',align:'center'},
            {field: 'ucnt', title: '业务会员数',width:'10%',align:'center'},
            {field: 'rocnt', title: '有效订单数',width:'10%',align:'center'},
            {field: 'orderAmount', title: '订单金额',width:'10%',align:'center'},
            {field: 'rucnt', title: '有效会员数',width:'10%',align:'center'},
            {field: 'aucnt', title: '推广会员数',width:'10%',align:'center'},
            {field: 'date', title: '月份',align:'center',width:'10%'}
        ];
    });
    //页面刷新时加载
    layui.use('layer',function(){
        var layer = layui.layer;
        layer.ready(function(){
            var html = '';
            var htmls = '';
            layList.form.render('select');
        });

    });

    
    //监听并执行 uid 的排序
    layList.tool(function (event,data,obj) {
        var layEvent = event;
        switch (layEvent){
            case 'edit':
                $eb.createModalFrame('编辑',layList.Url({a:'edit',p:{uid:data.uid}}));
                break;
            case 'see':
                $eb.createModalFrame(data.nickname+'-会员详情',layList.Url({a:'see',p:{uid:data.uid}}));
                break;
            case 'del_level':
                $eb.$swal('delete',function(){
                    $eb.axios.get(layList.U({a:'del_level',q:{uid:data.uid}})).then(function(res){
                        if(res.status == 200 && res.data.code == 200) {
                            $eb.$swal('success',res.data.msg);
                            obj.update({vip_name:false});
                            layList.reload();
                        }else
                            return Promise.reject(res.data.msg || '删除失败')
                    }).catch(function(err){
                        $eb.$swal('error',err);
                    });
                },{
                    title:'您确定要清除【'+data.nickname+'】的会员等级吗？',
                    text:'清除后无法恢复请谨慎操作',
                    confirm:'是的我要清除'
                })
                break;
            case 'give_level':
                $eb.createModalFrame(data.nickname+'-赠送会员',layList.Url({a:'give_level',p:{uid:data.uid}}),{w:500,h:300});
                break;
            case 'set_group':
                $eb.createModalFrame(data.nickname+'-设置分组',layList.Url({a:'set_group',p:{uid:data.uid}}),{w:500,h:300});
                break;
            case 'money':
                $eb.createModalFrame(data.nickname+'-积分余额修改',layList.Url({a:'edit_other',p:{uid:data.uid}}));
                break;
            case 'open_image':
                $eb.openImage(data.avatar);
                break;
        }
    });
    //layList.sort('uid');
    //监听并执行 now_money 的排序
    // layList.sort('now_money');
    //监听 checkbox 的状态
    layList.switch('status',function (odj,value,name) {
        if(odj.elem.checked==true){
            layList.baseGet(layList.Url({a:'set_status',p:{status:1,uid:value}}),function (res) {
                layList.msg(res.msg);
            });
        }else{
            layList.baseGet(layList.Url({a:'set_status',p:{status:0,uid:value}}),function (res) {
                layList.msg(res.msg);
            });
        }
    });
    layList.search('search',function(where){
        if(where['user_time_type'] != '' && where['user_time'] == '') return layList.msg('请选择选择时间');
        if(where['user_time_type'] == '' && where['user_time'] != '') return layList.msg('请选择访问情况');
        layList.reload(where,true);
    });

    var action={
        set_status_f:function () {
           var ids=layList.getCheckData().getIds('uid');
           if(ids.length){
               layList.basePost(layList.Url({a:'set_status',p:{is_echo:1,status:0}}),{uids:ids},function (res) {
                   layList.msg(res.msg);
                   layList.reload();
               });
           }else{
               layList.msg('请选择要封禁的会员');
           }
        },
        set_status_j:function () {
            var ids=layList.getCheckData().getIds('uid');
            if(ids.length){
                layList.basePost(layList.Url({a:'set_status',p:{is_echo:1,status:1}}),{uids:ids},function (res) {
                    layList.msg(res.msg);
                    layList.reload();
                });
            }else{
                layList.msg('请选择要解封的会员');
            }
        },
        set_grant:function () {
            var ids=layList.getCheckData().getIds('uid');
            if(ids.length){
                var str = ids.join(',');
                $eb.createModalFrame('发送抵扣券',layList.Url({c:'ump.goods_coupon',a:'grants',p:{id:str}}),{'w':800});
            }else{
                layList.msg('请选择要发送抵扣券的会员');
            }
        },
        set_ggrant:function () {
            var ids=layList.getCheckData().getIds('uid');
            if(ids.length){
                var str = ids.join(',');
                $eb.createModalFrame('发送抵扣券',layList.Url({c:'ump.goods_coupon',a:'grant',p:{id:str}}),{'w':800});
            }else{
                layList.msg('请选择要发送抵扣券的会员');
            }
        },
        set_cgrant:function () {
            var ids=layList.getCheckData().getIds('uid');
            if(ids.length){
                var str = ids.join(',');
                $eb.createModalFrame('发送抵扣券',layList.Url({c:'ump.goods_coupon',a:'cgrant',p:{id:str}}),{'w':800});
            }else{
                layList.msg('请选择要发送抵扣券的会员');
            }
        },
        set_template:function () {
            var ids=layList.getCheckData().getIds('uid');
            if(ids.length){
                var str = ids.join(',');
            }else{
                layList.msg('请选择要发送模板消息的会员');
            }
        },
        set_info:function () {
            var ids=layList.getCheckData().getIds('uid');
            if(ids.length){
                var str = ids.join(',');
                $eb.createModalFrame('发送站内信息',layList.Url({c:'user.user_notice',a:'notice',p:{id:str}}),{'w':1200});
            }else{
                layList.msg('请选择要发送站内信息的会员');
            }
        },
        set_custom:function () {
            var ids=layList.getCheckData().getIds('uid');
            if(ids.length){
                var str = ids.join(',');
                $eb.createModalFrame('发送客服图文消息',layList.Url({c:'wechat.wechat_news_category',a:'send_news',p:{id:str,type:1}}),{'w':1200});
            }else{
                layList.msg('请选择要发送客服图文消息的会员');
            }
        },
        set_group:function () {
            var ids=layList.getCheckData().getIds('uid');
            if(ids.length){
                var str = ids.join(',');
                $eb.createModalFrame('批量设置分组',layList.Url({a:'set_group',p:{uid:str}}),{w:500,h:300});
            }else{
                layList.msg('请选择要批量设置分组的会员');
            }
        },
        refresh:function () {
            layList.reload();
        }
    };
    $('.conrelTable').find('button').each(function () {
        var type=$(this).data('type');
        $(this).on('click',function () {
            action[type] && action[type]();
        })
    })
    $(document).on('click',".open_image",function (e) {
        var image = $(this).data('image');
        $eb.openImage(image);
    })
    //下拉框
    $(document).click(function (e) {
        $('.layui-nav-child').hide();
    })
    function dropdown(that){
        var oEvent = arguments.callee.caller.arguments[0] || event;
        oEvent.stopPropagation();
        var offset = $(that).offset();
        var top=offset.top-$(window).scrollTop();
        var index = $(that).parents('tr').data('index');
        $('.layui-nav-child').each(function (key) {
            if (key != index) {
                $(this).hide();
            }
        })
        if($(document).height() < top+$(that).next('ul').height()){
            $(that).next('ul').css({
                'padding': 10,
                'top': - ($(that).parent('td').height() / 2 + $(that).height() + $(that).next('ul').height()/2),
                'left':offset.left-$(that).parents('td').offset().left-20,
                'min-width': 'inherit',
                'position': 'absolute'
            }).toggle();
        }else{
            $(that).next('ul').css({
                'padding': 10,
                'top':$(that).parent('td').height() / 2 + $(that).height(),
                'left':offset.left-$(that).parents('td').offset().left-20,
                'min-width': 'inherit',
                'position': 'absolute'
            }).toggle();
        }
    }

</script>
{/block}
