<template>
  <div class="cash-withdrawal">
    <div class="nav acea-row">
      <div
        v-for="(item, index) in navList"
        class="item font-color-red"
        @click="swichNav(index, item)"
        :key="index"
      >
        <div
          class="line bg-color-red"
          :class="currentTab === index ? 'on' : ''"
        ></div>
        <div
          class="iconfont"
          :class="item.icon + ' ' + (currentTab === index ? 'on' : '')"
        ></div>
        <div>{{ item.name }}</div>
      </div>
    </div>
    <div class="wrapper">
      <div :hidden="currentTab !== 0" class="list">
        <div class="item acea-row row-between-wrapper">
          <div class="name">银行</div>
          <div class="input">
             <!--<input  v-model="post.bankname" disabled="disabled"/>-->
            <select v-model="post.bankname">
              <option value="请选择银行">请选择银行</option>
              <option value="中国银行" >中国银行</option>
              <option value="中国工商银行">中国工商银行</option>
              <option value="中国农业银行">中国农业银行</option>
              <option value="中国建设银行">中国建设银行</option>
              <option value="贵州农信">贵州农信</option>
              <option value="贵州银行">贵州银行</option>
              <option value="贵阳银行">贵阳银行</option>
            </select>
          </div>
        </div>
        <div class="item acea-row row-between-wrapper">
          <div class="name">支行名称</div>
          <div class="input">
            <input placeholder="请输入支行名称" v-model="post.bank_address" />
          </div>
        </div>
        <div class="item acea-row row-between-wrapper">
          <div class="name">持卡人</div>
          <div class="input">
            <input placeholder="请输入持卡人姓名" v-model="post.name" />
          </div>
        </div>
        <div class="item acea-row row-between-wrapper">
          <div class="name">卡号</div>
          <div class="input">
            <input placeholder="请填写卡号" v-model="post.cardnum" />
          </div>
        </div>
        <div class="item acea-row row-between-wrapper">
          <div class="name">提现</div>
          <div class="input">
            <input
              :placeholder="'最低提现金额' + minPrice"
              v-model="post.money"
            />
          </div>
        </div>
        <div class="tip">当前可提现金额: {{ commissionCount }}</div>
        <div class="bnt bg-color-red" @click="submitted">提现</div>
      </div>
      <div :hidden="currentTab !== 1" class="list">
        <div class="item acea-row row-between-wrapper">
          <div class="name">微信号</div>
          <div class="input">
            <input placeholder="请输入微信号" v-model="post.weixin" />
          </div>
        </div>
        <div class="item acea-row row-between-wrapper">
          <div class="name">提现</div>
          <div class="input">
            <input
              :placeholder="'最低提现金额' + minPrice"
              v-model="post.money"
            />
          </div>
        </div>
        <div class="tip">当前可提现金额: {{ commissionCount }}</div>
        <div class="bnt bg-color-red" @click="submitted">提现</div>
      </div>
      <div :hidden="currentTab !== 2" class="list">
        <div class="item acea-row row-between-wrapper">
          <div class="name">用户名</div>
          <div class="input">
            <input placeholder="请填写您的支付宝用户名" v-model="post.name" />
          </div>
        </div>
        <div class="item acea-row row-between-wrapper">
          <div class="name">账号</div>
          <div class="input">
            <input
              placeholder="请填写您的支付宝账号"
              v-model="post.alipay_code"
            />
          </div>
        </div>
        <div class="item acea-row row-between-wrapper">
          <div class="name">提现</div>
          <div class="input">
            <input
              :placeholder="'最低提现金额' + minPrice"
              v-model="post.money"
            />
          </div>
        </div>
        <div class="tip">当前可提现金额: {{ commissionCount }}</div>
        <div class="bnt bg-color-red" @click="submitted">提现</div>
      </div>
    </div>
  </div>
</template>
<script>
import { getBank, postHuoCashInfo } from "../../../api/user";
import { required } from "@utils/validate";
import { validatorDefaultCatch } from "@utils/dialog";

