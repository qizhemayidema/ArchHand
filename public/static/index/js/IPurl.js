//接口地址
var IPurl = "http://archhand.com:8000/";

function getNowCanshu() {
  var aa= window.location.href;//返回当前页面的url
  var urlinfo =aa.replace("#"," ");
  var canshu = function () {
    var ind = urlinfo.indexOf('?');//返回某个指定的字符串值在字符串中首次出现的位置
    var cs = urlinfo.substr(ind + 1);
    var tempobj = new Object();
    var csarr = cs.split("&");
    jQuery.each(csarr, function (i, v) {
      var temparr = v.split("=");
      var objname = temparr[0];
      tempobj[objname] = temparr[1];
    });
    return tempobj;
  }();
  // console.log(canshu);
  return canshu;
}
var url={
		index:'index.html',
		shequ:'shequ.html',
		yunku:'yunku.html',
		ketang:'ketang.html',
		vip:'VIP.html',
		help:'bangzhu.html',
		about:'about.html',
		qiandao:'qiandao.html',
		detail:'detail.html',
		mymsg:'mymsg.html',
		myshequ:'myshequ.html',
		myyunku:'myyunku.html',
		myaccount:'myaccount.html',
		more:'more.html',
		sousuo_ketang:'sousuo_ketang.html',
		kechengxq:'kechengxq.html',
		buy_history:'my_buy_history.html',
		fabu:'fabu.html',
		yinsi:'yinsi.html',
		zhaopin:'zhaopin.html',
		lxwm:'lxwm.html',
	}
	var login=false
	
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
	//退出登录
	function logout(){
		//询问框
	
		layer.confirm('您确定要退出吗？', {
			btn: ['确定','取消'] //按钮
		}, function(){
			setCookie('token','',-1)
			layer.msg('退出成功')
			setTimeout(function() {
				window.location.href=""
			}, 1000);
		}, function(){
			// layer.msg('也可以这样', {
			// 	time: 20000, //20s后自动关闭
			// 	btn: ['明白了', '知道了']
			// });
		});
	}
	//退出登录去首页
	function logout_goindex(){
		setCookie('token','',-1)
		layer.msg('登录权限过期，请重新登录')
		setTimeout(function() {
			window.location.href="index.html"
		}, 1000);
	}
	function quanxian(){
		// if(!getCookie('token')==''||getCookie('token')===null||getCookie('token')===undefined){
		if(!getCookie('token')){
			layer.msg('请先登录账号!')
			return false
		}else{
			console.log(getCookie('token'))
		}
	}
	
	function retCookie(){
		var that =this
		if(getCookie('token')==''||getCookie('token')===null||getCookie('token')===undefined){
			this.login=false
			layer.msg('登录信息已过期，请先登录账号!')
			return
		}
		///api/User/basicInfo
		$.ajax({
		  type: "post",
		  url: IPurl+'api/User/basicInfo',
		  data: {
				token:getCookie('token'),
			},
		  success: function (res) {
		    console.log(res);
				if(res.code==-1){
					logout_goindex()
					window.location.href="index.html"
				}
		    if(res.code==1){
					setCookie('username',res.data.nickname,1)
					setCookie('userimg',res.data.avatar_url,1)
					setCookie('userzsb',res.data.integral,1)
		    }else{
					if(res.msg){
						layer.msg(res.msg)
					}else{
						layer.msg('获取失败')
					}
				}
		
		  },
			error:function(err){
				layer.msg('获取失败')
				console.log(err)
			}
		})
	}
	// var AllPlate

	// $(function(){
		function getAllPlate(){
			$.ajax({
			  type: "post",
			  url: IPurl+'/api/Forum/getAllPlate',
			  data: {},
			  success: function (res) {
			    console.log(res);
					if(res.code==-1){
						logout_goindex()
						window.location.href="index.html"
					}
			    if(res.code==1){
						// obj=res.data
						return res.data
			    }else{
						if(res.msg){
							layer.msg(res.msg)
						}else{
							layer.msg('获取失败')
						}
					}
			
			  },
				error:function(err){
					layer.msg('获取失败')
					console.log(err)
				}
			})
		}
		
	// })
	
	
	//首页
	function getIConfig(){
		$.ajax({
			type: "post",
			url: IPurl + '/api/config',
			data: {},
			success: function(res) {
				console.log(res);
				if (res.code == -1) {
					logout_goindex()
					window.location.href = "index.html"
				}
				if (res.code == 1) {
					// IConfig = res.data
					localStorage.setItem("IConfig",res.data); 
					// return res.data
				} else {
					if (res.msg) {
						layer.msg(res.msg)
					} else {
						layer.msg('获取失败')
					}
				}
		
			},
			error: function(err) {
				layer.msg('获取失败')
				console.log(err)
			}
		})
	}
	(function ($) {
    getIConfig()
})(jQuery);
	
	
	$('#search_input').bind('keyup', function(event) {
	　　if (event.keyCode == "13") {
	　　　　//回车执行查询
	　　　　$('#search_button').click();
	　　}
	});