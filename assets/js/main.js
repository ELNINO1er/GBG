/*-----------------------------------------------------------------

Template Name: FarmHub - Agriculture Farming HTML Template
Author:  theme_ocean
Author URI: https://www.templatemonster.com/authors/theme_ocean/
Version: 1.0.0
Description: FarmHub - Agriculture Farming HTML Template

-------------------------------------------------------------------
CSS TABLE OF CONTENTS
-------------------------------------------------------------------

01. __Header
02. __animated text with swiper slider
03. __magnificPopup
04. __counter up
05. __wow animation
06. __nice select
07. __swiper slider
08. __search popup
09. __preloader 


------------------------------------------------------------------*/

(function ($) {
	("use strict");

	$(document).ready(function () {
		//>> Mobile Menu Js Start <<//
		$("#mobile-menu").meanmenu({
			meanMenuContainer: ".mobile-menu",
			meanScreenWidth: "1199",
			meanExpand: ['<i class="far fa-plus"></i>'],
		});

		//>> Sidebar Toggle Js Start <<//
		$(".offcanvas__close,.offcanvas__overlay").on("click", function () {
			$(".offcanvas__info").removeClass("info-open");
			$(".offcanvas__overlay").removeClass("overlay-open");
		});
		$(".sidebar__toggle").on("click", function () {
			$(".offcanvas__info").addClass("info-open");
			$(".offcanvas__overlay").addClass("overlay-open");
		});

		//>> Body Overlay Js Start <<//
		$(".body-overlay").on("click", function () {
			$(".offcanvas__area").removeClass("offcanvas-opened");
			$(".df-search-area").removeClass("opened");
			$(".body-overlay").removeClass("opened");
		});

		// Click to Scroll Top
		var ScrollTop = $(".scrollToTop");
		$(".scrollToTop").on("click", function () {
			$("html, body").animate(
				{
					scrollTop: 0,
				},
				600
			);
			return false;
		});

		//>> Sticky Header Js Start <<//

		$(window).scroll(function () {
			if ($(this).scrollTop() > 150) {
				$("#header-sticky").addClass("sticky");
			} else {
				$("#header-sticky").removeClass("sticky");
			}

			// Check Scroll
			if ($(this).scrollTop() < 600) {
				ScrollTop.removeClass("active");
			} else {
				ScrollTop.addClass("active");
			}
		});

		//--Global SideBar--
		$(".toggleMain-controls .bar-toggles, .cmn-overlay").on(
			"click",
			function () {
				$(
					".toggleMain-controls .sidebar-wrapper, .cmn-overlay"
				).toggleClass("active");
			}
		);
		//--Global SideBar--

		//>> Video Popup Start <<//
		$(".img-popup").magnificPopup({
			type: "image",
			gallery: {
				enabled: true,
			},
		});

		$(".video-popup").magnificPopup({
			type: "iframe",
			callbacks: {},
		});

		//--== Progress Bar ==--//
		$(document).ready(function () {
			progress_bar();
		});

		function progress_bar() {
			var speed = 30;
			var items = $(".progress_bar").find(".progress_bar_item");

			items.each(function () {
				var item = $(this).find(".progress");
				var itemValue = item.data("progress");
				var i = 0;
				var value = $(this);

				var count = setInterval(function () {
					if (i <= itemValue) {
						var iStr = i.toString();
						item.css({
							width: iStr + "%",
						});
						value.find(".item_value").html(iStr + "%");
					} else {
						clearInterval(count);
					}
					i++;
				}, speed);
			});
		}
		//--== Progress Bar ==--//

		//>> Counterup Start <<//
		$(".count").counterUp({
			delay: 15,
			time: 4000,
		});

		//>> Wow Animation Start <<//
		new WOW().init();

		//>> Mixtup Filter Start <<//
		if ($(".all-catagorys").length > 0) {
			var mixer = mixitup(".all-catagorys");
		}

		//>> Nice Select Start <<//
		$("select").niceSelect();

		//>> Servicves Slider Start <<//
		const servicewrapslide = new Swiper(".service-wrapslide", {
			spaceBetween: 0,
			speed: 1300,
			loop: true,
			centeredSlides: true,
			roundLengths: true,
			autoplay: {
				delay: 2000,
				disableOnInteraction: false,
			},
			pagination: {
				el: ".dot-cmn",
				clickable: true,
			},
			breakpoints: {
				1299: {
					slidesPerView: 3,
				},
				1199: {
					slidesPerView: 3,
				},
				767: {
					slidesPerView: 2,
				},
				575: {
					slidesPerView: 2,
				},
				0: {
					slidesPerView: 1,
				},
			},
		});
		//>> Servicves Slider Start <<//

		//>> Team Slider Start <<//
		const testimonialslidewrap01 = new Swiper(".testimonial-slidewrap01", {
			spaceBetween: 2,
			speed: 1300,
			loop: true,
			autoplay: {
				delay: 2000,
				disableOnInteraction: false,
			},
			pagination: {
				el: ".dot-cmn",
				clickable: true,
			},
			navigation: {
				nextEl: ".cmn-prev2",
				prevEl: ".cmn-next2",
			},
			breakpoints: {
				1299: {
					slidesPerView: 1,
				},
				1199: {
					slidesPerView: 1,
				},
				767: {
					slidesPerView: 1,
				},
				575: {
					slidesPerView: 1,
				},
				0: {
					slidesPerView: 1,
				},
			},
		});

		const teamSlider2 = new Swiper(".team-slider-2", {
			spaceBetween: 30,
			speed: 1500,
			loop: true,
			autoplay: {
				delay: 2000,
				disableOnInteraction: false,
			},
			navigation: {
				nextEl: ".array-prev",
				prevEl: ".array-next",
			},
			breakpoints: {
				1199: {
					slidesPerView: 3,
				},
				767: {
					slidesPerView: 2,
				},
				575: {
					slidesPerView: 1,
				},
				0: {
					slidesPerView: 1,
				},
			},
		});

		//>> Testimonial Slider Start <<//
		const recentprojectwrap = new Swiper(".recent-project-wrap", {
			spaceBetween: 24,
			speed: 1500,
			loop: true,
			centeredSlides: true,
			navigation: {
				nextEl: ".cmn-prev1",
				prevEl: ".cmn-next1",
			},
			breakpoints: {
				1199: {
					slidesPerView: 4.5,
				},
				767: {
					slidesPerView: 3.5,
					spaceBetween: 14,
				},
				575: {
					slidesPerView: 3,
					spaceBetween: 14,
				},
				0: {
					slidesPerView: 2,
					spaceBetween: 14,
				},
			},
		});

		//>> Brand Slider this Start <<//
		const brandSlider = new Swiper(".brand-slider", {
			spaceBetween: 30,
			speed: 1300,
			loop: true,
			centeredSlides: true,
			autoplay: {
				delay: 2000,
				disableOnInteraction: false,
			},

			breakpoints: {
				1199: {
					slidesPerView: 5,
				},
				991: {
					slidesPerView: 4,
				},
				767: {
					slidesPerView: 3,
				},
				575: {
					slidesPerView: 2,
				},
				0: {
					slidesPerView: 2,
				},
			},
		});

		//Shop Details
			var swiper = new Swiper(".mySwiper", {
				loop: true,
				spaceBetween: 10,
				slidesPerView: 3,
				freeMode: true,
				watchSlidesProgress: true,
				breakpoints: {
				  480: {
					slidesPerView: 4,
					spaceBetween: 10,
				  },
				  991: {
					slidesPerView: 4,
					spaceBetween: 20,
				  },
				  1199: {
					slidesPerView: 5,
					spaceBetween: 24,
				  }
				},
			  });
			  var swiper2 = new Swiper(".mySwiper2", {
				loop: true,
				spaceBetween: 10,
				navigation: {
				  nextEl: ".swiper-button-next",
				  prevEl: ".swiper-button-prev",
				},
				thumbs: {
				  swiper: swiper,
				},
			  });
			  //Shop Details

		//>> Search Popup Start <<//
		const $searchWrap = $(".search-wrap");
		const $navSearch = $(".nav-search");
		const $searchClose = $("#search-close");

		$(".search-trigger").on("click", function (e) {
			e.preventDefault();
			$searchWrap.animate({ opacity: "toggle" }, 500);
			$navSearch.add($searchClose).addClass("open");
		});

		$(".search-close").on("click", function (e) {
			e.preventDefault();
			$searchWrap.animate({ opacity: "toggle" }, 500);
			$navSearch.add($searchClose).removeClass("open");
		});

		function closeSearch() {
			$searchWrap.fadeOut(200);
			$navSearch.add($searchClose).removeClass("open");
		}

		$(document.body).on("click", function (e) {
			closeSearch();
		});

		$(".search-trigger, .main-search-input").on("click", function (e) {
			e.stopPropagation();
		});
	}); // End Document Ready Function

	// custom Accordion
	$(".accordion-single .header-area").on("click", function () {
		if ($(this).closest(".accordion-single").hasClass("active")) {
			$(this).closest(".accordion-single").removeClass("active");
			$(this).next(".content-area").slideUp();
		} else {
			$(".accordion-single").removeClass("active");
			$(this).closest(".accordion-single").addClass("active");
			$(".content-area").not($(this).next(".content-area")).slideUp();
			$(this).next(".content-area").slideToggle();
		}
	});

      // Custom Tabs
      $(".tablinks .nav-links").each(function () {
        var targetTab = $(this).closest(".singletab");
        targetTab.find(".tablinks .nav-links").each(function() {
          var navBtn = targetTab.find(".tablinks .nav-links");
          navBtn.click(function(){
            navBtn.removeClass('active');
            $(this).addClass('active');
            var indexNum = $(this).closest("li").index();
            var tabcontent = targetTab.find(".tabcontents .tabitem");
            $(tabcontent).removeClass('active');
            $(tabcontent).eq(indexNum).addClass('active');
          });
        });
      });

	//Preloader
	function loader() {
		$(window).on("load", function () {
			// Animate loader off screen
			$(".preloader").addClass("loaded");
			$(".preloader").delay(1000).fadeOut();
		});
	}

	//Pricing Product Renge Slider
	const rangeInput = document.querySelectorAll(".range-input input"),
		priceInput = document.querySelectorAll(".price-input input"),
		range = document.querySelector(".slider .progress");
	let priceGap = 1000;

	priceInput.forEach((input) => {
		input.addEventListener("input", (e) => {
			let minPrice = parseInt(priceInput[0].value),
				maxPrice = parseInt(priceInput[1].value);

			if (maxPrice - minPrice >= priceGap && maxPrice <= rangeInput[1].max) {
				if (e.target.className === "input-min") {
					rangeInput[0].value = minPrice;
					range.style.left = (minPrice / rangeInput[0].max) * 100 + "%";
				} else {
					rangeInput[1].value = maxPrice;
					range.style.right =
						100 - (maxPrice / rangeInput[1].max) * 100 + "%";
				}
			}
		});
	});

	rangeInput.forEach((input) => {
		input.addEventListener("input", (e) => {
			let minVal = parseInt(rangeInput[0].value),
				maxVal = parseInt(rangeInput[1].value);

			if (maxVal - minVal < priceGap) {
				if (e.target.className === "range-min") {
					rangeInput[0].value = maxVal - priceGap;
				} else {
					rangeInput[1].value = minVal + priceGap;
				}
			} else {
				priceInput[0].value = minVal;
				priceInput[1].value = maxVal;
				range.style.left = (minVal / rangeInput[0].max) * 100 + "%";
				range.style.right = 100 - (maxVal / rangeInput[1].max) * 100 + "%";
			}
		});
	});

	loader();
})(jQuery); // End jQuery
