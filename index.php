<?php
//echo 'MAINTENANCE';
//return;
//$merged_users = isset($_GET['merge']) ? $_GET['merge'] : true;
$merged_users = false;

$get_wikis = isset($_GET['wikis']) && is_array($_GET['wikis']) ? $_GET['wikis'] :
	array('starcraft', 'starcraft2', 'dota2', 'hearthstone', 'heroes', 'smash', 'counterstrike', 'overwatch', 'commons', 'warcraft', 'fighters', 'rocketleague');
$clean_wikis_list = array();
foreach ($get_wikis as $wiki) {
	if (preg_match('/^(starcraft|starcraft2|dota2|hearthstone|heroes|smash|counterstrike|overwatch|commons|warcraft|fighters|rocketleague)$/', $wiki)) {
		$clean_wikis_list[$wiki] = $wiki;
	}
}
?>
<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Liquipedia Edit count</title>
	<link rel="stylesheet" type="text/css" href="style.css">
	<link rel="stylesheet" type="text/css" href="flag-icon.min.css">
	<script type="text/javascript">
		function heatmap() {
			var elements = document.querySelectorAll('tr.total-row,tr.user-row');
			Array.prototype.forEach.call(elements, function(el, i){
				var wikiCells = el.querySelectorAll('td.wiki-column');
				var editCountTotal = 0, editCountValues = [];
				Array.prototype.forEach.call(wikiCells, function(wikiCell) {
					var value = wikiCell.textContent.replace(/[^0-9,]/g, '');
					value = value === '' ? 0 : parseInt(value);
					editCountValues.push(value);
					editCountTotal += value;
				});
				for (var k = 0; k < wikiCells.length; k++) {
					var ratio = editCountTotal != 0 ? (editCountValues[k] / editCountTotal) : 0;
					var color = Math.floor((7*(1 - ratio)/8 + 1/16) * 100);
					wikiCells[k].style.backgroundColor = 'hsl(120,100%,' + color + '%)';
					if (color < 40) {
						var anchors = wikiCells[k].querySelectorAll('a');
						if (anchors.length) {
							anchors[0].style.color = '#ffffff';
						} else {
							wikiCells[k].style.color = '#ffffff';
						}
						var abbrs = wikiCells[k].querySelectorAll('abbr');
						if (abbrs.length) {
							abbrs[0].style.color = '#ffffff';
						}
					}
				}
			});
		}
		function ready() {
			var buttons = document.querySelectorAll('#heatmap-button')
			buttons[0].addEventListener('click', heatmap);
		}
		if (document.readyState != 'loading'){
			ready();
		} else {
			document.addEventListener('DOMContentLoaded', ready);
		}
	</script>
</head>
<body>
	<form action="index.php" type="GET">
		<!--<input type="checkbox" <?php echo $merged_users ? 'checked="" ' : ''; ?>name="merge" value="1" id="merge"/>
		<label for="merge">Merge edit counts of merged users</label>-->
		<div id="wikis">
			<span><b>Select wikis: </b></span>
			<input type="checkbox" <?php echo isset($clean_wikis_list['starcraft']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="starcraft" id="starcraft"/>
			<label class="wiki-button starcraft" for="starcraft" title="StarCraft: Brood War"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['starcraft2']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="starcraft2" id="starcraft2"/>
			<label class="wiki-button starcraft2" for="starcraft2" title="StarCraft II"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['dota2']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="dota2" id="dota2"/>
			<label class="wiki-button dota2" for="dota2" title="Dota 2"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['hearthstone']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="hearthstone" id="hearthstone"/>
			<label class="wiki-button hearthstone" for="hearthstone" title="Hearthstone"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['heroes']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="heroes" id="heroes"/>
			<label class="wiki-button heroes" for="heroes" title="Heroes"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['smash']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="smash" id="smash"/>
			<label class="wiki-button smash" for="smash" title="Smash Bros"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['counterstrike']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="counterstrike" id="counterstrike"/>
			<label class="wiki-button counterstrike" for="counterstrike" title="Counter-Strike"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['overwatch']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="overwatch" id="overwatch"/>
			<label class="wiki-button overwatch" for="overwatch" title="Overwatch"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['commons']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="commons" id="commons"/>
			<label class="wiki-button commons" for="commons" title="Commons"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['warcraft']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="warcraft" id="warcraft"/>
			<label class="wiki-button warcraft" for="warcraft" title="Warcraft"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['fighters']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="fighters" id="fighters"/>
			<label class="wiki-button fighters" for="fighters" title="Fighting Games"></label>
			<input type="checkbox" <?php echo isset($clean_wikis_list['rocketleague']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="rocketleague" id="rocketleague"/>
			<label class="wiki-button rocketleague" for="rocketleague" title="Rocket League"></label>
		</div>
		<input type="submit" />
	</form>
	<input type="button" value="Heatmap" id="heatmap-button" />
	<hr>

<?php
/*debut du cache*/
$cache = 'cache/index' . ($merged_users ? '_merge' : '') . '_' . implode('_', $clean_wikis_list) . '.html';
$expire = time() - 3600 ; // valable une heure
 
if(file_exists($cache) && filemtime($cache) > $expire)
{
	echo '<b>Cached: ' . date('r (T)', filemtime($cache)) . '</b><br/>';
	echo 'Current server time: ' . date('r (T)') . ' - The cache expires after 60 minutes.<br/><br/>';
	readfile($cache);
}
else
{
	echo '<b>Generated now: ' . date('r (T)') . '</b><br/><br/>';

	ob_start(); // ouverture du tampon

	require('table.php');

	$page = ob_get_contents(); // copie du contenu du tampon dans une chaîne
	ob_end_clean(); // effacement du contenu du tampon et arrêt de son fonctionnement
	
	file_put_contents($cache, $page) ; // on écrit la chaîne précédemment récupérée ($page) dans un fichier ($cache) 
	echo $page ; // on affiche notre page :D 
}
?>
</body>	