{extend name="public/container"}
{block name="content"}
<style type="text/css">
    .form-add{position: fixed;left: 0;bottom: 0;width:100%;}
    .form-add .sub-btn{border-radius: 0;width: 100%;padding: 6px 0;font-size: 14px;outline: none;border: none;color: #fff;background-color: #2d8cf0;}
</style>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15"  id="app">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">搜索条件</div>
                <div class="layui-card-body">
                    <form class="layui-form layui-form-pane" action="">
                        <div class="layui-inline">
                            <label class="layui-form-label">商家名称</label>
                            <div class="layui-input-block">
                                <input type="text" name="name" class="layui-input" placeholder="请输入商家名称">
                            </div>
                        </div>
                        <div class="layui-inline">
                            <div class="layui-input-inline">
                                <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="search" lay-filter="search">
                                    <i class="layui-icon layui-icon-search"></i>搜索</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body">
                    <table class="layui-hide" id="List" lay-filter="List"></table>
                    <!--图片-->
                    <script type="text/html" id="image">
                        <img style="cursor: pointer" lay-event="open_image" src="{{d.image}}">
                    </script>
                    <!--操作-->
                </div>
            </div>
        </div>
</div>
<div class="form-add">
    <button type="submit" class="sub-btn">提交</button>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
{/block}
{block name='script'}
<script>
    var parentinputname = '{$Request.param.fodder}';
    layList.form.render();
    //加载列表
    layList.tableList('List',"{:Url('system.system_store/list',['type'=>1])}",function (){
        return [
            {type: 'checkbox'},
            {field: 'id', title: 'ID', sort: true,event:'id'},
            {field: 'image', title: '商家门头',templet:'#image'},
            {field: 'name', title: '商家名称',templet:'#name'},
        ]
    });

    //点击事件绑定
    $(".sub-btn").on("click",function(){
        var ids=layList.getCheckData().getIds('id');
        var pics=layList.getCheckData().getIds('image');
        parent.$f.changeField('image',pics);
        parent.$f.changeField('product_id',ids);
        parent.$f.closeModal(parentinputname);
    });
    //查询
    layList.search('search',function(where){
        layList.reload(where);
    });
</script>
{/block}