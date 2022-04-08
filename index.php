<?php
function curl_check($input){
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,"https://tgftp.nws.noaa.gov/data/observations/metar/stations/".strtoupper($input).".TXT");
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_exec($ch);
	$http_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if($http_code!=200){
		return false;
	}
	return true;
}

function curl_get($input){
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,"https://tgftp.nws.noaa.gov/data/observations/metar/stations/".strtoupper($input).".TXT");
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	return curl_exec($ch);
	curl_close($ch);
}


//$station = json_decode(get_info("kdtw", "station"), FALSE);
//STRINGS FOR DEBUGGING
//$metar = "EGLL 011220Z AUTO 02010KT 350V060 9999 FEW044 06/M04 Q1018";
//$metar="LIBQ 011455Z 27013G31KT 210V350 0000 -RA FG VV/// 01/01 Q0999 RMK MON INVIS VAL INVIS VIS MIN 0000";
//$metar="PAWD 021753Z AUTO 00000KT 1/4SM FG BKN002 OVC120 00/00 A2935 RMK AO2 SLP938 60003 T00000000 10011 20000 53000 TSNO $";
//$metar="WARR 031300Z 24005KT 5000 VCTS SCT020CB 25/M24 Q1010 RERA TEMPO TL1330 5000 -TSRA";
// function metar_global(){
	// global $metar;
	// return $metar;
// }
// var_dump($metar);
//\\
//PRODUCTION\\
//\\




function phonetize($input,$phonetize,$runway=false){
	$libNumber=array(
		"1"=>"one",
		"2"=>"two",
		"3"=>"three",
		"4"=>"four",
		"5"=>"five",
		"6"=>"six",
		"7"=>"seven",
		"8"=>"eight",
		"9"=>"niner",
		"0"=>"zero");
	$libLetter=array(
		"a"=>"alpha",
		"b"=>"bravo",
		"c"=>"charlie",
		"d"=>"delta",
		"e"=>"echo",
		"f"=>"foxtrot",
		"g"=>"golf",
		"h"=>"hotel",
		"i"=>"india",
		"j"=>"juliet",
		"k"=>"kilo",
		"l"=>"lima",
		"m"=>"mike",
		"n"=>"november",
		"o"=>"oscar",
		"p"=>"papa",
		"q"=>"quebec",
		"r"=>"romeo",
		"s"=>"sierra",
		"t"=>"tango",
		"u"=>"uniform",
		"v"=>"victor",
		"w"=>"whiskey",
		"x"=>"x-ray",
		"y"=>"yankee",
		"z"=>"zulu",
		"-"=>null,
		" "=>null);
	$libRunway=array(
		"c"=>"center",
		"l"=>"left",
		"r"=>"right");
	if(!$phonetize){
		return $input;
	}
	$output=null;
	$parts=explode(" ", trim(chunk_split(strtolower($input), 1, " ")));
	$i=1;
	foreach($parts as $part){
		if($part=="") {
			continue;
		}
		if(!$runway){
			if(is_numeric($part)){
				$output.=$libNumber[$part];
			}else{
				$output.=$libLetter[$part];
			}
		}else{
			if(is_numeric($part)){
				$output.=$libNumber[$part];
			}else{
				$output.=$libRunway[$part];
			}
		}
		if($i<count($parts)){
			$output.=" ";
		}
		$i++;		
	}
	return $output;
}

//echo metar_global()."\n\n";

function get_airport_name($input,$phonetize){
	$mysqli=new mysqli('localhost','redbbqhz_Vbyn','WentNeed$l5FQ','redbbqhz_atis_generator');
	$query="SELECT name FROM airports WHERE icao='".strtoupper($input)."' limit 1";
	$result=$mysqli->query($query);
	$row=$result->fetch_row();
	if($result->num_rows){
		if(!$phonetize){
			$data=$row[0];
		}else{
			$data=phonetize($row[0],$phonetize);
		}
	}else{
		$data=phonetize($row[0],$phonetize);
	}
	echo strtoupper($data)." ";
}

function get_info($input,$phonetize,$end){
	if($end){
		echo strtoupper("advise controller on initial contact that you have ");
	}
	echo strtoupper("information ".phonetize($input,$phonetize)."... ");
}

