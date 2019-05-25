import AjaxAnfrage from './classes/AjaxAnfrage.js';
import { isEmpty, isNumber, startsWith } from "lodash";

(async () => {
	const chartjs = await import(/* webpackChunkName: "chartjs" */ 'chart.js/dist/chart.js');
	const chartjsPluginDeferred = await import(/* webpackChunkName: "chartjs" */ 'chartjs-plugin-deferred/src/plugin.js');

	Chart.plugins.register({
		deferred: {
           	xOffset: 150,   // defer until 150px of the canvas width are inside the viewport
           	yOffset: '50%', // defer until 50% of the canvas height are inside the viewport
           	delay: 500      // delay of 500 ms after the canvas is considered inside the viewport
           }
       });

	let diagramme = document.querySelectorAll('.diagramm-container');
	Array.prototype.forEach.call(diagramme, function(diagramm, index){
		if(diagramm.getAttribute('data-page') === undefined){
			return;
		}

		let ajaxAnfrage = new AjaxAnfrage();
		ajaxAnfrage.getParams['twack-ajax'] = 1;

		let payload = {
			action: "getComponent",
			component: "Diagramm",
			page: diagramm.getAttribute('data-page'),
			directory: "inhalte"
		};
		ajaxAnfrage.postParams = payload;

		ajaxAnfrage.fetch()
		.then(function(response){
			let json = response.json();
			if (response.status >= 200 && response.status < 300) {
				return json;
			}
			return json.then(Promise.reject.bind(Promise));
		})
		.then(function(response) {
			// console.log("Anfrage erfolgreich: ", response);
			diagrammEinsetzen(diagramm, response);
		}, function(response){
			console.error('Fetch Error :-S', response);
		}).catch(function(err) {
			console.error('Fetch Error Catch :-S', err);
		});
	});

	function diagrammEinsetzen(element, args){
		if(typeof element !== 'object'){
			return false;
		}
		if(typeof args !== 'object'){
			return false;
		}

		var typ = 'bar';
		if(typeof args.typ === 'string'){
			typ = args.typ;
		}

		var chartConfig = {
			type: typ,
			options: {
				responsive: true,
				maintainAspectRatio: false
			}
		};

		if(typeof args.titel === 'string'){
			chartConfig.options.title = {
				display: true,
				text: args.titel
			}
		}

		if(typ === 'bar'){
			var werte = [];
			if(typeof args.werte === 'object'){
				werte = args.werte;
			}

			var labels = [];
			if(typeof args.labels === 'object'){
				labels = args.labels;
			}

			chartConfig.options.scales = {
				yAxes: [{
					ticks: {
						beginAtZero: true
					}
				}]
			};

			chartConfig.options.legend = {
				display: false
			};
			chartConfig.options.tooltips = {
				enabled: false
			};

			if(!isEmpty(werte)){
				chartConfig.data = {
					xLabels: [],
					datasets: []
				};

				let dataset = {
					label: '',
					data: [],
					backgroundColor: [],
					borderColor: [],
					borderWidth: 2
				};
				werte.forEach(function(wert){
					let label = '';
					if(wert.label !== undefined){
						label = wert.label;
					}
					chartConfig.data.xLabels.push(label);

					let data = 0;
					if(wert.wert !== undefined){
						data = toNumber(wert.wert);
					}
					dataset.data.push(data);

					let farbe = '#424242';
					if(typeof wert.farbe === 'string'){
						farbe = wert.farbe;
					}
					if(!startsWith(farbe, '#')){
						farbe = '#' + farbe;
					}

					dataset.borderColor.push(farbe);
					dataset.backgroundColor.push(hexToRgbA(farbe, 0.7));
				});
				chartConfig.data.datasets.push(dataset);
			}

			if(!isEmpty(labels)){
				chartConfig.options.scales.yAxes = [{
					ticks: {
						stepSize: 1,
						beginAtZero:true,
						// max: 8,
						callback: function(value, index, values) {
							var label = value;
							labels.every(function(element, labelIndex, _arr) {
								if(isNumber(element.wert_minimum) && element.wert_minimum > value){
									return true;
								}
								if(isNumber(element.wert_maximum) && element.wert_maximum < value){
									return true;
								}
								label = element.label
								return false;
							});
							return label;
						}
					}
				}];
			}
		}else if(typ === 'doughnut'){
			var werte = [];
			if(typeof args.werte === 'object'){
				werte = args.werte;
			}

			chartConfig.options.animation = {
				animateScale: true
			};

			chartConfig.options.legend = {
				display: true,
				position: 'bottom'
			};

			if(!isEmpty(werte)){
				chartConfig.data = {
					labels: [],
					datasets: []
				};

				let dataset = {
					label: '',
					data: [],
					backgroundColor: [],
					borderColor: [],
					borderWidth: 0
				};
				werte.forEach(function(wert){
					let label = '';
					if(wert.label !== undefined){
						label = wert.label;
					}
					chartConfig.data.labels.push(label);

					let data = 0;
					if(wert.wert !== undefined){
						data = toNumber(wert.wert);
					}
					dataset.data.push(data);

					let farbe = '#424242';
					if(typeof wert.farbe === 'string'){
						farbe = wert.farbe;
					}
					if(!startsWith(farbe, '#')){
						farbe = '#' + farbe;
					}

					dataset.borderColor.push(farbe);
					dataset.backgroundColor.push(hexToRgbA(farbe, 0.8));
				});
				chartConfig.data.datasets.push(dataset);
			}
		}

		if(typeof chartConfig !== 'object' || isEmpty(chartConfig)){
			return false;
		}

		// Canvas-Element bestimmen. Wenn kein Canvas-Child existiert, wird es neu angelegt.
		let canvasElement = element.querySelectorAll('canvas');
		if(!canvasElement){
			canvasElement = document.createElement("canvas");
			element.append(canvasElement);
		}
		if (canvasElement.classList){
			canvasElement.classList.add('diagramm');
		}else{
			canvasElement.className += ' diagramm';
		}

		let myChart = new Chart(canvasElement, chartConfig);
	}

	/**
	* Wandelt einen Hex-Farbwert in einen rgba-Wert um. Optional kann der Transparenz-Faktor angegeben werden.
	* @param  string hex   Hex-Farbwert
	* @param  string|int|float alpha Transparenz
	* @return string rgba-Wert
	*/
	function hexToRgbA(hex, alpha){
		if(alpha === undefined){
			alpha = 1;
		}
		var c;
		if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
			c= hex.substring(1).split('');
			if(c.length== 3){
				c= [c[0], c[0], c[1], c[1], c[2], c[2]];
			}
			c= '0x'+c.join('');
			return 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+',' + alpha + ')';
		}
		throw new Error('Bad Hex');
	}
})();