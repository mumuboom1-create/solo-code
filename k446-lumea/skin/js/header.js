//判断手机端
var ua = navigator.userAgent;
var ipad = ua.match(/(iPad).*OS\s([\d_]+)/),
	isIphone =!ipad && ua.match(/(iPhone\sOS)\s([\d_]+)/),
	isAndroid = ua.match(/(Android)\s+([\d.]+)/),
	isMobile = isIphone || isAndroid || ipad;


var new_scroll_position = 0;
var last_scroll_position;

window.addEventListener('scroll', function(e) {
	last_scroll_position = window.pageYOffset;//window.scrollY/window.pageYOffset
	if (new_scroll_position < last_scroll_position && last_scroll_position > 50) {
		$(".mheader").removeClass("scroll-top").addClass("scroll-down");
		$(".header").removeClass("scroll-top").addClass("scroll-down");
	} else if (new_scroll_position > last_scroll_position) {
		$(".mheader").removeClass("scroll-down").addClass("scroll-top");
		$(".header").removeClass("scroll-down").addClass("scroll-top");
	}

	var targetTop = $(this).scrollTop();
	if(targetTop <= 0){
		$(".mheader").removeClass("scroll-top").removeClass("scroll-down");
		$(".header").removeClass("scroll-top").removeClass("scroll-down");
	}
	new_scroll_position = last_scroll_position;
})

//顶部菜单
$(document).ready(function(){
	$(".header-search").click(function(){
		$(".search-box").stop().fadeIn();
	});
	$(".search-box-bg").click(function(){
		$(".search-box").stop().fadeOut();
	});
});

function header_menu(ulclass){
	$(document).ready(function(){
		$(ulclass+' li').hover(function(){
			$(this).children("ul").show();
		},function(){
			$(this).children("ul").hide();
		});
	});
}
header_menu('.header-nav');

//手机菜单
$('.nav-trigger').on('click', function(event){
	event.preventDefault();
	toggleNav();

});

function toggleNav(bool) {
	// $(".nav-inner").hide();
	// $(".shownav-c").removeClass('cur');
	$('.nav-container').toggleClass('is-visible', bool);
	$('main').toggleClass('scale-down', bool);
	$('html').toggleClass('open-menu', bool);
}

function shownav(navid) {
	$(navid).toggleClass("cur");
};
function closenav(navid) {
	$(navid).removeClass("cur");
};

$(function(){
	$('.onenav .twonav').click(function() {
		if($(this).siblings('.nav-inner').length > 0) {
			$('.onenav li>a').toggleClass('curr');
			$(this).siblings('.nav-inner').stop(true, true).slideToggle();
			if($(this).siblings('.nav-inner').css('display') == "block") {
				$(this).parents('li').siblings().find('a').removeClass('curr');
				$(this).parents('li').siblings().find('.nav-inner').slideUp();
			}
		}
	});
})
