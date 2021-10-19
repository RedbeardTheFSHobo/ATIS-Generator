<?
	function phonetize($in,$phonetize=null,$runway=null) {
		if(!isset($in)) :
			return false;
		endif;
		if(!isset($phonetize)) :
			return $in;
		endif;
		$libNum = array(
			"1" => "one",
			"2" => "two",
			"3" => "three",
			"4" => "four",
			"5" => "five",
			"6" => "six",
			"7" => "seven",
			"8" => "eight",
			"9" => "niner",
			"0" => "zero");
		$libLetter = array(
			"a" => "alpha",
			"b" => "bravo",
			"c" => "charlie",
			"d" => "delta",
			"e" => "echo",
			"f" => "foxtrot",
			"g" => "golf",
			"h" => "hotel",
			"i" => "india",
			"j" => "juliet",
			"k" => "kilo",
			"l" => "lima",
			"m" => "mike",
			"n" => "november",
			"o" => "oscar",
			"p" => "papa",
			"q" => "quebec",
			"r" => "romeo",
			"s" => "sierra",
			"t" => "tango",
			"u" => "uniform",
			"v" => "victor",
			"w" => "whiskey",
			"x" => "x-ray",
			"y" => "yankee",
			"z" => "zulu");
		$libRunway = array(
			"c" => "center",
			"l" => "left",
			"r" => "right");
		$libSymbol = array(
			"-" => "minus");
		$inSmall = strtolower($in);
		$parts = explode(" ",  trim(chunk_split($inSmall, 1, " ")));
		$out = null;
		$i = 1;
		foreach($parts as $part) :
			//preg_match('@^([0-9])([a-z])(-)$@',$part,$result);
			if(preg_match('@^([0-9])$@',$part,$result)) :
				$out .= $libNum[$result[1]];
			endif;
			if(preg_match('@^([a-z])$@',$part,$result)) :
				if(isset($runway)) :
					$out .= $libRunway[$result[1]];
				else :
					$out .= $libLetter[$result[1]];
				endif;
			endif;
			if(preg_match('@^(-)$@',$part,$result)) :
				$out .= $libSymbol[$result[1]];
			endif;
			if($i < count($parts)) :
				$out .= " ";
			endif;
			$i++;
		endforeach;
		return $out;
	}
	
	function station_name($in,$short=null) {
		if(!isset($in->name)) :
			return false;
		endif;
		if(!isset($short)) :
			return $in->name;
		endif;
		return $in->icao;
	}
	
	function metar_time($in,$phonetize=null) {
		if(!isset($in->time->repr)) :
			return false;
		endif;
		preg_match('@^([0-9]{2})([0-9]{2})([0-9]{2})(Z)$@',$in->time->repr,$out);
		return phonetize($out[2].$out[3].$out[4],$phonetize);
	}
	
	function wind_dir($in,$phonetize=null) {
		if(!isset($in->wind_direction)) :
			return false;
		endif;
		$windVarDir = $in->wind_variable_direction;
		preg_match('@^(VRB|[0-9]{3})$@',$in->wind_direction->repr,$out);
		if($out[1] != "VRB" and empty($windVarDir)) :
			if($out[1] < 1) :
				return false;
			else :
				return phonetize($out[1],$phonetize);
			endif;
		else :
			return "variable";
		endif;

	}
	
	function wind_spd($in,$phonetize=null) {
		if(!isset($in->wind_speed)) :
			return false;
		endif;
		$windSpd = $in->wind_speed->value;
		if($windSpd < 1) :
			return false;
		else :
			return phonetize($windSpd,$phonetize);
		endif;
	}
	
	function wind_gust($in,$phonetize=null) {
		if(!isset($in)) :
			return false;
		endif;
		$windGustSpd = $in->wind_gust;
		if(empty($windGustSpd)) :
			return false;
		else :
			return phonetize($windGustSpd->repr,$phonetize);
		endif;
	}
	
	function wind_full($in,$phonetize=null) {
		if(!isset($in)) :
			return false;
		endif;
		$windSpd = wind_spd($in,$phonetize);
		$windDir = wind_dir($in,$phonetize);
		$windGustSpd = wind_gust($in,$phonetize);
		$out = null;
		if(isset($windDir) and isset($windSpd)) :
			$out .= $windDir . " at " . $windSpd;
			if(isset($windGustSpd)) :
				$out .= " gusting " . $windGustSpd;
			endif;
		else :
			$out = "calm";
		endif;
		return $out;
	}
	
	function visibility($in,$phonetize=null) {
		if(!isset($in)) :
			return false;
		endif;
		$out = null;
		if($in->visibility->repr == "CAVOK") :
			$out .= "CAVOK";
		else :
			$distanceUnit = $in->units->visibility;
			$visibilityDistance = $in->visibility->repr;
			if($distanceUnit == "m") :
				$out .= "visibility " . phonetize(round($visibilityDistance/1000),$phonetize) . " kilometers";
			else :
				$out .=  "visibility " . phonetize($visibilityDistance,$phonetize) . " miles";
			endif;

		endif;
		if(!isset($phonetize)) :
			$out .= "... ";
		else :
			$out .= ". ";
		endif;
		return $out;
	}
		
	
	function weather($in,$phonetize=null) {
		if(!isset($in->wx_codes)) :
			return false;
		endif;
		$wxCodes = $in->wx_codes;
		if(empty($wxCodes)) :
			return false;
		else :
			$out = null;
			$i=1;
			foreach($wxCodes as $wxType) :
				$out .= $wxType->value;
				if($i < count($wxCodes)) :
					$out .= ", ";
				else : 
					if(!isset($phonetize)) :
						$out .= "... ";
					else :
						$out .= ". ";
					endif;
				endif;
				$i++;
			endforeach;
			return $out;
		endif;
	}
	
	function altimeter($in,$phonetize=null) {
		if(!isset($in->altimeter->repr)) :
			return false;
		endif;
		preg_match('@^(Q|A)([0-9]{4})$@',$in->altimeter->repr,$out);
		if($out[1] == "Q") :
			return "QNH " . phonetize($out[2],$phonetize);
		elseif($out[1] == "A") :
			return "altimeter " . phonetize($out[2],$phonetize);
		else :
			return false;
		endif;
	}
	
	function temperature($in,$phonetize=null) {
		if(!isset($in->temperature->value)) :
			return false;
		endif;
		return phonetize($in->temperature->value,$phonetize);
	}
	
	function dewpoint($in,$phonetize=null) {
		if(!isset($in->dewpoint->value)) :
			return false;
		endif;
		return phonetize($in->dewpoint->value,$phonetize);
	}
	
	function clouds($in,$phonetize=null) {
		if(!isset($in->clouds)) :
			return false;
		endif;
		$library = array("BKN" => "broken clouds", "CB" => "cumulonimbus", "CLR" => "sky clear", "FEW" => "few clouds", "OVC" => "overcast", "SCT" => "scattered clouds", "SKC" => "Sky Clear", "NSC" => "No Significant Clouds below 12000 feet", "NSC" => "No Significant Cloud", "TCU" => "towering cumulus");
		$clouds = $in->clouds;
		if(empty($clouds)) :
			return false;
		else :
			$out = null;
			$i = 1;
			foreach($clouds as $cloud) :
				$out .= $library[$cloud->type];
				$out .= " at ";
				$out .= $cloud->altitude*100;
				if($i < count($clouds)) :
					$out .= ", ";
				else :
					if(!isset($phonetize)) :
						$out .= "... ";
					else :
						$out .= ". ";
					endif;
				endif;
				$i++;
			endforeach;
			return $out;
		endif;
	}
	
	function runways($in,$phonetize=null) {
		if(!isset($in)) :
			return false;
		endif;
		$runways = explode(" ",$in);
		if(count($runways) > 1) :
			$plural = "runways";
		else :
			$plural = "runway";
		endif;
		$out = null;
		$i = 1;
		foreach($runways as $runway) :
			if(count($runways) > 1) :
				if($i == count($runways) and $i > 1) :
					$out .= " and ";
				elseif($i < count($runways) and $i > 1) :
					$out .= ", ";
				endif;
				$out .= phonetize($runway,$phonetize,true);
			else :
				$out .= phonetize($runway,$phonetize,true);
			endif;
			$i++;
		endforeach;
		return $plural . " " . $out . " in use";				
	}
	
	function remarks($in){
		if($in == null) :
			return false;
		endif;
		return "remarks: {$in}. ";
	}
	
	$arr_rwy = (isset($_POST['arr_rwy'])) ? $_POST['arr_rwy'] : null;
	$dep_rwy = (isset($_POST['dep_rwy'])) ? $_POST['dep_rwy'] : null;
	$station_id = (isset($_POST['station_id'])) ? $_POST['station_id'] : null;
	$ident = (isset($_POST['ident'])) ? $_POST['ident'] : null;
	$rmk = (isset($_POST['rmk'])) ? $_POST['rmk'] : null;
