<?php
	if(empty($_GET['icao'])){
		echo "No ICAO provided. Please fill \"Airport ICAO\" field and attempt to calculate runway recommendations again.";
		return false;
	}
	$ICAO=strtoupper($_GET['icao']);
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
	$metar=explode("\n",curl_get(strtoupper($_GET['icao'])));
	function metar_global(){
		global $metar;
		return $metar[1];
	}

	function metar_parts(){
		return explode(' ', metar_global());
	}
	function get_winds(){
		foreach(metar_parts() as $key=>$value){
			if($value=="METAR"||$value=="RMK"){
				break;
			}
			if(preg_match('@^(VRB|[0-9]{3})([0-9]{2})(G)?([0-9]{2,3})?(MPS|KT|KPH)$@',$value,$result,PREG_UNMATCHED_AS_NULL)){
				return $result[1];
			}else{
				continue;
			}
		}
	}

	function angleDiff($angleStart, $angleTarget) {
		$delta = $angleTarget - $angleStart;
		$direction = ($delta > 0) ? -1 : 1;
		$absDelta1 = abs($delta);
		$absDelta2 = 360 - $absDelta1;
		return $direction * ($absDelta1 < $absDelta2 ? $absDelta1 : $absDelta2);
	}
	if(!curl_check(strtoupper($_GET['icao']))){
		echo "No wind data available for ".strtoupper($_GET['icao']).". No runway recommendations can be calculated.";
		return false;
	}else{
		$mysqli=new mysqli('localhost','redbbqhz_Vbyn','WentNeed$l5FQ','redbbqhz_atis_generator');
		$query="SELECT runways FROM airports WHERE icao='".$ICAO."' limit 1";
		$result=$mysqli->query($query);
		if($result->num_rows){
			$row=$result->fetch_row();
			$runways=explode(",",$row[0]);
			$data=array();
			for($i=0;$i<=count($runways)-1;$i++){
				if($runways[$i]=="XX"){ continue; }
				$approx_runway_heading = str_pad(substr($runways[$i],0,2),3,"0");
				if(get_winds()=="VRB"){
					echo "Winds at ".$ICAO." are reported as \"VARIABLE\". No runway recommendations can be calculated.";
					$approx_heading_difference="???";
					return false;
				}else{
					$winds=get_winds();
					if($winds=="000"){
						echo "Winds at ".$ICAO." are reported as having no discernable heading. Usually this means that the winds are calm. No runway recommendations can be calculated.";
						$approx_heading_difference="???";
						return false;
					}else{
						$approx_heading_difference=abs(angleDiff(get_winds(),$approx_runway_heading));
					}
				}
				$data[$i]['runway']=$runways[$i];
				$data[$i]['approx_runway_heading']=$approx_runway_heading;
				$data[$i]['wind_heading']=$winds;
				$data[$i]['approx_heading_difference']=$approx_heading_difference;
			}
			$approx_difference = array_column($data, "approx_heading_difference");
			
			array_multisort($approx_difference, SORT_ASC, $data);
			$total_entries=count($data);
			if($total_entries>1){
				$limit=$total_entries*0.5;
			}else{
				$limit=0;
			}
			echo "<table class='table table-striped'>";
			echo " <thead class='thead-dark'>";
			echo "  <tr>";
			echo "   <th scope='col'>Runway</th>";
			echo "   <th scope='col'>Approx. Hdg</th>";
			echo "   <th scope='col'>Wind</th>";
			echo "   <th scope='col'>Difference</th>";
			echo "  </tr>";
			echo " </thead>";
			echo " <tbody>";
			for($i=0;$i<=$total_entries-1;$i++){
//				if($data[$i]['approx_heading_difference']>90){ break; }
				echo "<tr>";
				echo " <th scope='col'>".$data[$i]['runway']."</th>";
				echo " <td>".$data[$i]['approx_runway_heading']."</td>";
				echo " <td>".$data[$i]['wind_heading']."</td>";
				echo " <td>".$data[$i]['approx_heading_difference']."</td>";
				echo "</tr>";
			}
			echo " </tbody>";
			echo "</table>";

		}else{
			echo "No runway data available for ".$ICAO.". No runway recommendations can be calculated.";
			return false;
		}
	}
?>