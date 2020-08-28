{extend name="public/container"}
{block name="content"}
<style>
.imgstyle{float:left;width:40%;margin-left:30%;}

</style>
<div class="ibox-content order-info">
    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="row show-grid">
                        <div class="col-xs-12" ><img class="imgstyle" src="{$ermaImg}"/></div>
                        <div class="col-xs-12" style="color:#f00;">注：鼠标点击右键，“图片另存为”即可下载二维码</div>
                    </div>
                    
                </div>
            </div>
        </div>
</div>
<script src="{__FRAME_PATH}js/content.min.js?v=1.0.0"></script>
{/block}
{block name="script"}

{/block}