?>

<html>
	<head>
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
		
		<!-- Font Awesome -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

		<style>
			input, textarea,button,select {

				border-radius: 20px!important;
			}
			textarea {
				resize: none!important;
			}
		</style>
	</head>
	<body>

		<div class="page-header">
			<div class="container">
				<h1>Redbeard's TTS ATIS Generator</h1>
			</div>
		</div>
		<div class="jumbotron">
			<div class="container">
			<div class="col-md-5">
		  <form class="form-horizontal" method="post">
			<div class="form-group">
			<label for="station_id" class="control-label col-xs-4">Station ICAO</label> 
				<div class="col-xs-8">
					<input id="station_id" name="station_id" type="text" class="form-control" aria-describedby="station_idHelpBlock" value="<?=$station_id;?>" required="required"> 
					<span id="station_idHelpBlock" class="help-block">Example: KDTW. Required.</span>
				</div>
			</div>
			<div class="form-group">
				<label for="ident" class="control-label col-xs-4">Select</label> 
				<div class="col-xs-8">
					<select id="ident" name="ident" class="select form-control" required="required" aria-describedby="selectHelpBlock">
					<?
					foreach(range("A","Z") as $letter) :
					if($letter == $ident) :
						echo "<option value='" . $letter . "' selected>" . strtoupper(phonetize($letter,true)) . "</option>\n";
					else :
						echo "<option value='" . $letter . "'>" . strtoupper(phonetize($letter,true)) . "</option>\n";
					endif;
					endforeach;
					?>
				</select> 
				<span id="selectHelpBlock" class="help-block">Required.</span>
				</div>
			</div>
			<div class="form-group">
				<label for="arr_rwy" class="control-label col-xs-4">Arriving Runways</label> 
				<div class="col-xs-8">
					<input id="arr_rwy" name="arr_rwy" type="text" class="form-control" aria-describedby="arr_rwyHelpBlock" value="<?=$arr_rwy;?>" required="required"> 
					<span id="arr_rwyHelpBlock" class="help-block">Separate with spaces (example: 22L 22R). Required.</span>
				</div>
			</div>
			<div class="form-group">
				<label for="dep_rwy" class="control-label col-xs-4">Departing Runways</label> 
				<div class="col-xs-8">
					<input id="dep_rwy" name="dep_rwy" type="text" class="form-control" aria-describedby="dep_rwyHelpBlock" value="<?=$dep_rwy;?>" required="required"> 
					<span id="dep_rwyHelpBlock" class="help-block">Separate with spaces (example: 22L 22R). Required.</span>
				</div>
			</div>
			<div class="form-group">
				<label for="rmk" class="control-label col-xs-4">Custom Remarks</label> 
				<div class="col-xs-8">
					<textarea id="rmk" name="rmk" cols="40" rows="5" class="form-control"><?=$rmk;?></textarea>
				</div>
			</div>
			<?

