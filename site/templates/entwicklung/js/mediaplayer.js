import {ready} from './classes/hilfsfunktionen.js';

(async () => {
	const playerElemente = document.querySelectorAll('.audioplayer');
	if(playerElemente.length > 0){

		await import(/* webpackChunkName: "mediaelement" */ 'mediaelement');

		ready(function(){
			for(let index in playerElemente){
				const element = playerElemente[index];
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

				const playButtons = audiodateiParent.querySelectorAll('[data-audioplayer-aktion="play"]');
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