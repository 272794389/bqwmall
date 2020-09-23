{extend name="public/container"}
{block name="content"}
<div class="layui-fluid">
    <div class="layui-row layui-col-space15"  id="app">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">搜索条件</div>
                <div class="layui-card-body">
                    <form class="layui-form layui-form-pane" action="">
                        <div class="layui-form-item">
                            <div class="layui-inline">
                                <label class="layui-form-label">订单号</label>
                                <div class="layui-input-block">
                                    <input type="text" name="idno" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-inline">
                                <label class="layui-form-label">时间范围</label>
                                <div class="layui-input-inline" style="width: 200px;">
                                    <input type="text" name="start_time" placeholder="开始时间" id="start_time" class="layui-input">
                                </div>
                                <div class="layui-form-mid">-</div>
                                <div class="layui-input-inline" style="width: 200px;">
                                    <input type="text" name="end_time" placeholder="结束时间" id="end_time" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-inline">
                                <label class="layui-form-label">筛选类型</label>
                                <div class="layui-input-block">
                                    <select name="belong_t">
                                        <option value="">全部</option>
                                        <option value="0">消费订单</option>
                                        <option value="1">购物订单</option>
                                    </select>
                                </div>
                            </div>
                            <div class="layui-inline">
                                <div class="layui-input-inline">
                                    <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="search" lay-filter="search">
                                        <i class="layui-icon layui-icon-search"></i>搜索</button>
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
                <div class="layui-card-header">交易资金流水明细</div>
                <div class="layui-card-body">
                    <table class="layui-hide" id="userList" lay-filter="userList"></table>
                    <script type="text/html" id="flag">
                        {{#  if(d.flag ==0){ }}
                                                                  消费订单
                        {{# }else{ }}
                                                                   购物订单
                        {{# } }}
                    </script>
                    <script type="text/html" id="mer_name">
                       {{#  if(d.huokuan >0){ }}
                            {{d.mer_name}}
                       {{# }else{ }}
                                                                 --
                        {{# } }}
                    </script>
                    
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
<script>
    layList.form.render();
    layList.date({elem:'#start_time',theme:'#393D49',type:'datetime'});
    layList.date({elem:'#end_time',theme:'#393D49',type:'datetime'});
    layList.tableList('userList',"{:Url('payprofitlist')}",function () {
        return [
            {field: 'idno', title: '订单号', align:"center",width:"12%"},
            {field: 'flag', title: '订单类型' ,align:"center",templet:'#flag',},
            {field: 'total_amount', title: '交易额' ,align:"center"},
            {field: 'pay_amount', title: '实际支付' ,align:"center"},
            {field: 'huokuan', title: '货款' ,align:"center"},
            {field: 'pointer', title: '积分抵扣' ,align:"center"},
            {field: 'coupon_amount', title: '抵扣券抵扣' ,align:"center"},
            {field: 'shopaward', title: '商家推荐奖励' ,align:"center"},
            {field: 'faward', title: '一代推荐奖励' ,align:"center"},
            {field: 'saward', title: '二代推荐奖励' ,align:"center"},
            {field: 'fagent', title: '市级代理分红' ,align:"center"},
            {field: 'sagent', title: '地区代理分红' ,align:"center"},
            {field: 'fprerent', title: '市级总监分红' ,align:"center"},
            {field: 'sprerent', title: '地区总监分红' ,align:"center"},
            {field: 'out_amount', title: '运营成本' ,align:"center"},
            {field: 'fee', title: '手续费' ,align:"center"},
            {field: 'profit', title: '平台利润' ,align:"center"},
            {field: 'add_time', title: '创建时间',align:"center",width:"10%"},
        ];
    });
    layList.search('search',function(where){
        if(where.start_time!=''){
            if(where.end_time==''){
                layList.msg('请选择结束时间');
                return;
            }
        }
        if(where.end_time!=''){
            if(where.start_time==''){
                layList.msg('请选择开始时间');
                return;
            }
        }
        layList.reload(where,true);
    });
    layList.search('export',function(where){
        location.href=layList.U({a:'save_profit_export',q:{belong_t:where.belong_t,start_time:where.start_time,end_time:where.end_time,idno:where.idno}});
    });
</script>
{/block}