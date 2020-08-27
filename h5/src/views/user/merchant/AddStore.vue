<template>
  <div class="addAddress absolute">
    <div class="list">

      <div class="item acea-row row-between-wrapper">
        <div class="name">商家名称</div>
        <input type="text" placeholder="商家名称" v-model="store.mer_name" required/>
      </div>
      <div class="item acea-row row-between-wrapper">
        <div class="name">商家联系人</div>
        <input type="text" placeholder="商家联系人" v-model="store.link_name" required />
      </div>
      <div class="item acea-row row-between-wrapper">
        <div class="name">推荐人id</div>
        <input type="text" placeholder="商家推荐人id" v-model="userInfo.spread_uid" required />
      </div>
      <div class="item acea-row row-between-wrapper">
        <div class="name">商家联系电话</div>
        <input type="text" placeholder="请输入联系电话" v-model="store.link_phone" required />
      </div>
      <div class="item acea-row row-between-wrapper">
        <div class="name">门店名称</div>
        <input type="text" placeholder="请输入门店名称" v-model="store.name" required/>
      </div>

      <div class="item acea-row row-between-wrapper row-row">
        <div class="name">门头照片</div>
        <VueCoreImageUpload
                class="btn btn-primary"
                :crop="false"
                compress="80"
                @imageuploaded="imageuploaded"
                :headers="headers"
                :max-file-size="5242880"
                :credentials="false"
                inputAccept="image/*"
                inputOfFile="file"
                :url="url"
                ref="upImg"
        >
          <div class="pictrue acea-row row-center-wrapper row-row">
            <img height="40px" :src="store.image" alt="">
            <span class="iconfont icon-icon25201"></span>
            <div>上传照片</div>
          </div>
        </VueCoreImageUpload>
      </div>
      <div class="item acea-row row-between-wrapper row-row">
        <div class="name">营业执照</div>
        <VueCoreImageUpload
                class="btn btn-primary"
                :crop="false"
                compress="80"
                @imageuploaded="limageuploaded"
                :headers="headers"
                :max-file-size="5242880"
                :credentials="false"
                inputAccept="image/*"
                inputOfFile="file"
                :url="url"
                ref="upImg"
        >
          <div class="pictrue acea-row row-center-wrapper row-row">
            <img height="40px" :src="store.license" alt="">
            <span class="iconfont icon-icon25201"></span>
            <div>上传照片</div>
          </div>
        </VueCoreImageUpload>
      </div>
      
      <div class="item acea-row row-between-wrapper row-row">
        <div class="name">身份证头像页</div>
        <VueCoreImageUpload
                class="btn btn-primary"
                :crop="false"
                compress="80"
                @imageuploaded="idimageuploaded"
                :headers="headers"
                :max-file-size="5242880"
                :credentials="false"
                inputAccept="image/*"
                inputOfFile="file"
                :url="url"
                ref="upImg"
        >
          <div class="pictrue acea-row row-center-wrapper row-row">
            <img height="40px" :src="store.idCardz" alt="">
            <span class="iconfont icon-icon25201"></span>
            <div>上传照片</div>
          </div>
        </VueCoreImageUpload>
      </div>
      <div class="item acea-row row-between-wrapper row-row">
        <div class="name">身份证国徽页</div>
        <VueCoreImageUpload
                class="btn btn-primary"
                :crop="false"
                compress="80"
                @imageuploaded="idgimageuploaded"
                :headers="headers"
                :max-file-size="5242880"
                :credentials="false"
                inputAccept="image/*"
                inputOfFile="file"
                :url="url"
                ref="upImg"
        >
          <div class="pictrue acea-row row-center-wrapper row-row">
            <img height="40px" :src="store.idCardf" alt="">
            <span class="iconfont icon-icon25201"></span>
            <div>上传照片</div>
          </div>
        </VueCoreImageUpload>
      </div>
      <div class="item acea-row row-between-wrapper row-row">
        <div class="name">许可证</div>
        <VueCoreImageUpload
                class="btn btn-primary"
                :crop="false"
                compress="80"
                @imageuploaded="ximageuploaded"
                :headers="headers"
                :max-file-size="5242880"
                :credentials="false"
                inputAccept="image/*"
                inputOfFile="file"
                :url="url"
                ref="upImg"
        >
          <div class="pictrue acea-row row-center-wrapper row-row">
            <img height="40px" :src="store.xukeImg" alt="">
            <span class="iconfont icon-icon25201"></span>
            <div>上传照片</div>
          </div>
        </VueCoreImageUpload>
      </div>


      <div class="item acea-row row-between-wrapper">
        <div class="name">门店简介</div>
        <input type="text" placeholder="请输入门店简介" v-model="store.introduction" required/>
      </div>


      <div class="item acea-row row-between-wrapper">
        <div class="name">门店电话</div>
        <input type="text" placeholder="请输入联系电话" v-model="store.phone" required />
      </div>
      <div class="item acea-row row-between-wrapper">
        <div class="name">所在地区</div>
        <div class="picker acea-row row-between-wrapper select-value form-control" >
          <div class="address">
            <div slot="right" @click.stop="show2 = true">
              {{ model2 || "请选择门店地址" }}
            </div>
            <CitySelect
              v-model="show2"
              :callback="result2"
              :items="district"
              provance=""
              city=""
              area=""
            ></CitySelect>
          </div>
          <div class="iconfont icon-dizhi font-color-red"></div>
        </div>
      </div>


      <div class="item acea-row row-between-wrapper">
        <div class="name">详细地址</div>
        <input type="text" placeholder="请填写具体地址" v-model="store.detailed_address" required/>
      </div>




      <div class="item acea-row row-between-wrapper row-row" >
        <div class="name">营业时间</div>
        <date-time class="timeSelect" type="time" v-model="day_time_start" style="width:30%;"></date-time>
        <date-time class="timeSelect" type="time" v-model="day_time_end"  style="width:30%;"></date-time>
      </div>
    </div>
    <!--
    <div class="default acea-row row-middle">
      <div class="select-btn">
        <div class="checkbox-wrapper">
          <label class="well-check"
            ><input
              type="checkbox"
              name=""
              value=""
              @click="ChangeIsShow"
              :checked="store.is_show ? true : false"
            /><i class="icon"></i><span class="def">展现</span></label
          >
        </div>
      </div>
    </div>
    -->
    <div></div>


    <div class="keepBnt bg-color-red" @click="submit">立即提交</div>
  </div>
