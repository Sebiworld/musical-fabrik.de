import anime from 'animejs'

(async () => {
	// const anime = await import('animejs');

	jQuery(document).ready(function($){
		//final width --> this is the quick view image slider width
		//maxQuickWidth --> this is the max-width of the quick-view panel
		var sliderFinalWidth = 400;
		var maxQuickWidth = 900;
		var selectedPortrait = false;

		//open the quick view panel
		$('.portrait-trigger').on('click', function(event){
			var selectedImage = $(this).find('.portrait-bild'),
			selectedImageUrl = selectedImage.css('background-image');

			$('body').addClass('overlay-layer');
			animateQuickView($(this), sliderFinalWidth, maxQuickWidth, 'open');

			//update the visible slider image in the quick view panel
			//you don't need to implement/use the updateQuickView if retrieving the quick view data with ajax
			updateQuickView(selectedImageUrl);
		});

		//close the quick view panel
		$('body').on('click', function(event){
			if( $(event.target).is('.portrait-close') || $(event.target).is('body.overlay-layer')) {
				closeQuickView(sliderFinalWidth, maxQuickWidth);
			}
		});

		$(document).keyup(function(event){
			//check if user has pressed 'Esc'
			if(event.which=='27'){
				closeQuickView(sliderFinalWidth, maxQuickWidth);
			}
		});

		// quick view slider implementation
		$('.portrait-quick-view').on('click', '.portrait-slider-navigation a', function(){
			updateSlider($(this));
		});

		// center quick-view on window resize
		$(window).on('resize', function(){
			if($('.portrait-quick-view').hasClass('is-visible')){
				window.requestAnimationFrame(resizeQuickView);
			}
		});

		function updateQuickView(url) {
			$('.portrait-quick-view .portrait-slider li').removeClass('selected').find('img[src="'+ url +'"]').parent('li').addClass('selected');
		}

		function resizeQuickView() {
			var quickViewLeft = ($(window).width() - $('.portrait-quick-view').width())/2,
			quickViewTop = ($(window).height() - $('.portrait-quick-view').height())/2;
			$('.portrait-quick-view').css({
				"top": quickViewTop,
				"left": quickViewLeft,
			});
		}

		function closeQuickView(finalWidth, maxQuickWidth) {
			var close = $('.portrait-close');
			var quickView = close.closest('.portrait-quick-view');

			// update the image in the gallery
			if( !$('.portrait-quick-view').hasClass('velocity-animating') && $('.portrait-quick-view').hasClass('add-content')) {
				animateQuickView(undefined, finalWidth, maxQuickWidth, 'close');
			} else {
				closeNoAnimation(undefined, finalWidth, maxQuickWidth);
			}
		}

		function animateQuickView(portraitElement, finalWidth, maxQuickWidth, animationType) {
			// store some image data (width, top position, ...)
			// store window data to calculate quick view panel position
			selectedPortrait = portraitElement;
			var quickViewElement = $('.portrait-quick-view');
			var image = portraitElement.find('.portrait-bild');
			var topSelected = image.offset().top - $(window).scrollTop();
			var leftSelected = image.offset().left;
			var widthSelected = image.width();
			var heightSelected = image.height();
			var windowWidth = $(window).width();
			var windowHeight = $(window).height();
			var finalLeft = (windowWidth - finalWidth)/2;
			var finalHeight = finalWidth * heightSelected/widthSelected;
			var finalTop = (windowHeight - finalHeight)/2;
			var quickViewWidth = ( windowWidth * .8 < maxQuickWidth ) ? windowWidth * .8 : maxQuickWidth;
			var quickViewLeft = (windowWidth - quickViewWidth)/2;

			if( animationType == 'open') {
				//hide the image in the gallery
				portraitElement.addClass('empty-box');

				//place the quick view over the image gallery and give it the dimension of the gallery image
				quickViewElement.css({
					top: topSelected,
					left: leftSelected,
					width: widthSelected,
				});

				//animate the quick view: animate its width and center it in the viewport
				//during this animation, only the slider image is visible
				anime({
					targets: quickViewElement,
					top: finalTop + 'px',
					left: finalLeft + 'px',
					width: finalWidth + 'px',
					duration: 1000,
					complete: function(){
						//animate the quick view: animate its width to the final value
						quickViewElement.addClass('animate-width');

						anime({
							targets: quickViewElement,
							left: quickViewLeft + 'px',
							width: quickViewWidth + 'px',
							easing: 'easeOutExpo',
							duration: 300,
							complete: function(){
								//show quick view content
								quickViewElement.addClass('add-content').addClass('is-visible');
							}
						});
					}
				});
			} else {
				//close the quick view reverting the animation
				quickViewElement.removeClass('add-content');

				anime({
					targets: quickViewElement,
					top: finalTop + 'px',
					left: finalLeft + 'px',
					width: finalWidth + 'px',
					easing: 'easeOutExpo',
					duration: 300,
					complete: function(){
						$('body').removeClass('overlay-layer');
						quickViewElement.removeClass('animate-width');

						anime({
							targets: quickViewElement,
							top: topSelected,
							left: leftSelected,
							width: widthSelected,
							duration: 500,
							easing: 'easeOutExpo',
							complete: function(){
								quickViewElement.removeClass('is-visible');
								portraitElement.removeClass('empty-box');
							}
						});
					}
				});
			}
		}

		function closeNoAnimation(listItem, finalWidth, maxQuickWidth) {
			var quickViewElement = $('.portrait-quick-view');

			if(selectedPortrait){
				var image = selectedPortrait.find('.portrait-bild');
				var topSelected = image.offset().top - $(window).scrollTop();
				var leftSelected = image.offset().left;
				var widthSelected = image.width();

				$('body').removeClass('overlay-layer');
				selectedPortrait.removeClass('empty-box');
				anime.remove(quickViewElement);
				quickViewElement.removeClass('add-content animate-width is-visible').css({
					"top": topSelected,
					"left": leftSelected,
					"width": widthSelected,
				});
				selectedPortrait = false;

			}
		}
	});
})();