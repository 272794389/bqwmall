{extend name="public/container"}
{block name="head_top"}
<link rel="stylesheet" href="{__PLUG_PATH}daterangepicker/daterangepicker.css">
<script src="{__PLUG_PATH}moment.js"></script>
<script src="{__PLUG_PATH}daterangepicker/daterangepicker.js"></script>
{/block}
{block name="content"}
<div class="row">
    <div class="col-sm-12">
        <div class="ibox">
            <div class="ibox-content">
                <div class="row">
                    <div class="m-b m-l">
                        <form action="" class="form-inline">
                            <div class="search-item" data-name="date">
                                <span>选择时间：</span>
                                <button type="button" class="btn btn-outline btn-link" data-value="">全部</button>
                                <button type="button" class="btn btn-outline btn-link" data-value="{$limitTimeList.today}">今天</button>
                                <button type="button" class="btn btn-outline btn-link" data-value="{$limitTimeList.week}">本周</button>
                                <button type="button" class="btn btn-outline btn-link" data-value="{$limitTimeList.month}">本月</button>
                                <button type="button" class="btn btn-outline btn-link" data-value="{$limitTimeList.quarter}">本季度</button>
                                <button type="button" class="btn btn-outline btn-link" data-value="{$limitTimeList.year}">本年</button>
                                <div class="datepicker" style="display: inline-block;">
                                    <button type="button" class="btn btn-outline btn-link" data-value="{$where.date?:'no'}">自定义时间</button>
                                </div>
                                <input class="search-item-value" type="hidden" name="date" value="{$where.date}" />
                            </div>
                            <select name="status" aria-controls="editable" class="form-control input-sm">
                                <option value="">支付状态</option>
                                <option value="0" {eq name="where.status" value="0"}selected="selected"{/eq}>未支付</option>
                                <option value="1" {eq name="where.status" value="1"}selected="selected"{/eq}>已支付</option>
                            </select>
                            
                            <div class="input-group">
                                  <span class="input-group-btn">
                                      <input type="text" name="nireid" value="{$where.nireid}" placeholder="客户名称/电话号码" class="input-sm form-control" size="38"/>
                                      <input type="text" name="shopname" value="{$where.shopname}" placeholder="商户名称" class="input-sm form-control" size="38"/>
                                      <button type="submit" class="btn btn-sm btn-primary"> 搜索</button>
                                  </span>
                            </div>
                        </form>
                    </div>
                    <div class="col-sm-3 ui-sortable">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-success pull-right">￥</span>
                                <h5>消费金额</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins">{$data.total_amount}</h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3 ui-sortable">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-success pull-right">￥</span>
                                <h5>实际支付现金总额</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins">{$data.pay_amount}</h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3 ui-sortable">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-danger pull-right">￥</span>
                                <h5>购物积分支付总额</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins">{$data.pay_give}</h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3 ui-sortable">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-danger pull-right">￥</span>
                                <h5>抵扣券抵扣总额</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins">{$data.coupon_amount}</h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped  table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center">编号</th>
                                <th class="text-center">订单号</th>
                                <th class="text-center">用户信息</th>
                                <th class="text-center">消费金额</th>
                                <th class="text-center">实际支付</th>
                                <th class="text-center">购物积分支付</th>
                                <th class="text-center">抵扣券抵扣</th>
                                <th class="text-center">支付方式</th>
                                <th class="text-center">状态</th>
                                <th class="text-center">下单时间</th>
                            </tr>
                        </thead>
                        <tbody class="">
                        {volist name="list" id="vo"}
                        <tr>
                            <td class="text-center">
                                {$vo.id}
                            </td>
                            <td class="text-center">
                                {$vo.order_id}
                            </td>
                            <td class="text-center">
                               用户昵称: {$vo.nickname}/用户id:{$vo.uid}
                            </td>
                            <td class="text-center" style="color: #00aa00;">
                                {$vo.total_amount}
                            </td>
                            <td class="text-center" style="color: #00aa00;">
                                {$vo.pay_amount}
                            </td>
                            <td class="text-center" style="color: #00aa00;">
                                {$vo.pay_give}
                            </td>
                            <td class="text-center" style="color: #00aa00;">
                                {$vo.coupon_amount}
                            </td>
                            <td class="text-center">
                                {if condition="$vo['paid'] eq 1"}
                                   {if condition="$vo['pay_type'] eq 'yue'"}余额支付{else}微信支付{/if}
                                {else}--{/if}
                            </td>
                            <td class="text-center">
                                {if condition="$vo['paid'] eq 1"}
                                   已付款
                                {else}未付款{/if}
                            </td>
                            <td class="text-center">
                                {$vo.add_time|date='Y-m-d H:i:s'}
                            </td>
                        </tr>
                        {/volist}
                        </tbody>
                    </table>
                </div>
                {include file="public/inner_page"}
            </div>
        </div>
    </div>
