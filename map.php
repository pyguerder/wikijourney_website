<?php
	$INCLUDE_MAP_PROPERTIES = 1;
	include("./include/haut.php"); 
	//error_reporting(E_ALL);
	/* Obtain current user latitude/longitude */
	if(isset($_POST['name'])) {
		$name = $_POST['name'];
		$osm_array_json = file_get_contents("http://nominatim.openstreetmap.org/search?q=" . urlencode($name) . "&format=json");
		$osm_array = json_decode($osm_array_json, true);
		$user_latitude = $osm_array[0]["lat"];
		$user_longitude = $osm_array[0]["lon"];
		
	}
	else {
		$user_latitude= $_POST[0];
		$user_longitude= $_POST[1];
	}
	
	/* P31 */
	/* Returns a $poi_id_array_clean array with a list of wikidata pages ID within a $range km range from user location */
	$range = 1;
	$poi_id_array_json = file_get_contents("http://wdq.wmflabs.org/api?q=around[625,$user_latitude,$user_longitude,$range]");
	$poi_id_array = json_decode($poi_id_array_json, true);
	$poi_id_array_clean = $poi_id_array["items"];
	$nb_poi = count($poi_id_array_clean);
	
	$poi_array["nb_poi"] = $nb_poi;
	/* stocks latitude, longitude, name and description of every POI located by ↑ in $poi_array */
	for($i = 0; $i < min($nb_poi, 5); $i++) {
		$temp_geoloc_array_json = file_get_contents("http://www.wikidata.org/w/api.php?action=wbgetclaims&format=json&entity=Q" . $poi_id_array_clean["$i"] . "&property=P625");
		$temp_geoloc_array = json_decode($temp_geoloc_array_json, true);
		$temp_poi_type_array_json = file_get_contents("http://www.wikidata.org/w/api.php?action=wbgetclaims&format=json&entity=Q" . $poi_id_array_clean["$i"] . "&property=P31");
		$temp_poi_type_array = json_decode($temp_poi_type_array_json, true);
		$temp_poi_type_id = $temp_poi_type_array["claims"]["P31"][0]["mainsnak"]["datavalue"]["value"]["numeric-id"];
		$temp_description_type_array_json = file_get_contents("http://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=Q" . $temp_poi_type_id . "&props=labels&languages=$language");
		$temp_description_type_array = json_decode($temp_description_type_array_json, true);
		$type_name = $temp_description_type_array["entities"]["Q" . $temp_poi_type_id]["labels"]["$language"]["value"];
		$temp_latitude = $temp_geoloc_array["claims"]["P625"][0]["mainsnak"]["datavalue"]["value"]["latitude"];
		$temp_longitude = $temp_geoloc_array["claims"]["P625"][0]["mainsnak"]["datavalue"]["value"]["longitude"];
		$temp_description_array_json = file_get_contents("http://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=Q" . $poi_id_array_clean["$i"] . "&props=labels&languages=$language");
		$temp_description_array = json_decode($temp_description_array_json, true);
		$name = $temp_description_array["entities"]["Q" . $poi_id_array_clean["$i"]]["labels"]["$language"]["value"];
		$temp_sitelink_array_json = file_get_contents("http://www.wikidata.org/w/api.php?action=wbgetentities&ids=Q" . $poi_id_array_clean["$i"] . "&sitefilter=$language&props=sitelinks/urls&format=json");
		$temp_sitelink_array = json_decode($temp_sitelink_array_json, true);
		$temp_sitelink = $temp_sitelink_array["entities"]["Q" . $poi_id_array_clean["$i"]]["sitelinks"][$language . "wiki"]["url"];
	
		
	
		$poi_array[$i]["latitude"] = 		$temp_latitude;
		$poi_array[$i]["longitude"] = 		$temp_longitude;
		$poi_array[$i]["name"] = 			$name;
		$poi_array[$i]["sitelink"] = 		$temp_sitelink;
		$poi_array[$i]["type_name"] = 		$type_name;
		$poi_array[$i]["type_id"] = 		$temp_poi_type_id;
		$poi_array[$i]["id"] = 				$poi_id_array_clean[$i];
	}
	$poi_array_json_encoded = json_encode((array)$poi_array);
?>
<p style="padding: 5px;" >Your position : <br/>Lat : <?php echo $user_latitude;?><br/>Long : <?php echo $user_longitude;?><br/><br/>
This is what we found around you :</p>
</div>