export default {
  name: "HuokuanCash",
  components: {},
  props: {},
  data: function() {
    return {
     /*
      navList: [
        { name: "银行卡", type: "bank", icon: "icon-yinhangqia" },
        { name: "微信", type: "weixin", icon: "icon-weixin2" },
        { name: "支付宝", type: "alipay", icon: "icon-icon34" }
      ],
      */
      navList: [
        { name: "银行卡", type: "bank", icon: "icon-yinhangqia" }
      ],
      post: {
        extract_type: "bank",
        alipay_code: "",
        money: "",
        name: "",
        bankname: "请选择银行",
        bank_address:"",
        cardnum: "",
        weixin: ""
      },
      currentTab: 0,
      minPrice: 0,
      banks: [],
      commissionCount: 0
    };
  },
  mounted: function() {
    this.getBank();
  },
  methods: {
    swichNav: function(index, item) {
      this.currentTab = index;
      this.post.extract_type = item.type;
    },
    getBank: function() {
      let that = this;
      getBank().then(
        res => {
          that.banks = res.data.extractBank;
          that.minPrice = 10;
          that.commissionCount = res.data.huokuan;
          
          if(res.data.bankname){
             that.post.bankname = res.data.bankname;
             that.post.bank_address=res.data.bank_address;
             that.post.name=res.data.uname;
             that.post.cardnum=res.data.cardnum;
          }else{
             that.post.bankname = "请选择银行";
          }
        },
        function(err) {
          that.$dialog.message(err.msg);
        }
      );
    },
    async submitted() {
    /*this.isDisable = true;*/
      let bankname = this.post.bankname,
        alipay_code = this.post.alipay_code,
        money = this.post.money,
        name = this.post.name,
        bank_address = this.post.bank_address,
        cardnum = this.post.cardnum,
        weixin = this.post.weixin,
        that = this;
      if (
        parseFloat(money) > parseFloat(that.commissionCount) ||
        parseFloat(that.commissionCount) == 0
      )
        return that.$dialog.message("余额不足");
      if (parseFloat(money) < parseFloat(that.minPrice))
        return that.$dialog.message("最低提现金额" + that.minPrice);
      switch (that.post.extract_type) {
        case "bank":
          try {
            await this.$validator({
              name: [required.message("持卡人姓名")],
              cardnum: [required(required.message("卡号"))],
              bankname: [required(required("请选择提现银行"))],
              money: [required.message("提现金额")]
            }).validate({ bankname, cardnum, name, money });
            let save = {
              extract_type: that.post.extract_type,
              bankname: bankname,
              bank_address: bank_address,
              cardnum: cardnum,
              name: name,
              money: money
            };
            that.save(save);
          } catch (e) {
            return validatorDefaultCatch(e);
          }
          break;
        case "alipay":
          try {
            await this.$validator({
              name: [required(required.message("支付宝用户名"))],
              alipay_code: [required(required.message("支付宝账号"))],
              money: [required(required.message("提现金额"))]
            }).validate({ name, alipay_code, money });
            let save = {
              extract_type: that.post.extract_type,
              alipay_code: alipay_code,
              name: name,
              money: money
            };
            that.save(save);
          } catch (e) {
            return validatorDefaultCatch(e);
          }
          break;
        case "weixin":
          try {
            await this.$validator({
              weixin: [required(required.message("提现微信号"))],
              money: [required(required.message("提现金额"))]
            }).validate({ weixin, money });
            let save = {
              extract_type: that.post.extract_type,
              weixin: weixin,
              money: money
            };
            that.save(save);
          } catch (e) {
            return validatorDefaultCatch(e);
          }
          break;
      }
    },
    save: function(info) {
      this.$dialog.confirm({
              mes: "请务必填写支行名称，以确保准时到账！",
              opts: [
                {
                  txt: "确认提现",
                  color: false,
                  callback: () => {
                    postHuoCashInfo(info).then(
				        res => {
				          this.$dialog.message(res.msg);
				          this.$router.push({ path: "/user/haudit" });
				        },
				        error => {
				          this.$dialog.message(error.msg);
				        }
				      );
                  }
                },
                {
                  txt: "取消",
                  color: false,
                  callback: () => {
                   
                  }
                }
              ]
       });
    }
  }
};
</script>
