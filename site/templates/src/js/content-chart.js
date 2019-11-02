/* jshint -W024 */
import AjaxCall from './classes/AjaxCall.js';
import { isEmpty, isNumber, startsWith } from "lodash-es";

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

	let charts = document.querySelectorAll('.chart-container');
	Array.prototype.forEach.call(charts, function(chart, index){
		if(chart.getAttribute('data-page') === undefined){
			return;
		}

		let ajaxCall = new AjaxCall();
		ajaxCall.getParams['twack-ajax'] = 1;

		let payload = {
			action: "getComponent",
			component: "Chart",
			page: chart.getAttribute('data-page'),
			directory: "contents_component"
		};
		ajaxCall.postParams = payload;

		ajaxCall.fetch()
		.then(function(response){
			let json = response.json();
			if (response.status >= 200 && response.status < 300) {
				return json;
			}
			return json.then(Promise.reject.bind(Promise));
		})
		.then(function(response) {
			// console.log("Request successful: ", response);
			insertChart(chart, response);
		}, function(response){
			console.error('Fetch Error :-S', response);
		}).catch(function(err) {
			console.error('Fetch Error Catch :-S', err);
		});
	});

	function insertChart(element, args){
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
			let values = [];
			if(typeof args.values === 'object'){
				values = args.values;
			}

			let labels = [];
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

			if(!isEmpty(values)){
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
				values.forEach(function(item){
					let label = '';
					if(item.label !== undefined){
						label = item.label;
					}
					chartConfig.data.xLabels.push(label);

					let data = 0;
					if(item.value !== undefined){
						data = toNumber(item.value);
					}
					dataset.data.push(data);

					let color = '#424242';
					if(typeof item.farbe === 'string'){
						color = item.color;
					}
					if(!startsWith(color, '#')){
						color = '#' + color;
					}

					dataset.borderColor.push(color);
					dataset.backgroundColor.push(hexToRgbA(color, 0.7));
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
								if(isNumber(element.value_minimum) && element.value_minimum > value){
									return true;
								}
								if(isNumber(element.value_maximum) && element.value_maximum < value){
									return true;
								}
								label = element.label;
								return false;
							});
							return label;
						}
					}
				}];
			}
		}else if(typ === 'doughnut'){
			var values = [];
			if(typeof args.values === 'object'){
				values = args.values;
			}

			chartConfig.options.animation = {
				animateScale: true
			};

			chartConfig.options.legend = {
				display: true,
				position: 'bottom'
			};

			if(!isEmpty(values)){
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
				values.forEach(function(item){
					let label = '';
					if(item.label !== undefined){
						label = item.label;
					}
					chartConfig.data.labels.push(label);

					let data = 0;
					if(item.value !== undefined){
						data = toNumber(item.value);
					}
					dataset.data.push(data);

					let color = '#424242';
					if(typeof item.farbe === 'string'){
						color = item.farbe;
					}
					if(!startsWith(color, '#')){
						color = '#' + color;
					}

					dataset.borderColor.push(color);
					dataset.backgroundColor.push(hexToRgbA(color, 0.8));
				});
				chartConfig.data.datasets.push(dataset);
			}
		}

		if(typeof chartConfig !== 'object' || isEmpty(chartConfig)){
			return false;
		}

		// Canvas element. If no canvas picture exists, it is created again.
		let canvasElement = element.querySelectorAll('canvas');
		if(!canvasElement){
			canvasElement = document.createElement("canvas");
			element.append(canvasElement);
		}
		if (canvasElement.classList){
			canvasElement.classList.add('chart');
		}else{
			canvasElement.className += ' chart';
		}

		let myChart = new Chart(canvasElement, chartConfig);
	}

	/**
	* Converts a hex color value into an rgba value. Optionally the transparency factor can be specified.
	* @param  string hex   Hex-Color
	* @param  string|int|float alpha transparency
	* @return string rgba-Value
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