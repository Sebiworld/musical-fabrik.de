<?php
namespace ProcessWire;

?>

<?php if (!empty($this->karte->address) || !empty($this->adresse)) { ?>
<div class="box">
	<h3>Veranstaltungsort</h3>

	<div class="kartenelement" id="veranstaltungsort">
		<?= $this->adresse; ?>
	</div>

	<?php if (empty($this->adresse) || !empty($this->karte->address)) { ?>
	<script>
		function initMap() {
			// Create a map object and specify the DOM element for display.
			var myLatLng = {
				lat: <?= $this->karte->lat; ?>,
				lng: <?= $this->karte->lng; ?>
			};

			var element = document.getElementById('veranstaltungsort');
			element.style.width = "100%";
			element.style.height = "300px";

			var adresse = "<?= $this->adresse; ?>";
			if(adresse.length <= 0) adresse = false;

			var map = new google.maps.Map(element, {
				center: myLatLng,
				scrollwheel: false,
				zoom: <?= $this->karte->zoom; ?>,
				mapTypeControl: false,
				disableDefaultUI: true
			});
			var marker = new google.maps.Marker({
				position: myLatLng,
				map: map,
				title: 'Veranstaltungsort'
			});

			setTimeout(function(){
				if(adresse !== false){
					var infowindow = new google.maps.InfoWindow({
						content: adresse,
						maxWidth: 200
					});

					marker.addListener('click', function() {
						infowindow.open(map, marker);
						map.setCenter(marker.getPosition());
					});
					infowindow.open(map, marker);
				}
			}, 100);
		}
	</script>
	<?php } ?>
</div>
<?php } ?>