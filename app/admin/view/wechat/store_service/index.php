{extend name="public/container"}
{block name="head_top"}

{/block}
{block name="content"}
<div class="layui-fluid" style="background: #fff;margin-top: -10px;">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body">
                    <form class="layui-form layui-form-pane" action="">
                        <div class="layui-form-item">
                            <div class="layui-inline">
                                <label class="layui-form-label">门店名称</label>
                                <div class="layui-input-block">
                                   <input type="text" name="name" class="layui-input" placeholder="请输入门店名称">
                                </div>
                            </div>
                            <div class="layui-inline">
                                <div class="layui-input-inline">
                                    <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="search" lay-filter="search">
                                        <i class="layui-icon layui-icon-search"></i>搜索</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="layui-col-md12">
            <div class="layui-card">
<!--                <div class="layui-card-header">门店列表</div>-->
                <div class="layui-card-body">
                    <table class="layui-hide" id="List" lay-filter="List"></table>
                    <script type="text/html" id="headimgurl">
                        <img style="cursor: pointer" lay-event='open_image' src="{{d.avatar}}">
                    </script>
                    <script type="text/html" id="is_admin">
                        {{# if(d.is_admin>0){ }}
                          是
                        {{# }else{ }}否{{# } }}
                    </script>
                    <script type="text/html" id="is_check">
                        {{# if(d.is_check>0){ }}
                          是
                        {{# }else{ }}否{{# } }}
                    </script>
                    <script type="text/html" id="checkboxstatus">
                        <input type='checkbox' name='id' lay-skin='switch' value="{{d.id}}" lay-filter='is_show'
                               lay-text='开启|关闭' {{ d.status== 1 ? 'checked' : '' }}>
                    </script>
                    <script type="text/html" id="act">
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" lay-event='edit'>
                            下载收款码
                        </button>
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" lay-event='del'>
                            删除
                        </button>
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
{/block}
{block name="script"}
<script>
    layList.tableList('List', "{:Url('list')}", function () {
        return [
            {field: 'id', title: '编号', sort: true, event: 'id', width: '5%'},
            {field: 'nickname', title: '微信名称', width: '10%'},
            {field: 'avatar', title: '头像', templet: '#headimgurl', width: '15%'},
            {field: 'real_name', title: '客服姓名', width: '15%'},
            {field: 'name', title: '所属门店', width: '15%'},
            {field: 'is_admin', title: '是否管理员', width: '15%',templet: '#is_admin',},
            {field: 'is_check', title: '是否核销员', width: '10%',templet: '#is_check',},
            {field: 'right', title: '操作', align: 'center', toolbar: '#act', width: '15%'},
        ];
    });

    layList.form.render();
    //查询条件
    layList.search('search',function(where){
        layList.reload(where);
    });

    //门店是否显示
    layList.switch('is_show', function (odj, value) {
        if (odj.elem.checked == true) {
            layList.baseGet(layList.Url({
                c: 'system.SystemStoreStaff',
                a: 'set_show',
                p: {is_show: 1, id: value}
            }), function (res) {
                layList.msg(res.msg, function () {
                    layList.reload();
                });
            });
        } else {
            layList.baseGet(layList.Url({
                c: 'system.SystemStoreStaff',
                a: 'set_show',
                p: {is_show: 0, id: value}
            }), function (res) {
                layList.msg(res.msg, function () {
                    layList.reload();
                });
            });
        }
    });
    //点击事件绑定
    layList.tool(function (event, data, obj) {
        switch (event) {
            case 'del':
                var url = layList.U({c: 'wechat.StoreService', a: 'delete', q: {id: data.id}});
                var code = {title: "操作提示", text: "确定将该客服删除吗？", type: 'info', confirm: '是的，删除该客服'};
                $eb.$swal('delete', function () {
                    $eb.axios.get(url).then(function (res) {
                        if (res.status == 200 && res.data.code == 200) {
                            $eb.$swal('success', res.data.msg);
                            obj.del();
                            location.reload();
                        } else
                            return Promise.reject(res.data.msg || '删除失败')
                    }).catch(function (err) {
                        $eb.$swal('error', err);
                    });
                }, code)
                break;
            case 'edit':
            	$eb.createModalFrame('商家收款码',layList.U({a:'store_qrcode',q:{id:data.id}}));
                break;
        }
    })
</script>
{/block}