</div>
{/block}
{block name="script"}
<script>
    $(function init() {
        $('.search-item>.btn').on('click', function () {
            var that = $(this), value = that.data('value'), p = that.parent(), name = p.data('name'), form = p.parents();
            form.find('input[name="' + name + '"]').val(value);
            $('input[name=export]').val(0);
            form.submit();
        });
        $('.tag-item>.btn').on('click', function () {
            var that = $(this), value = that.data('value'), p = that.parent(), name = p.data('name'), form = p.parents(),list = $('input[name="' + name + '"]').val().split(',');
            var bool = 0;
            $.each(list,function (index,item) {
                if(item == value){
                    bool = 1
                    list.splice(index,1);
                }
            })
            if(!bool) list.push(''+value+'');
            form.find('input[name="' + name + '"]').val(list.join(','));
            $('input[name=export]').val(0);
            form.submit();
        });
        $('.search-item>li').on('click', function () {
            var that = $(this), value = that.data('value'), p = that.parent(), name = p.data('name'), form = $('#form');
            form.find('input[name="' + name + '"]').val(value);
            $('input[name=export]').val(0);
            form.submit();
        });
        $('.search-item>li').each(function () {
            var that = $(this), value = that.data('value'), p = that.parent(), name = p.data('name');
            if($where[name]) $('.'+name).css('color','#1ab394');
        });
        $('.search-item-value').each(function () {
            var that = $(this), name = that.attr('name'), value = that.val(), dom = $('.search-item[data-name="' + name + '"] .btn[data-value="' + value + '"]');
            dom.eq(0).removeClass('btn-outline btn-link').addClass('btn-primary btn-sm')
                .siblings().addClass('btn-outline btn-link').removeClass('btn-primary btn-sm')
        });
    })
    $('.j-fail').on('click',function(){
        var url = $(this).data('url');
        $eb.$alert('textarea',{
            title:'请输入未通过原因',
            value:'输入信息不完整或有误!',
        },function(value){
            $eb.axios.post(url,{message:value}).then(function(res){
                if(res.data.code == 200) {
                    $eb.$swal('success', res.data.msg);
                    setTimeout(function () {
                        window.location.reload();
                    },1000);
                }else
                    $eb.$swal('error',res.data.msg||'操作失败!');
            });
        });
    });
    $('.j-success').on('click',function(){
        var url = $(this).data('url');
        $eb.$swal('delete',function(){
            $eb.axios.post(url).then(function(res){
                if(res.data.code == 200) {
                    setTimeout(function () {
                        window.location.reload();
                    },1000);
                    $eb.$swal('success', res.data.msg);
                }else
                    $eb.$swal('error',res.data.msg||'操作失败!');
            });
        },{
            title:'确定审核通过?',
            text:'通过后无法撤销，请谨慎操作！',
            confirm:'审核通过'
        });
    });
    $('.btn-warning').on('click',function(){
        window.t = $(this);
        var _this = $(this),url =_this.data('url');
        $eb.$swal('delete',function(){
            $eb.axios.get(url).then(function(res){
                if(res.status == 200 && res.data.code == 200) {
                    $eb.$swal('success',res.data.msg);
                    _this.parents('tr').remove();
                }else
                    return Promise.reject(res.data.msg || '删除失败')
            }).catch(function(err){
                $eb.$swal('error',err);
            });
        })
    });
    $(".open_image").on('click',function (e) {
        var image = $(this).data('image');
        $eb.openImage(image);
    })
    var dateInput = $('.datepicker');
    dateInput.daterangepicker({
        autoUpdateInput: false,
        "opens": "center",
        "drops": "down",
        "ranges": {
            '今天': [moment(), moment().add(1, 'days')],
            '昨天': [moment().subtract(1, 'days'), moment()],
            '上周': [moment().subtract(6, 'days'), moment()],
            '前30天': [moment().subtract(29, 'days'), moment()],
            '本月': [moment().startOf('month'), moment().endOf('month')],
            '上月': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        "locale" : {
            applyLabel : '确定',
            cancelLabel : '取消',
            fromLabel : '起始时间',
            toLabel : '结束时间',
            format : 'YYYY/MM/DD',
            customRangeLabel : '自定义',
            daysOfWeek : [ '日', '一', '二', '三', '四', '五', '六' ],
            monthNames : [ '一月', '二月', '三月', '四月', '五月', '六月',
                '七月', '八月', '九月', '十月', '十一月', '十二月' ],
            firstDay : 1
        }
    });
    dateInput.on('apply.daterangepicker', function(ev, picker) {
        $("input[name=date]").val(picker.startDate.format('YYYY/MM/DD') + ' - ' + picker.endDate.format('YYYY/MM/DD'));
        $('form').submit();
    });
</script>
{/block}

