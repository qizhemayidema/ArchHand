var loginHTML = '<div class="login_list">' +
    '<input type="text" class="fz14 c3" data-reg="/^1[3-9]\\d{9}$/" id="tel" data-validation="true" data-error="请输入手机号"  placeholder="请输入手机号">' +
    '</div>' +
    '<div class="login_list">' +
    '<input id="pwd" type="password" class="fz14 c3" placeholder="请输入密码" data-reg="/\\S/" data-validation="true" data-error="请输入密码" placeholder="请输入密码">' +
    '</div>' +
    '<div class="submit_btn">' +
    '<button type="button" class="" onclick="submit(\'/api/Login/login\');">登录</button>' +
    '</div>' +
    '<div class="w314 fz14 lh30 c9">' +
    '<a href="javascript:void(0);" class="ced3237" onclick="showMarks(rePassWordHTML,2);">忘记密码？</a>' +
    '<a href="javascript:void(0);" class="ced3237 tar" onclick="showMarks(registerHTML,0);">去注册</a>' +
    '</div>';
var registerHTML = '<div class="login_list">' +
    '<input type="text" class="fz14 c3" data-reg="/^1[3-9]\\d{9}$/" id="tel" data-validation="true" data-error="请输入手机号" placeholder="请输入手机号">' +
    '</div>' +
    '<div class="login_list">' +
    '<div class="oh">' +
    '<div class="code_inp fl">' +
    '<input id="code" type="text" data-reg="/\\S/" data-validation="true" data-error="请输入验证码" class="fz14 c3" placeholder="请输入验证码">' +
    '</div>' +
    '<button type="button" class="code_btn" onclick="getCode(\'register\',\'url\');">获取验证码</button>' +
    '</div>' +
    '</div>' +
    '<div class="login_list">' +
    '<input id="pwd" type="password" class="fz14 c3" data-reg="/\\S/" data-validation="true" data-error="请输入密码" placeholder="请输入密码">' +
    '</div>' +
    '<div class="login_list">' +
    '<input id="pwd1" type="password" class="fz14 c3" data-reg="/\\S/" data-validation="true" data-error="请确认密码" placeholder="请确认密码">' +
    '</div>' +
    '<div class="submit_btn">' +
    '<button type="button" class="" onclick="submit(\'/api/Login/register\');">注册</button>' +
    '</div>' +
    '<div class="w314 fz14 lh30 c9">已有账号？<a href="javascript:void(0);" class="ced3237" onclick="showMarks(loginHTML,1);">登录</a></div>';

var rePassWordHTML = '<div class="login_list">' +
    '<input type="text" class="fz14 c3" data-reg="/^1[3-9]\\d{9}$/" id="tel" data-validation="true" data-error="请输入手机号"  placeholder="请输入手机号">' +
    '</div>' +
    '<div class="login_list">' +
    '<div class="oh">' +
    '<div class="code_inp fl">' +
    '<input id="code" type="text" class="fz14 c3" placeholder="请输入验证码" data-reg="/\\S/" data-validation="true" data-error="请输入验证码">' +
    '</div>' +
    '<button type="button" class="code_btn" onclick="getCode(\'rePassWord\',\'url\');">获取验证码</button>' +
    '</div>' +
    '</div>' +
    '<div class="login_list">' +
    '<input id="pwd" type="password" class="fz14 c3" data-reg="/\\S/" data-validation="true" data-error="请输入密码" placeholder="请输入密码">' +
    '</div>' +
    '<div class="login_list">' +
    '<input id="pwd1" type="password" class="fz14 c3" data-reg="/\\S/" data-validation="true" data-error="请确认密码" placeholder="请确认密码">' +
    '</div>' +
    '<div class="submit_btn">' +
    '<button type="button" class="" onclick="submit(\'/api/Login/rePwd\');">找回密码</button>' +
    '</div>' +
    '<div class="w314 fz14 lh30 c9">已有账号？<a href="javascript:void(0);" class="ced3237" onclick="showMarks(loginHTML,1);">登录</a></div>';

function appendHtml(name) {
    var boxHTML = '<div class="login_warpper">' +
        '<div class="login_box height_auto">' +
        '<div class="login_close"><img src="images/close.png" alt="" onclick="close_box()"></div>' +
        '<div class="tac">' +
        '<img src="images/logo_c.png" alt="" class="mb20 login_logo">' +
        '</div>' +
        '<div class="hook_tab">' +
        name +
        '</div>' +
        '</div>';
    $("body").append(boxHTML);
}



function close_box() { //关闭层
    $(".login_warpper").remove();
}

