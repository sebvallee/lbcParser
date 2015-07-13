<?php
header( 'content-type: text/html; charset=utf-8' );
error_reporting(-1);
date_default_timezone_set("Europe/Paris");
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>LeBonCoin Parser</title>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">		<!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">-->
		<script src="https://code.jquery.com/jquery-2.1.4.min.js" charset="utf-8"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	</head>
	<body>
		<div class="container">



<?php
function translateDate($date){

	$french = array('Aujourd\'hui', 'Hier');
	$english = array('Today', 'Yesterday');
	$french_month = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'décembre');
	$month = date("n");
	if ($month < 3) {
		$year = date("Y")-1;
	} else {
		$year = date("Y");
	}
	$english_month = array('january '.$year, 'february '.$year, 'march '.$year, 'april '.$year, 'may '.$year, 'june '.$year, 'july '.$year, 'august '.$year, 'september '.$year, 'october '.$year, 'november '.$year, 'december '.$year);
	$date = str_replace($french, $english, $date);
	$date = str_replace($french_month, $english_month, $date);
	//echo "### ".$date." ###<br />";
	$date = strtotime($date);

	return $date;

};

include('simple_html_dom.php');
$url = 'http://www.leboncoin.fr/equipement_auto/offres/pays_de_la_loire/occasions/?f=a&th=1&q=radiosat';
if (isset($_POST['url']) && $_POST['url'] != "") $url = $_POST['url'];
$html = file_get_html($url);

$annonces = array();

echo "<a href='$url' target='_blank' title='$url'>Voir cette recherche sur Le Bon Coin .fr</a>";
echo "<hr />";


foreach($html->find('.list-lbc a') as $annonce){

	$annonce_id = parse_url($annonce->href);
	$annonce_id = $annonce_id['path'];
	$annonce_id = preg_match_all('!\d+!', $annonce_id, $matches);
	$annonce_id = $matches[0][0];

	$annonces[] = array(

		'id' => $annonce_id,
		'title' => trim($annonce->find('.lbc .detail .title', 0)->plaintext),
		'url' => $annonce->href,
		'timestamp' => translateDate(trim(strip_tags($annonce->find('.lbc .date', 0)->innertext))),
		//'rawdate' => trim(strip_tags($annonce->find('.lbc .date', 0)->innertext)),
		'price' => trim($annonce->find('.lbc .detail .price', 0)->plaintext),
		'image' => trim($annonce->find('.lbc .image .image-and-nb img', 0)->src)
		);
}


$last_annonce = $annonces[0]['timestamp'];

$annonces_new = $annonces;
$annonces_old = unserialize(file_get_contents('datas/annonces.txt'));

function udiffCompare($a, $b){

	return $a['id'] - $b['id'];

}

$diff = array_udiff($annonces_new, $annonces_old, 'udiffCompare');

if (!empty($diff) or $_GET["viewold"] == "1"){

	$i = 1;

	if ($_GET["viewold"] == "1") $diff = $annonces;

	echo "<div class='container-fluid'>";
	foreach ($diff as $result) {
		//echo '#'.$i++.' Publiée le '.$result['timestamp'].' '.date("d/m/y à H:i", $result['timestamp']).' ('.$result['rawdate'].') :<br />';
		if ($i % 2 != 0) echo "<div class='row'>";
		echo "<div class='col-md-6 col-lg-6'>";
		echo "<div class='panel panel-primary'>";
		echo '<div class="panel-heading panel-title"><a href="'.$result['url'].'" target="_blank">'.$result['title'].' ('.$result['price'].')</a></div>';
		echo "<div class='panel-body'>";
		echo '<div class="text-center"><img src="'.$result['image'].'" alt="IMAGE"></div>';
		echo "</div>";
		echo '<div class="panel-footer">#'.$i++.' Publiée le '.date("d/m/y à H:i", $result['timestamp']).'</div>';
		echo "</div>";
		if ($i % 2 != 0) echo "</div>";
		echo "</div>";
	}

	echo "</div>";

	file_put_contents('datas/annonces.txt', serialize($annonces));

} else {

	echo 'Pas de nouvelles annonces depuis le '.date('d/m/y à H:i', $last_annonce).' !<br />';
	echo '<a href="?viewold=1">Voir quand meme toutes les annonces présentes?</a>';

}

?>
		</div>
	</body>
</html>