function get_zulu_time($phonetize){
	foreach(metar_parts() as $key=>$value){
		if($value=="METAR"||$value=="RMK"){
			break;
		}
		if(!preg_match('@^([0-9]{2})([0-9]{4})(Z)$@', $value, $result, PREG_UNMATCHED_AS_NULL)){
			continue;
		}
		echo strtoupper(phonetize($result[2].$result[3], $phonetize, false));
	}
	echo "... ";
}
function get_winds($phonetize){
	$data=null;
	foreach(metar_parts() as $key=>$value){
		if($value=="METAR"||$value=="RMK"){
			break;
		}
		if(preg_match('@^(VRB|[0-9]{3})([0-9]{2})(G)?([0-9]{2,3})?(MPS|KT|KPH)$@',$value,$result,PREG_UNMATCHED_AS_NULL)){
			if(!$phonetize){
				$data.="winds ";
			}
			else{
				$data.="winnds ";
			}
			
			if($result[1]=="VRB"){
				$data.="variable at ";
			}else{
				if(abs($result[1])<5||abs($result[2])<5){
					$data.="calm";
					continue;
				}
				$data.=phonetize($result[1],$phonetize)." at ".phonetize($result[2],$phonetize);
				if(isset($result[3])){
					$data.=", gusting ".phonetize($result[4],$phonetize);
				}
			}
		}else{
			continue;
		}
	}
	return $data;
}
	
function get_variable_winds($phonetize=false){
	foreach(metar_parts() as $key=>$value){
		if(preg_match('@^([0-9]{3})V([0-9]{3})$@', $value, $result, PREG_UNMATCHED_AS_NULL)){
			return "variable between ".phonetize($result[1], $phonetize)." and ".phonetize($result[2], $phonetize);
		}else{
			continue;
		}
	}
	return false;
}

function full_winds($phonetize,$variable_winds){
	$data=get_winds($phonetize);
	if($variable_winds!=false){
		$data.=", ".strtoupper(get_variable_winds($phonetize));
	}
	echo strtoupper($data)."... ";
}

function get_visibility($phonetize){
	$data=null;
	foreach(metar_parts() as $key=>$value){
		if($value=="METAR"||$value=="RMK"){
			break;
		}
		if(preg_match('@^(CAVOK|[0-9]{4})?(([0-9]{1,2})?((\s)?[1|3|4|7]\/[2|4|6|8|16])?(SM)?)$@',$value,$result, PREG_UNMATCHED_AS_NULL)){
			if(isset($result[1])){
				if($result[1]=="CAVOK"){
					echo "CAVOK... ";
					return false;
				}
				if($result[1]<1000){
					$data.="less than ".phonetize("1",$phonetize)." kilometer";
				}else{
					$data.=phonetize(round($result[1]/1000),$phonetize);
					if($result[1]>1999){
						$data.=" kilometers";
					}else{
						$data.=" kilometer";
					}
				}
			}else{
				if(!is_null($result[2])){
					if(!is_null($result[3])){
						$data.=phonetize($result[3],$phonetize);
					}else{
						$data.="less than ".phonetize("1",$phonetize)." mile";
					}
				}
			}
		}else{
			continue;
		}
	}
	echo strtoupper("visibility ".$data)."... ";
}
						
						
function get_clouds($phonetize){
	$cloud_codes=array(
		"BKN"=>"broken",
		"CB"=>"cumulonimbus",
		"CLR"=>"sky clear",
		"FEW"=>"few",
		"OVC"=>"overcast",
		"SCT"=>"scattered",
		"SKC" =>"sky clear",
		"TCU"=>"towering cumulus",
		"NCD"=>"nil clouds detected",
		null=>null);
	$i=0;
	$clouds=array();
	$is_ceiling=true;
	foreach(metar_parts() as $key=>$value){
		if($value=="METAR"||$value=="RMK"){
			break;
		}
		if(preg_match('@^(BKN|CLR|FEW|OVC|SCT|SKC|NCD)([0-9]{3})?(CB|TCU)?$@', $value, $result, PREG_UNMATCHED_AS_NULL)){
			$cloud_altitude=$result[2]*100;
			if($cloud_altitude>=10000){
				if($phonetize){
					$cloud_altitude=phonetize(substr($cloud_altitude, 0, 2), true)." thousand";
				}
			}
			if($result[1]=="OVC"||$result[1]=="BKN"){
				if($is_ceiling==true){
					$clouds[$i]="ceiling ".$cloud_altitude." ".$cloud_codes[$result[1]];
					$is_ceiling=false;
				}else{
					$clouds[$i]=$cloud_codes[$result[1]]." clouds at ".$cloud_altitude;
				}
			}else{
				if(isset($result[1])&&is_null($result[2])){
					$clouds[$i]=$cloud_codes[$result[1]];
				}else{
					$clouds[$i]=$cloud_codes[$result[1]]." clouds at ".$cloud_altitude;
					if(isset($result[3])){
						$clouds[$i].=" ".$cloud_codes[$result[3]];
					}
				}
			}
			$i++;
		}else{
			continue;
		}
	}
	if(empty($clouds)){
		return false;
	}else{
		foreach($clouds as $key=>$value){
			echo strtoupper($value);
			if($key===array_key_last($clouds)){
				echo "... ";
				return false;
			}
			echo ", ";
		}
	}
}

