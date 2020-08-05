<template>
  <div>
    <div class="star mb-10" :class="starType">
        <span 
          class="star-item" 
          v-for = "(itemClass,i) in itemClassess" 
          :class="itemClass" 
          :key="i"
        ></span>
        <span>{{ score }}分</span>
    </div>
  </div>
</template>

<script>
     const LENGTH = 5;//星星个数
     const CLS_ON = "on";//满星状态
     const CLS_HALF = "half";//半星状态
     const CLS_OFF = "off";//无星状态

    export default {
         props: {
            size: {
                type : Number//参数：尺寸
            },
            score: {
                type : Number//参数：评分
            }
         },
         computed: {
            starType(){//设置星星尺寸
                return "star-" + this.size;
            },
            itemClassess(){
                let result = [];//记录状态的数组
                let score = Math.floor(this.score * 2) / 2;
                let hasDecimal = score % 1 !==0;
                let integer = Math.floor(score);//向下取整
                //全星
                for(let i = 0; i < integer; i++){
                    result.push(CLS_ON);
                }
                //半星
                if(hasDecimal){
                    result.push(CLS_HALF);
                }
                //无星
                if(result.length < LENGTH){
                    result.push(CLS_OFF);
                }
                return result;
            }
         }
    }
</script>

<style>
    .star {
        display: flex;
        width: 100%;
        margin-bottom:0.2rem;
    }
    .star-48 .star-item { 
        width: 20px;
        height: 20px;
        background-repeat: no-repeat;
        background-size: 20px 20px;
    }
    .star-48 .star-item:last-child {
        margin-right: 0px;
    }
    .star-48 .star-item.on {
        background-image: url("http://oss.dshqfsc.com/eda5c202008051748281568.png");
    }
    .star-48 .star-item.half {
        background-image: url("http://oss.dshqfsc.com/7bf2e202008051748284332.png");
    }
    .star-48 .star-item.off {
        background-image: url("http://oss.dshqfsc.com/1a09220200805175133424.png");
    }

    .star-36 .star-item { 
        width: 15px;
        height: 15px;
        margin-right: 6px;
        background-repeat: no-repeat;
        background-size: 15px 15px;
    }
    .star-36 .star-item:last-child {
        margin-right: 0px;
    }
    .star-36 .star-item:last-child {
        margin-right: 0px;
    }
    .star-36 .star-item.on {
        background-image: url("https://ss3.bdstatic.com/70cFv8Sh_Q1YnxGkpoWK1HF6hhy/it/u=1264157627,2023353298&fm=26&gp=0.jpg");
    }
    .star-36 .star-item.half {
        background-image: url("https://ss1.bdstatic.com/70cFvXSh_Q1YnxGkpoWK1HF6hhy/it/u=3837197790,4152490133&fm=15&gp=0.jpg");
    }
    .star-36 .star-item.off {
        background-image: url("https://ss3.bdstatic.com/70cFv8Sh_Q1YnxGkpoWK1HF6hhy/it/u=3377642465,2658946556&fm=26&gp=0.jpg");
    }
</style>