function showMarks(name,type) { //打开登录层
		if(type){
			tk_login_type=type
		}else{
			tk_login_type=1
		}
		console.log(type)
    var dom = $(".login_warpper");
    if (dom.length > 0) { //元素已存在
        $(".hook_tab").html(name);
    } else { //元素不存在
        appendHtml(name);
    }
}
var loginkg=0
function submit(url) {
    var isSubmit = true;
    $('.login_warpper input').each(function(key, el) {
        if ($(el).attr('data-validation') == 'true') {
            var reg = eval($(el).attr('data-reg'));
            var msg = $(el).attr('data-error');
            var value = $(el).val();
            if (!reg.test(value)) {
                $.alert(msg);
                isSubmit = false;
                return false;
            }
        }
    });
    if (isSubmit) {
			console.log(url)
        if(url=="/api/Login/register"){ //注册
					if($('#pwd').val().length<6){
						layer.msg('密码长度要大于6')
						return
					}
					if($('#pwd').val()!==$('#pwd1').val()){
						layer.msg('两次密码不一致')
						return
					}
					if(loginkg==1){
						layer.msg('请勿重复点击')
						return
					}else{
						loginkg=1
					}
					$.ajax({
					  type: "post",
					  url: IPurl+url,
					  data: {
							phone:$("#tel").val(),
							code:$('#code').val(),
							password:$('#pwd').val(),
							re_password:$('#pwd1').val()
						},
					  success: function (res) {
					    console.log(res);
					    if(res.code==1){
								console.log('ok')
								 layer.msg('注册成功')
								 loginkg=0
								 showMarks(loginHTML,1)
					    }else{
								loginkg=0
								if(res.msg){
									layer.msg(res.msg)
								}else{
									layer.msg('操作失败')
								}
							}
					
					  },
						error:function(err){
							loginkg=0
							layer.msg('操作失败')
							console.log(err)
						}
					})
				}else if(url=="/api/Login/login"){ //登录
					if($('#pwd').val().length<6){
						layer.msg('密码长度要大于6')
						return
					}
					if(loginkg==1){
						layer.msg('请勿重复点击')
						return
					}else{
						loginkg=1
					}
					$.ajax({ //获取csrf
						type: "post",
						url:IPurl+'/api/Login/getCsrf',
						data:{
							phone:$("#tel").val(),
						},
						success: function (res) {
						  console.log(res);
						  if(res.code==1){
								console.log('ok')
								 
								 $.ajax({//login
								   type: "post",
								   url: IPurl+url,
								   data: {
										 csrf:res.msg,
								 		phone:$("#tel").val(),
								 		password:$('#pwd').val()
								 	},
								   success: function (res) {
								     console.log(res);
								     if(res.code==1){
								 			console.log('ok')
								 			 layer.msg('登录成功')
								 			 loginkg=0
											 setCookie('token',res.msg,1)
											 setCookie('username',res.data.nickname,1)
											 setCookie('userimg',res.data.avatar_url,1)
											 setCookie('userzsb',res.data.integral,1)
											 setTimeout(function(){
											 									 window.location.href=""
											 },1000)
								 			 // window.location.href=""
								     }else{
								 			loginkg=0
								 			if(res.msg){
								 				layer.msg(res.msg)
								 			}else{
								 				layer.msg('操作失败')
								 			}
								 		}
								 
								   },
								 	error:function(err){
								 		loginkg=0
								 		layer.msg('操作失败')
								 		console.log(err)
								 	}
								 })
								 
						  }else{
								loginkg=0
								if(res.msg){
									layer.msg(res.msg)
								}else{
									layer.msg('操作失败')
								}
							}
											
						},
						error:function(err){
							loginkg=0
							layer.msg('操作失败')
							console.log(err)
						}
					})
					
				}else if(url=="/api/Login/rePwd"){  //修改密码
					if($('#pwd').val().length<6){
						layer.msg('密码长度要大于6')
						return
					}
					if($('#pwd').val()!==$('#pwd1').val()){
						layer.msg('两次密码不一致')
						return
					}
					if(loginkg==1){
						layer.msg('请勿重复点击')
						return
					}else{
						loginkg=1
					}
					$.ajax({
					  type: "post",
					  url: IPurl+url,
					  data: {
							phone:$("#tel").val(),
							code:$('#code').val(),
							password:$('#pwd').val()
						},
					  success: function (res) {
					    console.log(res);
					    if(res.code==1){
								console.log('ok')
								 layer.msg('修改成功')
								 loginkg=0
								 showMarks(loginHTML,1)
					    }else{
								loginkg=0
								if(res.msg){
									layer.msg(res.msg)
								}else{
									layer.msg('操作失败')
								}
							}
					
					  },
						error:function(err){
							loginkg=0
							layer.msg('操作失败')
							console.log(err)
						}
					})
				}
    }

};
//setcookie
function setCookie(cname,cvalue,exdays){
  var d = new Date();
  d.setTime(d.getTime()+(exdays*24*60*60*1000));
  var expires = "expires="+d.toGMTString();
  document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie(cname){
  var name = cname + "=";
  var ca = document.cookie.split(';');
  for(var i=0; i<ca.length; i++) 
  {
    var c = ca[i].trim();
    if (c.indexOf(name)==0) return c.substring(name.length,c.length);
  }
  return "";
}
var isGetCode = false; //验证码标注
function getCode(op, url) { //获取验证码
    if (!isGetCode) {
        var userreg = /^1[3-9]\d{9}$/;
        if (!userreg.test($("#tel").val())) {
            $.alert('电话号码不正确')
            return;
        }
        $.ajax({
          type: "post",
          url: IPurl+"/api/Login/getCode",
          data: {
        		phone:$("#tel").val()
        	},
          success: function (res) {
            console.log(res);
            if(res.code==1){
        			console.log('ok')
        			layer.msg('发送成功')
        			isGetCode = true;
        			var time = 60;
        			 var countDown = setInterval(function() {
        			    if (time == 1) {
        			        time = 60;
        			        $(".code_btn").html('获取验证码');
        			        clearInterval(countDown);
        			        isGetCode = false;
        			    // } else {
        			        time--;
        			        $(".code_btn").html(time + 's');
        			    }
        			}, 1000);
            }else{
							if(res.msg){
								layer.msg(res.msg)
							}else{
								layer.msg('操作失败')
							}
						}
        
          },
					error: function(err){
						layer.msg('操作失败')
						console.log(err)
					}
        })
    }
}