</template>
<script type="text/babel">
import { CitySelect } from "vue-ydui/dist/lib.rem/cityselect";
import District from "ydui-district/dist/jd_province_city_area_id";
import { getAddress, postAddress,getUser } from "@api/user";
import {postStoreAdd, getStoreInfo} from "@api/merchant"
import attrs, { required, chs_phone } from "@utils/validate";
import { validatorDefaultCatch } from "@utils/dialog";
import { openAddress } from "@libs/wechat";
import { trim, VUE_APP_API_URL, isWeixin } from "@utils";

import {DateTime} from 'vue-ydui/dist/lib.rem/datetime';
import VueCoreImageUpload from "vue-core-image-upload";

export default {
  components: {
    CitySelect,
    VueCoreImageUpload,
    DateTime
  },
  data() {
    return {
      url: `${VUE_APP_API_URL}/upload/image`,
      headers: {
        "Authori-zation": "Bearer " + this.$store.state.app.token
      },
      show2: false,
      model2: "",
      image:'',
      license:'',
      district: District,
      day_time_start:"00:00",
      day_time_end:"00:00",
      userInfo: {},
      id: 0,
      parent_id: 0,
      store: {
        is_default: 0 ,
        is_show:1,
        image:'',
        license:'',
        idCardz:'',
        idCardf:'',
        xukeImg:'',
      },
      address: {},
      isWechat: isWeixin()
    };
  },
  computed: {
    // 计算属性的 getter
    day_time: function () {
      return this.day_time_start + ':00 - ' + this.day_time_end + ":00";
    }
  },

  mounted: function() {
    let id = this.$route.params.id;
    this.id = id;
    document.title = !id ? "新增门店" : "修改门店";
    this.User();
    this.getUserAddress();
  },
  methods: {
   User: function() {
      let that = this;
      getUser().then(res => {
        that.userInfo = res.data;
      });
    },
    getUserAddress: function() {
      if (this.id==0) return false;
      let that = this;
      getStoreInfo({id:that.id}).then(res => {
        that.store = res.data;
        that.model2 = res.data.province + " " + res.data.city + " " + res.data.district;
        that.address.province = res.data.province;
        that.address.city = res.data.city;
        that.address.district = res.data.district;
      });
    },
    getAddress() {
      openAddress().then(userInfo => {
        this.$dialog.loading.open();
        postAddress({
          id: this.id,
          real_name: userInfo.userName,
          phone: userInfo.telNumber,
          address: {
            province: userInfo.provinceName,
            city: userInfo.cityName,
            district: userInfo.countryName
          },
          detail: userInfo.detailInfo,
          is_default: 1,
          post_code: userInfo.postalCode
        })
          .then(() => {
            this.$dialog.loading.close();
            this.$dialog.toast({ mes: "添加成功" });
            this.$router.go(-1);
          })
          .catch(err => {
            this.$dialog.loading.close();
            this.$dialog.error(err.msg || "添加失败");
          });
      });
    },
    async submit() {
      let name = this.store.name,
        phone = this.store.phone,
        model2 = this.model2,
        detail = this.store.detailed_address,
        isShow = this.store.is_show,
        image = this.store.image,
        license=this.store.license,
        idCardz=this.store.idCardz,
        idCardf=this.store.idCardf;
      try {
        await this.$validator({
          name: [
            required(required.message("姓名")),
            attrs.range([2, 16], attrs.range.message("姓名"))
          ],
          phone: [
            required(required.message("联系电话")),
           // chs_phone(chs_phone.message())
          ],
          image: [required("请上传门头照片")],
          license: [required("请上传营业执照")],
          idCardz: [required("请上经办人身份证头像页")],
          idCardf: [required("请上经办人身份证国徽页")],
          model2: [required("请选择地址")],
          detail: [required(required.message("具体地址"))]
        }).validate({ name, phone,image,license,idCardz,idCardf, model2, detail });
      } catch (e) {
        return validatorDefaultCatch(e);
      }
      /*
      if(!image){
        this.$dialog.toast({ mes: "请上传门头照片" });
      }*/
      try {
        let that = this,
          data = {
            id :that.id,
            name: name,
            introduction: this.store.introduction,
            phone: phone,
            image:this.store.image,
            license:this.store.license,
            idCardz:this.store.idCardz,
            idCardf:this.store.idCardf,
            xukeImg:this.store.xukeImg,
            address: this.address,
            detailed_address: detail,
            is_show: isShow,
            post_code: "",
            valid_time:this.store.valid_time,
            day_time:this.day_time,
            mer_name:this.store.mer_name,
            link_name:this.store.link_name,
            parent_id:this.store.parent_id,
            link_phone:this.store.link_phone,
          };
        postStoreAdd(data).then(function() {
          if (that.id) that.$dialog.toast({ mes: "修改成功" });
          else that.$dialog.toast({ mes: "添加成功" });
          that.$router.go(-1);
        });
      } catch (e) {
        this.$dialog.error(e.msg);
      }
    },
    imageuploaded(res) {
      if (res.status !== 200)
        return this.$dialog.error(res.msg || "上传图片失败");
        this.store.image =res.data.url
    },
    limageuploaded(res) {
      if (res.status !== 200)
        return this.$dialog.error(res.msg || "上传图片失败");
        this.store.license =res.data.url
    },
    idimageuploaded(res) {
      if (res.status !== 200)
        return this.$dialog.error(res.msg || "上传图片失败");
        this.store.idCardz =res.data.url
    },
    idgimageuploaded(res) {
      if (res.status !== 200)
        return this.$dialog.error(res.msg || "上传图片失败");
        this.store.idCardf =res.data.url
    },
    ximageuploaded(res) {
      if (res.status !== 200)
        return this.$dialog.error(res.msg || "上传图片失败");
        this.store.xukeImg =res.data.url
    },
    ChangeIsShow: function() {
      this.store.is_show = !this.store.is_is_show;
    },
    result2(ret) {
      this.model2 = ret.itemName1 + " " + ret.itemName2 + " " + ret.itemName3;
      this.address.province = ret.itemName1;
      this.address.city = ret.itemName2;
      this.address.district = ret.itemName3;
    }
  }
};
</script>

<style>
  .timeSelect{
    width: 2rem;
  }
  .yd-datetime-input{width: 30%;}

</style>
