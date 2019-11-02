<?php
namespace ProcessWire;

?>

<?php if (!empty($this->map->address) || !empty($this->address)) { ?>
<div class="box map_box">
	<h3><?= __('Location'); ?></h3>

	<div class="location-box-element" id="location_box_element">
		<?= $this->address; ?>
	</div>

	<?php if (empty($this->address) || !empty($this->map->address)) { ?>
	<script>
		function initMap() {
			// Create a map object and specify the DOM element for display.
			var myLatLng = {
				lat: <?= $this->map->lat; ?>,
				lng: <?= $this->map->lng; ?>
			};

			var element = document.getElementById('location_box_element');
			element.style.width = "100%";
			element.style.height = "300px";

			var address = "<?= $this->address; ?>";
			if(address.length <= 0) address = false;

			var map = new google.maps.Map(element, {
				center: myLatLng,
				scrollwheel: false,
				zoom: <?= $this->map->zoom; ?>,
				mapTypeControl: false,
				disableDefaultUI: true
			});
			var marker = new google.maps.Marker({
				position: myLatLng,
				map: map,
				title: '<?= __('Location'); ?>'
			});

			setTimeout(function(){
				if(address !== false){
					var infowindow = new google.maps.InfoWindow({
						content: address,
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