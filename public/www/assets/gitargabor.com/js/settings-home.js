/*
Cufon.replace('h1,h2', {
	hover: true
});

Cufon.replace('nav', {
	color: '-linear-gradient(#fff, #a29b95)',
	textShadow: '1px 1px rgba(0,0,0,0.75)'
});
*/

$(window).load(function() {

	$('#slider').nivoSlider({
		animSpeed: 1000, // Slide transition speed
        pauseTime: 6500, // Pause between transitions
		captionOpacity: 0,
                effect:'fold,fade,slideInRight,slideInLeft,boxRandom,boxRain,boxRainReverse,boxRainGrow,boxRainGrowReverse'
	});

	$("#partners .carousel").jCarouselLite({
        btnNext: ".next",
        btnPrev: ".prev",
    	visible: 4,
		auto: 2000,
    	speed: 1000
    });

	$(".reviews .carousel").jCarouselLite({
    	visible: 1,
		auto: 5000,
    	speed: 1000
    });

	$("nav ul li").hover(function() {
        $(this).find("ul").stop(true, true).hide().slideDown(200);
    }, function() {
        $(this).find("ul").stop(true, true).show().slideUp(400);
    });

});

function fourColsInit()
{
    if ($(".col-container1").height() > 300)
    {
        var height1 = $(".col-container1").css("height");
        $(".col-container1").css("overflow", "hidden");
        $(".col-container1").css("height", "300px");
        $('<p class="more col-more1"><a href=""><img src="//cdn.virtuosoft.eu/gitargabor.com/images/mehr.png" alt="Mehr..."></a></p>').insertAfter(".col-container1");
        $('<p class="more col-less1" style="display:none"><a href=""><img src="//cdn.virtuosoft.eu/gitargabor.com/images/weniger.png" alt="Weniger..."></a></p>').insertAfter(".col-container1");
        $(".col-more1").click(function(){
            $(".col-more1").slideUp();
            $(".col-container1").animate({
                height: height1
            }, 1500, function(){
                $(".col-less1").slideDown();
            });
            return false;
        });
        $(".col-less1").click(function(){
            $(".col-less1").slideUp(function(){
                $(".col-more1").slideDown();
            });
            $(".col-container1").animate({
                height: "300px"
            }, 1500);
            return false;
        });
    }

    if ($(".col-container2").height() > 300)
    {
        var height2 = $(".col-container2").css("height");
        $(".col-container2").css("overflow", "hidden");
        $(".col-container2").css("height", "300px");
        $('<p class="more col-more2"><a href=""><img src="//cdn.virtuosoft.eu/gitargabor.com/images/mehr.png" alt="Mehr..."></a></p>').insertAfter(".col-container2");
        $('<p class="more col-less2" style="display:none"><a href=""><img src="//cdn.virtuosoft.eu/gitargabor.com/images/weniger.png" alt="Weniger..."></a></p>').insertAfter(".col-container2");
        $(".col-more2").click(function(){
            $(".col-more2").slideUp();
            $(".col-container2").animate({
                height: height2
            }, 1500, function(){
                $(".col-less2").slideDown();
            });
            return false;
        });
        $(".col-less2").click(function(){
            $(".col-less2").slideUp(function(){
                $(".col-more2").slideDown();
            });
            $(".col-container2").animate({
                height: "300px"
            }, 1500);
            return false;
        });
    }

    if ($(".col-container3").height() > 300)
    {
        var height3 = $(".col-container3").css("height");
        $(".col-container3").css("overflow", "hidden");
        $(".col-container3").css("height", "300px");
        $('<p class="more col-more3"><a href=""><img src="//cdn.virtuosoft.eu/gitargabor.com/images/mehr.png" alt="Mehr..."></a></p>').insertAfter(".col-container3");
        $('<p class="more col-less3" style="display:none"><a href=""><img src="//cdn.virtuosoft.eu/gitargabor.com/images/weniger.png" alt="Weniger..."></a></p>').insertAfter(".col-container3");
        $(".col-more3").click(function(){
            $(".col-more3").slideUp();
            $(".col-container3").animate({
                height: height3
            }, 1500, function(){
                $(".col-less3").slideDown();
            });
            return false;
        });
        $(".col-less3").click(function(){
            $(".col-less3").slideUp(function(){
                $(".col-more3").slideDown();
            });
            $(".col-container3").animate({
                height: "300px"
            }, 1500);
            return false;
        });
    }

    if ($(".col-container4").height() > 300)
    {
        var height4 = $(".col-container4").css("height");
        $(".col-container4").css("overflow", "hidden");
        $(".col-container4").css("height", "300px");
        $('<p class="more col-more4"><a href=""><img src="//cdn.virtuosoft.eu/gitargabor.com/images/mehr.png" alt="Mehr..."></a></p>').insertAfter(".col-container4");
        $('<p class="more col-less4" style="display:none"><a href=""><img src="//cdn.virtuosoft.eu/gitargabor.com/images/weniger.png" alt="Weniger..."></a></p>').insertAfter(".col-container4");
        $(".col-more4").click(function(){
            $(".col-more4").slideUp();
            $(".col-container4").animate({
                height: height4
            }, 1500, function(){
                $(".col-less4").slideDown();
            });
            return false;
        });
        $(".col-less4").click(function(){
            $(".col-less4").slideUp(function(){
                $(".col-more4").slideDown();
            });
            $(".col-container4").animate({
                height: "300px"
            }, 1500);
            return false;
        });
    }
}