function get_weather(){
	$wx_codes=array(
		"-"=>"light",
		"+"=>"heavy",
		"VC"=>"in vicinity",
		"MI"=>"shallow",
		"PR"=>"partial",
		"BC"=>"patchy",
		"DR"=>"drifting",
		"BL"=>"blowing",
		"SH"=>"showers",
		"TS"=>"thunderstorm",
		"FZ"=>"freezing",
		"DZ"=>"drizzle",
		"RA"=>"rain",
		"SN"=>"snow",
		"SG"=>"snow grains",
		"IC"=>"ice crystals",
		"PL"=>"ice pellets",
		"GR"=>"hail",
		"GS"=>"small hail",
		"UP"=>"unknown precipitation",
		"BR"=>"mist",
		"FG"=>"fog",
		"FU"=>"smoke",
		"VA"=>"volcanic ash",
		"DU"=>"dust",
		"SA"=>"sand",
		"HZ"=>"haze",
		"PY"=>"spray",
		"PO"=>"well developed dust or sand whirls",
		"SQ"=>"squalls",
		"FC"=>"funnel cloud",
		"SS"=>"sand storm",
		null=>null
	);
	$i=0;
	$weather=array();
	foreach(metar_parts() as $key=>$value){
		if($value=="METAR"||$value=="RMK"){
			break;
		}
		if(preg_match('@^(\-|\+|VC)?(MI|PR|BC|DR|BL|SH|TS|FZ)?(DZ|RA|SN|SG|IC|PL|GR|GS|UP)?(DZ|RA|SN|SG|IC|PL|GR|GS|UP)?(BR|FG|FU|VA|DU|SA|HZ|PY)?(PO|SQ|FC|SS)?$@', $value, $result, PREG_UNMATCHED_AS_NULL)){
			$data=null;
			if(isset($result[1])){
				if($result[1]!="VC"){
					$data.=$wx_codes[$result[1]]." ";
				}else{
					null;
				}
			}
			if(isset($result[2])){
				if($result[2]!="SH"){
					$data.=$wx_codes[$result[2]]." ";
				}
			}
			if(isset($result[3])){
				$data.=$wx_codes[$result[3]]." ";
			}
			if(isset($result[4])){
				$data.="mixed with ".$wx_codes[$result[4]]." ";
			}
			if(isset($result[5])){
				$data.=$wx_codes[$result[5]]." ";
			}
			if(isset($result[6])){
				$data.=$wx_codes[$result[6]]." ";
			}
			if(isset($result[1])&&$result[1]=="SH"){
				$data.=$wx_codes[$result[1]]." ";
			}
			if(isset($result[1])&&$result[1]=="VC"){
				$data.=$wx_codes[$result[1]]." ";
			}
			
			$weather[$i]=trim($data);
			$i++;
		}
		if(preg_match('@^(A|Q)([0-9]{4})$@', $value)){
			break;
		}
	}
	if(empty($weather)){
		return false;
	}else{
		foreach($weather as $key=>$value){
			echo strtoupper($value);
			if($key==array_key_last($weather)){
				echo "... ";
				return false;
			}
			echo ", ";
		}
	}		
}			