<div id="map_cart_container">
	<div id="POI_CART"></div>
	<div id="map" class="map"></div>
	<div id="button-wrapper">
		<input type="button" value="Centrer" onclick="center()">
	</div>

	<script>
		var poi_array = new Array();
		var cartList = new Array();
		
		function addToCart(i) {
		//Add a marker to the cart. 
			var j = 0;
			var flag;
			
			flag = 0;
			
			for(j = 0; j < cartList.length; j++)
			{
				if( poi_array[i].id == cartList[j].id )//We test if this POI is already in the list
					flag = 1;
			}
			
			if(flag == 0)
				cartList[cartList.length] = poi_array[i];
			
			reloadCart(cartList);
		}
		
		function reloadCart() {
		
			var i = 0;
			document.getElementById("POI_CART").innerHTML = ''; //Reset
			
			for(i = 0; i <= cartList.length - 1; i++)//Display
			{
				document.getElementById("POI_CART").innerHTML = 
				"<div class=\"eltCart\">" + cartList[i].name + "<br/><i>" + poi_array[i].type_name + "</i><br/><a href="+cartList[i].sitelink+"><img style=\"width: 30px;\" src=\"./images/design/wikipedia.png\" title=\"Wikipedia\" /></a></div>" 
				+ document.getElementById("POI_CART").innerHTML;
			}
		}

		function center(){
			map.setView([user_latitude, user_longitude], 15);
		}
		
		poi_array = <?php echo($poi_array_json_encoded); ?>;
		user_latitude = <?php echo($user_latitude); ?>;
		user_longitude = <?php echo($user_longitude); ?>;
		L.mapbox.accessToken = 'pk.eyJ1IjoicG9sb2Nob24tc3RyZWV0IiwiYSI6Ikh5LVJqS0UifQ.J0NayavxaAYK1SxMnVcxKg';
		var map = L.mapbox.map('map', 'polochon-street.kpogic18');
		
		/* place the first marker with 50% opacity to distinguish it */	
		//var marker = L.marker([user_latitude, user_longitude], {opacity:0.5, color: '#fa0'}).addTo(map);
		var marker = L.marker([user_latitude, user_longitude], {    icon: L.mapbox.marker.icon({
			'marker-size': 'large',
			'marker-symbol': 'pitch',
			'marker-color': '#fa0'
		})}).addTo(map);
		//Cf liste complète des symboles : https://www.mapbox.com/maki/
		
		marker.bindPopup("Vous êtes ici !").openPopup();
		/* place wiki POI */
		for(i = 0; i < Math.min(poi_array.nb_poi, 5); ++i) {
			var popup_content = new Array();
			//if(poi_array[i].sitelink != null)
			popup_content = poi_array[i].name + "<br /> <p><a target=\"_blank\" href=\"http:" + poi_array[i].sitelink + "\">Lien wikipédia</a> <br /> <a href=\"#\" onclick=\"addToCart(" + i + ",'" + cartList +"'); return false;\">[+]</a></p>";
				//
			//else
			//	popup_content = poi_array[i].name + "<br /> <a href=\"http://perdu.com\">[+]</a></p>";
			if (poi_array[i].type_id == "16970"){ 
				var marker = L.marker([poi_array[i].latitude, poi_array[i].longitude], {    icon: L.mapbox.marker.icon({
					'marker-size': 'large',
					'marker-symbol': 'place-of-worship',
				})}).addTo(map); 
			}
			else if(poi_array[i].type_id == "2095"){
				var marker = L.marker([poi_array[i].latitude, poi_array[i].longitude], {    icon: L.mapbox.marker.icon({
					'marker-size': 'large',
					'marker-symbol': 'restaurant',
				})}).addTo(map);
			}
			else if(poi_array[i].type_id == "12518"){
			var marker = L.marker([poi_array[i].latitude, poi_array[i].longitude], {    icon: L.mapbox.marker.icon({
					'marker-size': 'large',
					'marker-symbol': 'monument',
				})}).addTo(map);
			}
			else{
				var marker = L.marker([poi_array[i].latitude, poi_array[i].longitude]).addTo(map); 
			}
			marker.bindPopup(popup_content).openPopup();
		}
		
		map.setView([user_latitude, user_longitude], 15);

			
	</script>

	</div>
<div>
<?php
	include("./include/bas.php");
?>
