
         $(function(){
  
        var ok1=false;
        var ok2=false;
        var ok3=false;
        var ok4=false;
        // 验证商品名称
    $('input[name="name"]').focus(function(){
          $(this).next().text('').removeClass('state1').addClass('state2');
        }).blur(function(){
          if($(this).val().length >= 1 && $(this).val().length <=24 && $(this).val()!=''){
            $(this).next().text('').removeClass('state1').addClass('state4');
            ok1=true;
          }else if($(this).val().length >24){
            $(this).next().text('产品名称在24字以内').removeClass('state4').addClass('state3');
          }
            else{$(this).next().text('请输入产品名称').removeClass('state4').addClass('state3');}
        });

    //验证商品描述
    $('input[name="serviceinfo"]').focus(function(){
          $(this).next().text('').removeClass('state1').addClass('state2');
        }).blur(function(){
          if($(this).val().length >= 1 && $(this).val().length <=100 && $(this).val()!=''){
            $(this).next().text('').removeClass('state1').addClass('state4');
            ok2=true;
          }else if($(this).val().length >100){
            $(this).next().text('商品描述最多输入100字').removeClass('state4').addClass('state3');
          }
            else{$(this).next().text('请输入商品描述').removeClass('state4').addClass('state3');}
        });
 
      //验证规格名称
    $('input[name="nameSpecification"]').focus(function(){
          $("span#spanSpecification").text('').removeClass('state1').addClass('state2');
        }).blur(function(){
          if($(this).val().length >= 1 && $(this).val().length <=8 && $(this).val()!=''){
            $("span#spanSpecification").text('').removeClass('state1').addClass('state4');
            ok3=true;
          }else if($(this).val().length >8){
            $("span#spanSpecification").text('规格名称最多输入8字').removeClass('state4').addClass('state3');
          }
            else{$("span#spanSpecification").text('请输入商品描述').removeClass('state4').addClass('state3');}
        });

        //提交按钮,所有验证通过方可提交
  
        $('.submit').click(function(){
          if(ok1 && ok2 && ok3 && ok4){
            $('form').submit();
          }else{
            return false;
          }
        });
          
      });

    function validationPrice(e, num) {
      var regu = /^[0-9]+\.?[0-9]*$/;
      $("span#spanPrice").text('').removeClass('state3').addClass('state1');
      if (e.value != "") {
        if (!regu.test(e.value)) {
          $("span#spanPrice").text('请输入正确的价格，不可为负数，精确到分').removeClass('state1').addClass('state3');
          e.value = e.value.substring(0, e.value.indexOf('.')- 2);
          e.focus();
        } else {
          if (num == 0) {
            $("span#spanPrice").text('').removeClass('state1').addClass('state3');
            if (e.value.indexOf('.') = -1) {
                $("span#spanPrice").text('').removeClass('state3').addClass('state1');
                e.value = e.value.substr(0, e.value.indexOf('.')+1);
                e.focus();
            }
            if (e.value.indexOf('.') = -1) {
                $("span#spanPrice").text('').removeClass('state3').addClass('state1');
                e.value = e.value.substr(0, e.value.indexOf('.')-1);
                e.focus();
            }
          }
          if (e.value.indexOf('.') > -1) {
            if (e.value.split('.')[1].length > num) {
              e.value = e.value.substring(0, e.value.indexOf('.')+3);
              e.focus();
            }
          }
          
        }
      }
    }


    function validationStock(e, num) {
      var regu = /^[0-9]+\.?[0-9]*$/;
      if (e.value != "") {
        if (!regu.test(e.value)) {
          $("span#spanStock").text('请输入正确的价格，不可为负数，精确到分').removeClass('state1').addClass('state3');
          e.value = e.value.substring(0, e.value.length - 1);
          e.focus();
        } else {
          if (num == 0) {
            $("span#spanStock").text('').removeClass('state1').addClass('state3');
            if (e.value.indexOf('.') > -1) {
              $("span#spanStock").text('').removeClass('state3').addClass('state1');
              e.value = e.value.substring(0, e.value.indexOf('.'));
              e.focus();
            }
          }
          if (e.value.indexOf('.') > -1) {
            if (e.value.split('.')[1].length > num) {
              $("span#spanStock").text('').removeClass('state3').addClass('state1');
              e.value = e.value.substring(0, e.value.length - 1);
              e.focus();
            }
          }
        }
      }
    }

    function validationRestriction(e, num) {
      var regu = /^[0-9]+\.?[0-9]*$/;
      if (e.value != "") {
        if (!regu.test(e.value)) {
          $("span#spanPurchase").text('请输入正确的价格，不可为负数，精确到分').removeClass('state1').addClass('state3');
          e.value = e.value.substring(0, e.value.length - 1);
          e.focus();
        } else {
          if (num == 0) {
            $("span#spanPurchase").text('').removeClass('state1').addClass('state3');
            if (e.value.indexOf('.') > -1) {
              $("span#spanPurchase").text('').removeClass('state3').addClass('state1');
              e.value = e.value.substring(0, e.value.indexOf('.'));
              e.focus();
            }
          }
          if (e.value.indexOf('.') > -1) {
            if (e.value.split('.')[1].length > num) {
              $("span#spanPurchase").text('').removeClass('state3').addClass('state1');
              e.value = e.value.substring(0, e.value.length - 1);
              e.focus();
            }
          }
        }
      }
    }
