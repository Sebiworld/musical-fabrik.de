import { Swiper, Navigation, Virtual, Keyboard, Pagination, Lazy, HashNavigation, History, Autoplay, EffectFade, A11y } from 'swiper/js/swiper.esm.js';
Swiper.use([Navigation, Virtual, Keyboard, Pagination, Lazy, HashNavigation, History, Autoplay, EffectFade, A11y]);

export const MfSwiper = {
    initElement(element){
        if (typeof element !== 'object' || !(element instanceof Element)) {
			return false;
		}

		if(typeof element.swiper === 'object' && element.swiper instanceof Swiper){
			return false;
		}

		let swiperParams = {
			pagination: '.swiper-pagination',
			navigation: {
				nextEl: '.swiper-button-next',
				prevEl: '.swiper-button-prev',
			},
			slidesPerView: 'auto',
			spaceBetween: 0,
			watchSlidesVisibility: true,
			grabCursor: true,
			preloadImages: false,
			lazy: {
				loadPrevNext: true,
				loadPrevNextAmount: 2
			}
		};

		if (element.hasAttribute('data-autoplay')) {
			let delay = 5000;
			if (!isNaN(parseInt(element.getAttribute('data-autoplay'))) && parseInt(element.getAttribute('data-autoplay')) > 1) {
				delay = parseInt(element.getAttribute('data-autoplay'));
			}
			swiperParams.autoplay = {
				delay: delay
			};
		}else if (element.hasAttribute('data-hover-autoplay')) {
			let delay = 2000;
			if (!isNaN(parseInt(element.getAttribute('data-hover-autoplay'))) && parseInt(element.getAttribute('data-hover-autoplay')) > 1) {
				delay = parseInt(element.getAttribute('data-hover-autoplay'));
			}
			swiperParams.autoplay = {
				delay: delay
			};
		}


		if (element.hasAttribute('data-loop')) {
			swiperParams.loop = true;
		}

		const swiper = new Swiper(element, swiperParams);

		if (element.hasAttribute('data-hover-autoplay')) {
			if (swiper.autoplay.running) {
				swiper.autoplay.stop();
			}
			element.addEventListener('mouseenter', () => {
				if (!swiper.autoplay.running) {
					swiper.autoplay.start();
				}
			});
			element.addEventListener('mouseleave', () => {
				if (swiper.autoplay.running) {
					swiper.autoplay.stop();
				}
			});
		}
    },
    initElements(wrapper){
        let wrapperTmp = document;
        if (typeof wrapper === 'object' && wrapper instanceof Element) {
            wrapperTmp = wrapper;
        }

        const elements = wrapperTmp.querySelectorAll('.swiper-container');
        if (elements.length <= 0) {
            return false;
        }
    
        for (let index in elements) {
            const element = elements[index];
            if (typeof element !== 'object' || !(element instanceof Element)) {
                continue;
            }

            MfSwiper.initElement(element);
        }
    }
};