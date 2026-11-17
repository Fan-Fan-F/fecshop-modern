(function($){
	"use strict";

	$(document).ready(function(){
		var currentBaseUrl = $(".currentBaseUrl").val() || "";

		// Currency switcher - modern asynchronous call without blocking the UI
		$(".top_currency .currency_list ul li").click(function(){
			var currency = $(this).attr("rel");
			if (!currency) return;
			$.ajax({
				url: currentBaseUrl + "/cms/home/changecurrency?currency=" + encodeURIComponent(currency),
				type: "get",
				async: true
			}).always(function(){
				location.reload();
			});
		});

		// Store language switcher
		$(".top_lang .store_lang").click(function(){
			var currentStore = $(".current_lang").attr("rel");
			var changeStore = $(this).attr("rel");
			var currentUrl = window.location.href;
			if (currentStore && changeStore) {
				var redirectUrl = currentUrl.replace("://" + currentStore, "://" + changeStore);
				location.href = redirectUrl;
			}
		});

		// Ajax get account login info
		var loginInfoUrl = currentBaseUrl + "/customer/ajax";
		var logoutUrl    = $(".logoutUrl").val();
		var product_id   = $(".product_view_id").val() || null;

		$.ajax({
			async: true,
			timeout: 6000,
			dataType: "json",
			type: "get",
			data: {
				"currentUrl": window.location.href,
				"product_id": product_id
			},
			url: loginInfoUrl,
			success: function(data){
				var welcome = $(".welcome_str").val() || "";
				var logoutStr = $(".logoutStr").val() || "";
				if(data && data.loginStatus){
					var customer_name = data.customer_name || "";
					var str = '<span id="welcome">' + welcome + ' ' + customer_name + '</span>';
					str += '<span id="js_isNotLogin">';
					str += '<a href="' + logoutUrl + '" rel="nofollow">' + logoutStr + '</a>';
					str += '</span>';
					$(".login-text").html(str);
				}
				if(data && data.favorite){
					$(".myFavorite_nohove").addClass("act");
					$(".myFavorite_nohove a").addClass("act");
				}
				if(data && data.favorite_product_count){
					$("#js_favour_num").html(data.favorite_product_count);
				}
				if(data && data.csrfName && data.csrfVal && data.product_id){
					$(".product_csrf").attr("name", data.csrfName);
					$(".product_csrf").val(data.csrfVal);
				}
				if(data && data.cart_qty){
					$("#js_cart_items").html(data.cart_qty);
				}
			},
			error: function(){}
		});

		$("#goTop").click(function(){
			$("html,body").animate({scrollTop:0}, "slow");
		});

		$("#goBottom").click(function(){
			var screenb = $(document).height();
			$("html,body").animate({scrollTop:screenb}, "slow");
		});
	});

	// Global helper for programmatic POST submission with automatic CSRF injection
	window.doPost = function(to, p) {
		p = p || {};
		var myForm = document.createElement("form");
		myForm.method = "post";
		myForm.action = to;

		var csrfParam = $('meta[name="csrf-param"]').attr("content") || "_csrf";
		var csrfToken = $('meta[name="csrf-token"]').attr("content");
		if (csrfToken && typeof p[csrfParam] === "undefined") {
			p[csrfParam] = csrfToken;
		}

		for (var key in p){
			if (Object.prototype.hasOwnProperty.call(p, key)) {
				var myInput = document.createElement("input");
				myInput.setAttribute("type", "hidden");
				myInput.setAttribute("name", key);
				myInput.setAttribute("value", p[key]);
				myForm.appendChild(myInput);
			}
		}
		document.body.appendChild(myForm);
		myForm.submit();
		document.body.removeChild(myForm);
	};

})(jQuery);
