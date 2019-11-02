/* jshint -W024 */
import {ready} from './classes/helpers.js';

(async () => {
	const playerElements = document.querySelectorAll('.audioplayer');
	if(playerElements.length > 0){

		await import(/* webpackChunkName: "mediaelement" */ 'mediaelement');

		ready(function(){
			for(let index in playerElements){
				const element = playerElements[index];
				if(typeof element !== 'object' || !(element instanceof Element)){
					continue;
				}

				const player = new MediaElementPlayer(element, {
					stretching: 'responsive',
					alwaysShowControls: false,
					features: ['playpause','volume','progress'],
					audioVolume: 'vertical'
				});

				const audiodateiParent = element.closest('.audiodatei');
				if(typeof audiodateiParent !== 'object' || !(audiodateiParent instanceof Element)){
					continue;
				}

				const playButtons = audiodateiParent.querySelectorAll('[data-audioplayer-action="play"]');
				if(playButtons.length > 0){
					for(let index in playButtons){
						const element = playButtons[index];
						if(typeof element !== 'object' || !(element instanceof Element)){
							continue;
						}

						element.addEventListener("click", function(event){
							event.preventDefault();
							if(player.paused){
								player.play();
							}else{
								player.pause();
							}
						});
					}
				}
			}
		});
	}
})();