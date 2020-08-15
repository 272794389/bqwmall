{extend name="public/container"}
{block name="content"}
<div class="row">
    <div class="col-sm-12">
        <div class="ibox">
            <div class="ibox-content">
                <div class="row">
                    <div class="m-b m-l">

                        <form action="" class="form-inline">

                            <select name="is_fail" aria-controls="editable" class="form-control input-sm">
                                <option value="">是否有效</option>-->
                                <option value="1" {eq name="where.is_fail" value="1"}selected="selected"{/eq}>是</option>
                                <option value="0" {eq name="where.is_fail" value="0"}selected="selected"{/eq}>否</option>
                           </select>
                            
                            <div class="input-group">
                                <input type="text" name="nickname" value="{$where.nickname}" placeholder="请输入发放人姓名" class="input-sm form-control"> <span class="input-group-btn">
                            </div>
                            <div class="input-group">
                                <input type="text" name="coupon_title" value="{$where.coupon_title}" placeholder="请输入抵扣券名称" class="input-sm form-control"> <span class="input-group-btn">
                                    <button type="submit" class="btn btn-sm btn-primary"> <i class="fa fa-search" ></i>搜索</button> </span>
                            </div>
                        </form>
                    </div>

                </div>
                <div class="table-responsive">
                    <table class="table table-striped  table-bordered">
                        <thead>
                        <tr>

                            <th class="text-center">编号</th>
                            <th class="text-center">抵扣券名称</th>
                            <th class="text-center">抵扣券类型</th>
                            <th class="text-center">领取人</th>
                            <th class="text-center">抵扣券面值</th>
                            <th class="text-center">已使用金额</th>
                            <th class="text-center">抵扣券开始使用时间</th>
                            <th class="text-center">抵扣券结束使用时间</th>
                            <th class="text-center">获取放方式</th>
                            <th class="text-center">是否有效</th>
                        </tr>
                        </thead>
                        <tbody class="">
                        {volist name="list" id="vo"}
                        <tr>
                            <td class="text-center">
                                {$vo.id}
                            </td>
                            <td class="text-center">
                                {$vo.title}
                            </td>
                            <td class="text-center">
                                {if condition="$vo['is_flag']"}
                                  商家抵扣券
                                {else}
                                商品抵扣券
                                {/if}
                            </td>
                            <td class="text-center">
                                {$vo.nickname}
                            </td>
                            <td class="text-center">
                                {$vo.coupon_price}
                            </td>
                             <td class="text-center">
                                {$vo.hamount}
                            </td>
                            <td class="text-center">
                                {$vo.add_time|date='Y-m-d H:i:s'}
                            </td>
                            <td class="text-center">
                                {$vo.end_time|date='Y-m-d H:i:s'}
                            </td>
                            <td class="text-center">
                                {$vo.type}
                            </td>
                            <td class="text-center">
                                {if condition="$vo['is_fail'] eq 0"}
                                    <i class="fa fa-check text-navy"></i>
                                {else/}
                                <i class="fa fa-close text-danger"></i>
                                {/if}
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