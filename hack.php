<?php

function getPlaceSearchResults($keyword) {
	$prefixSearchURLBase = "http://lookup.dbpedia.org/api/search.asmx/PrefixSearch?QueryClass=place&MaxHits=1&QueryString=";
	
	$ch = curl_init($prefixSearchURLBase . urlencode($keyword));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	// Shoot the request
	$result = curl_exec($ch);	// Gotcha!
	
	$sxml = simplexml_load_string($result);
	return $sxml->Result;
}

function getPersonSearchResults($keyword) {
	$prefixSearchURLBase = "http://lookup.dbpedia.org/api/search.asmx/PrefixSearch?QueryClass=person&MaxHits=1&QueryString=";
	
	$ch = curl_init($prefixSearchURLBase . urlencode($keyword));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	// Shoot the request
	$result = curl_exec($ch);	// Gotcha!
	
	$sxml = simplexml_load_string($result);
	return $sxml->Result;
}

function query($query) {
	$baseSPARQLQueryURL = "http://dbpedia.org/sparql?default-graph-uri=http%3A%2F%2Fdbpedia.org&query=" . urlencode($query) . "&format=json&timeout=1000&debug=off";

	$ch = curl_init($baseSPARQLQueryURL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	// Shoot the request
	$result = curl_exec($ch);	// Gotcha!
	
	$json = json_decode($result, TRUE);
	return $json['results']['bindings'];
}


// Strip and trim the query so that we know what we are doing.
$searchQuery = $str = preg_replace('/\s\s+/', ' ', trim($_POST['wikisearch']));	
$searchTokens = explode(" ", $searchQuery);

// Get the command assuming the user has followed the syntax
$command = $searchTokens[0];

switch($command) {
	case "born":
		// TBD
		break;
	case "dead":
		// TBD
		break;
	case "birthplace":
		$place = $searchTokens[1];
		$placeXML = getPlaceSearchResults($place);
		$place = $placeXML->URI;
		$query = "select distinct ?name ?picture ?link ?intro where {
?s ?p dbpedia-owl:Person .
?s dbpedia-owl:birthPlace <" . $place . "> .
?s foaf:name ?name .
?s dbpedia-owl:thumbnail ?picture .
?s foaf:page ?link .
?s dbpedia-owl:abstract ?intro .
} order by ?name
LIMIT 50";
		$persons = query($query);
		$person_hash = array();
		echo "<table>";
		foreach($persons as $person) {
			if(in_array($person['name']['value'], $person_hash)) continue;
			
			if($person['intro']['xml:lang'] == "en") {
				echo "<tr><td><img src='" . $person['picture']['value']. "' align='bottom' width='200' /></td><td><a href='" . $person['link']['value'] . "'>" . $person['name']['value'] . "</a><br />" . $person['intro']['value'];
			$person_hash[] = $person['name']['value'];
			}
		}
		echo "</table>";
		break;
	case "deathplace":
		$place = $searchTokens[1];
		$placeXML = getPlaceSearchResults($place);
		$place = $placeXML->URI;
		$query = "select distinct ?name ?picture ?link ?intro where {
?s ?p dbpedia-owl:Person .
?s dbpedia-owl:deathPlace <" . $place . "> .
?s foaf:name ?name .
?s dbpedia-owl:thumbnail ?picture .
?s foaf:page ?link .
?s dbpedia-owl:abstract ?intro .
} order by ?name
LIMIT 50";
		$persons = query($query);
		$person_hash = array();
		echo "<table>";
		foreach($persons as $person) {
			if(in_array($person['name']['value'], $person_hash)) continue;
			
			if($person['intro']['xml:lang'] == "en") {
				echo "<tr><td><img src='" . $person['picture']['value'] . "' align='bottom' width='200' /></td><td><a href='" . $person['link']['value'] . "'>" . $person['name']['value'] . "</a><br />" . $person['intro']['value'];
				$person_hash[] = $person['name']['value'];
			}
		}
		echo "</table>";
		break;
	case "list":
		$entity = $searchTokens[1];
		$query = "select ?s ?intro where {?s ?p dbpedia-owl:" . $entity . " . 
			?s dbpedia-owl:abstract ?intro .
		} LIMIT 50";
		$entityList = query($query);
		print_r($entityList);
		break;
	case "starring":
		$stars = $searchTokens;
		unset($stars[0]);
		
		$star_uri = array();
		
		foreach($stars as $star) {
			$person = getPersonSearchResults($star);
			$star_uri[] = $person->URI;
		}
		
		$query = "select distinct ?movie ?link ?intro ?picture where {";
		foreach($star_uri as $uri) {
			$query .= "
?m dbpedia-owl:starring <" . $uri . "> .";
		}
		$query .= "?m foaf:name ?movie .
		?m foaf:page ?link .
		?m dbpedia-owl:abstract ?intro .
		?m dbpedia-owl:thumbnail ?picture .
		}
		order by ?movie";
		
		$movies = query($query);

		$movie_hash = array();
		echo "<table>";
		foreach($movies as $movie) {
			if(in_array($movie['movie']['value'], $movie_hash)) continue;
			
			if($movie['intro']['xml:lang'] == "en") {
				echo "<tr><td><!--<img src='" . $movie['picture']['value'] . "' align='bottom' width='200' />--> &nbsp; </td><td><a href='" . $movie['link']['value'] . "'>" . $movie['movie']['value'] . "</a><br />" . $movie['intro']['value'];
				$person_hash[] = $movie['movie']['value'];
			}
		}
		echo "</table>";
		break;
}