?>


	
		
			<div class="form-group row">
				<div class="col-xs-offset-4 col-xs-8">
					<button name="submit" type="submit" class="btn btn-primary">Generate ATIS</button>
				</div>
			</div>

		</form>

</div>
<div class="col-md-2"></div>
<div class="col-md-5">
<?
	if(!empty($_POST)) :
		$wxSource = "https://avwx.rest/api/metar/" . $_POST['station_id'] ."?options=speech&airport=true&reporting=true&format=json&onfail=cache&token=LwHCwFyqq279ncN3qnK_IMlWLmmXltXzbljN6uIKg-k";
		if(!@file_get_contents($wxSource)) :
			echo "<div class='alert alert-danger'><strong>ERROR:</strong> Airport not found.</div>\n";
		else :
			$wxData = json_decode(file_get_contents("https://avwx.rest/api/metar/" . $_POST['station_id'] ."?options=speech&airport=true&reporting=true&format=json&onfail=cache&token=LwHCwFyqq279ncN3qnK_IMlWLmmXltXzbljN6uIKg-k"));
			$stationData = json_decode(file_get_contents("https://avwx.rest/api/station/" . $_POST['station_id'] ."?format=json&token=LwHCwFyqq279ncN3qnK_IMlWLmmXltXzbljN6uIKg-k"));
			$readableATIS = strtoupper(
				station_name($stationData,true) . " information {$ident}... " .
				metar_time($wxData) . "... " .
				"winds " . wind_full($wxData) . "... " .
				visibility($wxData) . 
				weather($wxData) .
				clouds($wxData) .
				"temperature " . temperature($wxData) . "... " .
				"dewpoint " . dewpoint($wxData) . "... " .
				altimeter($wxData) . "... " .
				"arrival " . runways($arr_rwy) . "... " .
				"departure " . runways($dep_rwy) ."... " .
				remarks($rmk) .
				"advise controller on intial contact that you have information {$ident}."
			);
			$spokenATIS = strtoupper(
				station_name($stationData) . " information " . phonetize($ident,true) . ". " .
				metar_time($wxData,true) . ". " .
				"winnds " . wind_full($wxData,true) . ". " .
				visibility($wxData,true) .
				weather($wxData,true) .
				clouds($wxData,true) .
				"temperature " . temperature($wxData,true) . ". " .
				"dewpoint " . dewpoint($wxData,true) . ". " .
				altimeter($wxData,true) . ". " .
				"arrival " . runways($arr_rwy,true) . ". " .
				"departure " . runways($dep_rwy,true) .". " .
				remarks($rmk) .
				"advise controller on intial contact that you have information " . phonetize($ident,true)
			);
		endif;
	endif;
	$readableATIS = (isset($readableATIS)) ? $readableATIS : "ATIS Not Generated";
	$spokenATIS = (isset($spokenATIS)) ? $spokenATIS : "ATIS Not Generated";

?>
<form method="post" action="./download.php">
  <div class="form-group row">
    <label for="readableATIS" class="col-4 col-form-label">Human Readable ATIS</label> 
    <div class="col-8">
      <textarea id="readableATIS" name="readableATIS" cols="40" rows="5" class="form-control" readonly>
<?=$readableATIS;?>
	  </textarea>
    </div>
  </div> 
  
  <div class="form-group row">
    <label for="spokenATIS" class="col-4 col-form-label">ATIS Output for TTS</label> 
    <div class="col-8">
      <textarea id="spokenATIS" name="spokenATIS" cols="40" rows="5" class="form-control" readonly>
<?=$spokenATIS;?>
	  </textarea>

    </div>
  </div> 
			<div class="form-group row">
				<div class="col-xs-4 col-xs-8">
					<button name="submit" type="submit" class="btn btn-primary">Download ATIS Audio</button>
				</div>
			</div>

</form>
</div>
			</div>
		</div>
	</body>
</html>