function get_altimeter($phonetize){
	foreach(metar_parts() as $key=>$value){
		if($value=="METAR"||$value=="RMK"){
			break;
		}
		if(preg_match('@^(A|Q)([0-9]{4})$@', $value, $result, PREG_UNMATCHED_AS_NULL)){
			if($result[1]=="A"){
				echo strtoupper("altimeter ".phonetize($result[2],$phonetize)."... ");
			}
			elseif($result[1]){
				echo strtoupper("qnh ".phonetize($result[2],$phonetize)."... ");
			}else{
				return false;
			}
		}else{
			continue;
		}
	}
}

function get_temperature($phonetize){
	$data=array(
		"temperature"=>null,
		"dewpoint"=>null);
	foreach(metar_parts() as $key=>$value){
		if($value=="METAR"||$value=="RMK"){
			break;
		}
		if(preg_match('@^(M)?([0-9]{2,3})/(M)?([0-9]{2,3})$@', $value, $result, PREG_UNMATCHED_AS_NULL)){
			if(isset($result[1])){
				$data["temperature"].="minus ";
			}
			if(isset($result[3])){
				$data["dewpoint"].="minus ";
			}
			$data["temperature"].=phonetize(abs($result[2]),$phonetize);
			$data["dewpoint"].=phonetize(abs($result[4]),$phonetize);
		}else{
			continue;
		}
	}
	foreach($data as $key=>$value){
		echo strtoupper($key." ". $value);
		if($key==array_key_last($data)){
			echo "... ";
			return false;
		}
		echo ", ";
	}
}

function get_runways($input,$phonetize,$type){
	$runways=explode(" ",$input);
	echo strtoupper($type." runway in use ");
	for($i=0;$i<=count($runways)-1;$i++){
		$approach_type_check=explode("/",$runways[$i]);
		if(isset($approach_type_check[1])){
			if(strtoupper($approach_type_check[1])=="ILS"){
				echo strtoupper("ILS runway ". phonetize($approach_type_check[0],$phonetize,true));
			}
			elseif(strtoupper($approach_type_check[1])=="VIS"){
				echo strtoupper("visual runway ".phonetize($approach_type_check[0],$phonetize,true));
			}
		}else{
			echo strtoupper("runway ".phonetize($approach_type_check[0],$phonetize,true));
		}
		if($i<count($runways)-1){
			echo ", ";
		}else{
			echo "... ";
		}
	}
}
function get_remarks($input){
	if(!$input){
		return false;
	}
	echo strtoupper("remarks: ".$input."... ");
}

function atis_identifiers(){
	foreach(range("A","Z") as $ident){
		echo "<option value='".$ident."'>".strtoupper(phonetize($ident,true))."</option>\n";
	}
}


$icao = (isset($_POST['icao'])) ? strtoupper($_POST['icao']) : NULL;
$arrival_rwy = (isset($_POST['arrival_rwy'])) ? $_POST['arrival_rwy'] : NULL;
$departure_rwy = (isset($_POST['departure_rwy'])) ? $_POST['departure_rwy'] : NULL;
$ident = (isset($_POST['ident'])) ? $_POST['ident'] : NULL;
$remarks = (isset($_POST['remarks'])) ? $_POST['remarks'] : NULL;

