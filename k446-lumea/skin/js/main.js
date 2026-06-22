$(function () {

    new WOW().init();


    //首页公司简介
    $('.in-company .sum span ').countUp({
        delay: 10,
        time: 2000
    });


    //首页Banner
    var width=$(window).width();
    // 获取第一个视频时长
    if(/Android|webOS|iPhone|ipad|iPod|BlackBerry/i.test(navigator.userAgent)) {

    }else {
        $(".in-banner .swiper-slide").each(function(){
            if($(this).has('video').length){
                $(this).find('video').attr('id','video');
                return false;
            }
        });
    }
    var audioE2  = document.getElementById("video");
    // 如果swiper有视频
    if(audioE2){
        // 上传了视频,等待视频加载完
        audioE2 .onloadedmetadata = function() {
            var tol=audioE2.duration;
            // tol=tol*1000;
            var swiper1 = new Swiper('#swiper1', {
                speed:1000,
                loop:true,
                // autoplay:false,
                autoplay: {
                    delay: 5000,
                    stopOnLastSlide: false,
                    disableOnInteraction: false,
                },
                navigation: {
                    nextEl: '.in-banner .banner-next',
                    prevEl: '.in-banner .banner-prev',
                },
                pagination: {
                    el: '.in-banner .swiper-pagination',
                    clickable: true,
                },
                on: {
                    init: function(){
                        $('.in-banner .swiper-slide').eq(this.activeIndex).find('.text').addClass('active');
                        swiperAnimateCache(this);
                        swiperAnimate(this);
                    },
                    slideChangeTransitionStart: function(){
                        $('.in-banner .swiper-slide').find('.text').removeClass('active').eq(this.activeIndex).addClass('active').siblings();
                        var _target = this;
                        var curVideo = this.$el.find(".swiper-slide-active").find("video");
                        // tol=curVideo.duration;
                        //暂停所有视频
                        function pauseAll(ele){
                            ele.find("video").each(function(){
                                $(this)[0].pause();
                            });
                        }
                        pauseAll(this.$el);
                        //轮播间隔时间
                        console.log(tol)
                        _target.params.autoplay.delay = tol*1000;
                        //判断当前激活元素是否有视频
                        if(curVideo.length > 0){
                            console.log("有视频！");
                            this.autoplay.stop();
                            curVideo[0].currentTime = 0;
                            curVideo[0].play();
                            curVideo[0].removeEventListener('ended', function (){}, false);
                            curVideo[0].addEventListener('ended', function (){
                                if($('.in-banner .sign').hasClass('active')){
                                    // 此时是暂停状态
                                }else{
                                    _target.slideNext();
                                }

                            }, false);
                        }else{
                            console.log("无视频！");
                            _target.params.autoplay.delay=5000;
                            _target.autoplay.start();
                            $('.in-banner .sign').removeClass('active');
                            flag=true;
                        }

                    },
                    slideChangeTransitionEnd: function(){
                                     swiperAnimate(this);
                                 },
                },
            });
        }
    }else{
        // 没上传视频
        var swiper1 = new Swiper('#swiper1', {
            speed:1000,
            loop:true,
            // autoplay:false,
            autoplay: {
                delay: 5000,
                stopOnLastSlide: false,
                disableOnInteraction: false,
            },
            navigation: {
                nextEl: '.in-banner .banner-next',
                prevEl: '.in-banner .banner-prev',
            },
            pagination: {
                el: '.in-banner .swiper-pagination',
                clickable: true,
            },
            on: {
                init: function(){
                    $('.in-banner .swiper-slide').eq(this.activeIndex).find('.text').addClass('active');
                    swiperAnimateCache(this);
                    swiperAnimate(this);
                },
                slideChangeTransitionStart: function(){
                    $('.in-banner .swiper-slide').find('.text').removeClass('active').eq(this.activeIndex).addClass('active').siblings();
                },
                slideChangeTransitionEnd: function(){
                    swiperAnimate(this);
                },
            },
        });
    }


    // 首页新闻
    var news = new Swiper(".in-news .news-latest", {
        // autoplay: {
        // 	delay: 3000,
        // },
        speed: 800,
        effect: 'fade',
        loop: true,
        observer: true,
        observeParents: true,
        observeSlideChildren: true,
        on: {
            init: function () {
                swiperAnimateCache(this); //隐藏动画元素
                this.emit('slideChangeTransitionStart');//在初始化时触发一次slideChangeTransitionStart事件
                this.emit('slideChangeTransitionEnd');//在初始化时触发一次slideChangeTransitionStart事件
            },
            slideChangeTransitionStart: function () {
                var i = parseInt(this.realIndex);
                $('.news-latest .swiper-slide.swiper-slide-active').addClass('on').siblings().removeClass('on');
                $(".news-item").eq(i).addClass('on').siblings().removeClass('on');
            },
            slideChangeTransitionEnd: function () {
                swiperAnimate(this); //每个slide切换结束时运行当前slide动画
            },
        },
    });
    $('.news-item').hover(function () {
        var _ = $(this);
        _.addClass('on').siblings().removeClass('on');
        news.slideToLoop(_.index());
        return false;
    });


    /*返回顶部*/
    var top = $(window).scrollTop();
    $(".sidebar .top").click(function () {
        $("html,body").animate({ scrollTop: 0 }, 1000);
    });
    $(window).scroll(function () {
        if ($(window).scrollTop() >= 600) {
            $(".sidebar").stop(true, false).addClass('act');
        } else {
            $(".sidebar").stop(true, false).removeClass('act');
        }
    });
    $('.sidebar .message').hover(function(){
        $(this).stop().animate({"left":"-70px"},350)
    },function(){
        $(this).stop().animate({"left":"0"},350)
    })
    $('.sidebar .telephone').hover(function(){
        $(this).stop().animate({"left":"-116px"},350)
    },function(){
        $(this).stop().animate({"left":"0"},350)
    })
    $('.sidebar .wechat').hover(function(){
        $(this).find('.float_shwx').show();
    },function(){
        $(this).find('.float_shwx').hide();
    })


});
