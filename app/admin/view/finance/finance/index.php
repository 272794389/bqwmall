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
                                <label class="layui-form-label">昵称/ID</label>
                                <div class="layui-input-block">
                                    <input type="text" name="nickname" class="layui-input">
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
                                        <option value="2">提现</option>
                                        <option value="3">货款转余额</option>
                                    </select>
                                </div>
                            </div>
                            <div class="layui-inline">
                                <label class="layui-form-label">变动类型</label>
                                <div class="layui-input-block">
                                    <select name="pay_type">
                                        <option value="">全部</option>
                                        <option value="0">余额</option>
                                        <option value="1">货款</option>
                                        <option value="2">购物积分</option>
                                        <option value="3">消费积分</option>
                                        <option value="4">重消积分</option>
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
                <div class="layui-card-header">资金监控日志</div>
                <div class="layui-card-body">
                    <table class="layui-hide" id="userList" lay-filter="userList"></table>
                    <script type="text/html" id="belong_t">
                        {{#  if(d.belong_t ==0){ }}
                                                                  消费订单
                        {{# }else if(d.belong_t ==1){ }}
                                                                  购物订单
                        {{# }else if(d.belong_t ==1){ }}
                                                                  提现
                        {{# }else{ }}
                                                                  货款转余额
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
    layList.tableList('userList',"{:Url('payloglist')}",function () {
        return [
            {field: 'uid', title: '会员ID', sort: true,event:'uid',align:"center",width:"5%"},
            {field: 'nickname', title: '昵称|电话号码' ,align:"center",width:"10%"},
            {field: 'belong_t', title: '记录类型',sort:true,templet:'#belong_t',align:"center"},
            {field: 'use_money', title: '余额',align:"center"},
            {field: 'huokuan', title: '货款',align:"center"},
            {field: 'give_point', title: '购物积分',align:"center"},
            {field: 'pay_point', title: '消费积分',align:"center"},
            {field: 'repeat_point', title: '重消积分',align:"center"},
            {field: 'fee', title: '手续费',align:"center"},
            {field: 'mark', title: '备注',align:"center",width:"16%"},
            {field: 'add_time', title: '创建时间',align:"center",width:"16%"},
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
        location.href=layList.U({a:'save_bell_export',q:{belong_t:where.belong_t,pay_type:where.pay_type,start_time:where.start_time,end_time:where.end_time,nickname:where.nickname}});
    });
</script>
{/block}