function display_full_atis($phonetize){
	global $icao,$arrival_rwy,$departure_rwy,$ident,$remarks;
	get_airport_name($icao,false);
	get_info($ident,$phonetize,false);
	get_zulu_time($phonetize);
	full_winds($phonetize,get_variable_winds());
	get_visibility($phonetize);
	get_weather();
	get_clouds($phonetize);
	get_temperature($phonetize);
	get_altimeter($phonetize);
	get_runways($arrival_rwy,$phonetize,"arrival");
	get_runways($departure_rwy,$phonetize,"departing");
	get_remarks($remarks);
	get_info($ident, $phonetize, true);
}
?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	<style>
		textarea{
			resize:none!important;
		}
		.hide{
			display:none;
		}
	</style>
    <title>redbeard.cc ATIS Generator</title>
  </head>
	<body>
		<div class="jumbotron">
			<div class="container">
				<h1 class="display-4">redbeard.cc ATIS Generator</h1>
				<div class="lead">A simple tool for creating both human and machine-readable ATIS from current, real world METAR information. Includes the ability to download a spoken version of the ATIS in .mp3 format</div>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<div id="primary" class="col-lg-12">
					<form method="post">
						<div class="form-row">
							<div class="col">
								<label for="icao">Airport ICAO (eg KDTW)</label>
								<div class="input-group mb-1">
									<input type="text" class="form-control" id="icao" name="icao" value="<?php echo $icao; ?>" maxlength="4" required>
								</div>
							</div>                        
							<div class="col">
								<label for="ident">ATIS Identifier</label>
								<div class="input-group">
									<select class="form-control" id="ident" name="ident">
	<!---->
	<!---->
	<?php atis_identifiers();?>
	<!---->
	<!---->
									</select>
								</div>
							</div>
						</div>
					<div class="form-row">
						<div class="col">
							<button type="button" class="show-runway-recommendations btn btn-outline-primary">Get Runway Recommendations</button>
							<button type="button" class="dismiss-runway-recommendations btn btn-outline-danger hide">Dismiss Recommendations</button>
						</div>
					</div>
						<label for="arrival_rwy">Arrival Runways (space separated. eg 22L 22R). <span id="runway-options"><strong>Click here for more options</strong></span></label>
						<div class="input-group mb-1">
							<input type="text" class="form-control" id="arrival_rwy" name="arrival_rwy" value="<?php echo $arrival_rwy;?>" required>
						</div>
						<label for="departure_rwy">Departure Runways (space separated. eg 22L 22R)</label>
						<div class="input-group mb-1">
							<input type="text" class="form-control" id="departure_rwy" name="departure_rwy" value="<?php echo $departure_rwy;?>" required>
						</div>
						<label for="remarks">ATIS Remarks</label>
						<div class="input-group mb-1">
							<textarea class="form-control" id="remarks" name="remarks" rows="4"><?php echo $remarks; ?></textarea>
						</div>
						<button type="submit" class="btn btn-primary">Create ATIS</button>
					</form>
				</div>
				<div id="secondary" class="hide">
					<h4>Runway Recommendations</h4>
					<div id="runway_recommendation" class="table-responsive"></div>
				</div>
			</div>
		</div>
		<div id="sticky-footer" class="flex-shrink-0 py-4">
			<div class="container text-center">
				<div class="row">
					<div class="col text-left">
						<small>Airport and runway data from <a href="https://ourairports.com/data/">Our Airports</a>.<br/>
						Weather data from <a href="https://aviationweather.gov/">NOAA/NWS Aviation Weather Center</a></small>
					</div>
					<div class="col text-right">
						<small><strong>Made in Higgins Lake, Michigan by <a href="https://redbeard.cc/">Redbeard</a></strong></small>
					</div>
				</div>
				<div  class="row">
					<div class="col text-center">
						<small>
							Built using
							<a href="https://getbootstrap.com/">Bootstrap</a>,
							<a href="https://jquery.com/">jQuery</a>,
							<a href="https://www.php.net/">PHP</a> &amp;
							<a href="https://www.mysql.com/">MySQL</a>
						</small>
					</div>
				</div>
			</div>
		</div>
		<!-- Optional JavaScript -->
		<!-- jQuery first, then Popper.js, then Bootstrap JS -->
		<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
		<script>
			$(document).ready(function(){
				$(".show-runway-recommendations").click(function(){
					$("#primary").removeClass("col-lg-12");
					$("#primary").addClass("col-lg-7");
					$("#secondary").removeClass("hide");
					$("#secondary").addClass("col-lg-5");
					$(".dismiss-runway-recommendations").removeClass("hide");
					var xmlhttp = new XMLHttpRequest();
					var query = document.getElementById("icao").value;
					xmlhttp.onreadystatechange=function(){
						if(this.readyState==4&&this.status==200){
							document.getElementById("runway_recommendation").innerHTML = this.responseText;
						}
					};
					xmlhttp.open("GET","runwayrecommendation.php?icao="+query,true);
					xmlhttp.send();
				});
				$(".dismiss-runway-recommendations").click(function(){
					$("#primary").addClass("col-lg-12");
					$("#primary").removeClass("col-lg-7");
					$("#secondary").addClass("hide");
					$("#secondary").removeClass("col-lg-5");
					$(".dismiss-runway-recommendations").addClass("hide");
				});
				$("#runway-options").click(function(){
					$("#runway-options-modal").modal("show");
				});
			});
		</script>
		
