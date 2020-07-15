<template>
  <div class="addAddress absolute">
    <div class="list">
      <div class="item acea-row row-between-wrapper">
        <div class="name">姓名：</div>
        <input
          type="text"
          placeholder="请输入姓名"
          v-model="user.real_name"
          required
        />
      </div>
      <div class="item acea-row row-between-wrapper">
        <div class="name">联系电话：</div>
        <input
          type="text"
          placeholder="请输入联系电话"
          v-model="user.phone"
          required
        />
      </div>
    </div>
    <div class="default acea-row row-middle">
      <div class="select-btn">
        <div class="checkbox-wrapper">
          <label class="well-check"
            ><input
              type="checkbox"
              name="admin"
              value=""
              @click="ChangeIsAdmin"
              :checked="user.is_admin ? true : false"
            /><i class="icon"></i><span class="def">设置为管理员</span></label
          >
        </div>
      </div>
    </div>

    <div class="default acea-row row-middle">
      <div class="select-btn">
        <div class="checkbox-wrapper">
          <label class="well-check"
          ><input
                  type="checkbox"
                  name="check"
                  value=""
                  @click="ChangeIsCheck"
                  :checked="user.is_check ? true : false"
          /><i class="icon"></i><span class="def">设置为核销员</span></label
          >
        </div>
      </div>
    </div>
    <div></div>
    <div class="keepBnt bg-color-red" @click="submit">立即添加</div>
  </div>
</template>
<script type="text/babel">
import { CitySelect } from "vue-ydui/dist/lib.rem/cityselect";
import District from "ydui-district/dist/jd_province_city_area_id";
import {  postServiceAdd } from "@api/merchant";
import attrs, { required, chs_phone } from "@utils/validate";
import { validatorDefaultCatch } from "@utils/dialog";
import dialog from "@utils/dialog";


export default {
  components: {
    CitySelect
  },
  data() {
    return {
      id: this.$route.params.id,
      user: { is_admin: 0 ,is_check:1},
    };
  },
  mounted: function() {
    let id = this.$route.params.id;
    this.id = id;
  },
  methods: {

    async submit() {
      let name = this.user.real_name,
        phone = this.user.phone,
        detail = this.user.detail,
              isCheck = this.user.is_check,
        isAdmin = this.user.is_admin;
      try {
        await this.$validator({
          name: [
            required(required.message("姓名")),
            attrs.range([2, 16], attrs.range.message("姓名"))
          ],
          phone: [
            required(required.message("联系电话")),
            chs_phone(chs_phone.message())
          ],
        }).validate({ name, phone });
      } catch (e) {
        return validatorDefaultCatch(e);
      }
      try {
        let that = this,
          data = {
            id: that.id,
            real_name: name,
            phone:phone,
            is_admin: isAdmin,
            is_check:isCheck,
          };
        postServiceAdd(data).then(function() {
          if (that.id) that.$dialog.toast({ mes: "修改成功" });
          else that.$dialog.toast({ mes: "添加成功" });
          that.$router.go(-1);
        }).catch(function (err) {
          dialog.toast({ mes: err.msg || "订单支付失败" });

        });
      } catch (e) {
        this.$dialog.error(e.msg);
      }
    },
    ChangeIsAdmin: function() {
      this.user.is_admin = !this.user.is_admin;
    },
    ChangeIsCheck: function() {
      this.user.is_admin = !this.user.is_admin;
    },
  }
};
</script>
