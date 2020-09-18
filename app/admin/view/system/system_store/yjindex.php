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
                                <div class="layui-input-inline">
                                    <button class="layui-btn layui-btn-primary layui-btn-sm export"  lay-submit="export" lay-filter="export">
                                        <i class="fa fa-floppy-o" style="margin-right: 3px;"></i>导出</button>
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
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
{/block}
{block name="script"}
<script>
    layList.tableList('List', "{:Url('yjlist')}", function () {
        return [
            {field: 'parent_id', title: 'ID', sort: true, event: 'id', width: '4%'},
            {field: 'operator', title: '姓名'},
            {field: 'telphone', title: '联系电话'},
            {field: 'today', title: '今日业绩'},
            {field: 'week', title: '本周业绩'},
            {field: 'month', title: '本月业绩'},
            {field: 'counts', title: '总业绩'}
        ];
    });
    //查询条件
    layList.search('search',function(where){
        layList.reload(where);
    });
    layList.date('user_time');
    //excel下载
    layList.search('export',function(where){
        where.excel = 1;
        location.href=layList.U({c:'system.system_store',a:'yjlist',q:where});
    })
    //点击事件绑定
    layList.tool(function (event, data, obj) {
        switch (event) {
                
        }
    })
</script>
{/block}