<?php
if($_SERVER["REQUEST_METHOD"]=="POST"){
	if(!curl_check($icao)){
?>
<!-- DISPLAY FAILURE MODAL-->
<script>
	$(document).ready(function(){
		$("#modal-atis-fail").modal("show");
	});
</script>
<!--/DISPLAY FAILURE MODAL-->

<?php
	}else{
		$metar=explode("\n",curl_get($icao));
		function metar_global(){
			global $metar;
			return $metar[1];
		}

		function metar_parts(){
			return explode(' ', metar_global());
		}
?>

<div style="display:none;" id="human-readable-atis"><?php display_full_atis(false); ?>Created using the redbeard.cc ATIS Generator (https://atis.redbeard.cc) by Redbeard Creative Company.</div>
<div style="display:none;" id="machine-readable-atis"><?php display_full_atis(true); ?>Created using the redbeard.cc ATIS Generator at atis.redbeard.cc</div>
<script>
$(document).ready(function(){
	$("#modal-atis-success").modal("show");
	$("#copy-atis").click(function(){
		var query = $("#human-readable-atis").text();
		navigator.clipboard.writeText(query);
	});
				
	var humanReadableBackup = $("#human-readable-atis").text();
	$("#human-readable-backup").text(humanReadableBackup);
	
	var machineReadableAtis=$("#machine-readable-atis").text();
	$("#download-audio-atis").attr("href", "download.php?q="+machineReadableAtis);
});
</script>

<?php
	}
}
?>

<!--MODALS-->
<!--Runway Options-->
<div class="modal fade" id="runway-options-modal" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Runway Options</h5>
			</div>
			<div class="modal-body">
				<p>You now have the option to  choose between <em>visual</em> and <em>instrument</em> approaches for individual runways.</p>
				<p>This can be done by adding either <em>/VIS</em> or <em>/ILS</em> after a runway. The tool takes care of parsing this information properly.</p>
				<p><strong>Example</strong></p>
				<p>Entering <em>22L/ILS 22R/VIS</em> will tell the tool to output the appropriate approach type into your ATIS, and would read <em>"Arrival runway in use ILS Runway 22L, Visual Runway 22R"</em></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close Window</button>
			</div>
		</div>
	</div>
</div>
<!---->
<!--Generation Success-->
<div class="modal fade" id="modal-atis-success" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Success!</h5>
			</div>
			<div class="modal-body">
				<p>Your ATIS is ready to use! You can click the buttons below to copy the human readable version to your clipboard, save the machine spoken version in .mp3 format, or close this window.</p>
				<p>NOTE: Not all browsers allow the "Copy Text Version To Clipboard" button to work, so here's your human readable ATIS that you can manually copy and paste if that's the case:</p>
				<textarea class="form-control" id="human-readable-backup" rows="4"></textarea>
			</div>
			<div class="modal-footer">
				<a class="btn btn-primary" id="download-audio-atis" href="#">Download Spoken ATIS in .mp3</a>
				<button type="button" class="btn btn-info" id="copy-atis">Copy Text Version To Clipboard</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close Window</button>
			</div>
		</div>
	</div>
</div>
<!--Generation Failure-->
<div class="modal fade" id="modal-atis-fail" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Oops!</h5>
			</div>
			<div class="modal-body">No weather data was found through the <a href="https://aviationweather.gov/adds/" target="_blank">NOAA/NWS Aviation Weather Service</a> Database for <?php echo $icao; ?>. It's okay, though. If you can't find the exact airport you're looking for, you can always try to look for weather at an airport nearby.</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close Window</button>
			</div>
		</div>
	</div>
</div>
<!---->
<!--/MODALS-->
	</body>
</html>
		