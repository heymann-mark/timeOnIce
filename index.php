<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.js"></script>
<!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>-->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<link rel="stylesheet" href="/css/toi.css">
<title>Time On Ice</title>
</head>
<body>
  <h2>Game Time</h2>
	<div class=row id="canvas-holder" style="width: 150px;">
		<h3>score</h3>
		<div class=inline>
			<div class=inline>
				<svg width="30" height="15">
            <rect width="30" height="15" style="fill:rgba(255,0,0,0.2);stroke-width:3;stroke:rgba(255,0,0,1)" />
            </svg> winning
			</div> <br>
			<div class=inline>
				<svg width="30" height="15">
               <rect width="30" height="15" style="fill:rgba(0,255,0,0.2);stroke-width:3;stroke:rgba(0,255,0,1)" />
            </svg> losing
			</div> <br>
			<div class=inline>
				<svg width="30" height="15">
               <rect width="30" height="15" style="fill:rgba(0,0,255,0.2);stroke-width:3;stroke:rgba(0,0,255,1)" />
            </svg> tied
			</div>
		</div>
		<canvas id="chart-area" width="300" height="300"></canvas>
	</div>
	<!-- canvas holder -->
	<div class=row id="canvas-holder2" style="width: 150px;">
		<h3>powerplay</h3>
		<div class=inline>
			<div class=inline>
				<svg width="30" height="15" float="left">
          <rect width="30" height="15" style="fill:rgba(255,0,0,0.2);stroke-width:3;stroke:rgba(255,0,0,1)" />
        </svg> Short Handed
			</div> <br>
			<div class=inline>
				<svg width="30" height="15" float="left">
          <rect width="30" height="15" style="fill:rgba(0, 255,0,0.2);stroke-width:3;stroke:rgba(0,255,0,1)" />
        </svg> Power Play
			</div> <br>
			<div class=inline>
				<svg width="30" height="15" float="left">
          <rect width="30" height="15" style="fill:rgba(0,0,255,0.2);stroke-width:3;stroke:rgb(0,0,255,1)" />
        </svg> Even Strength
			</div>
		</div>
		<canvas id="chart-area2" width="300" height="300"></canvas>
	</div>
	<br></br>
	<div class=inline>
		<h1>Deployment</h1>
    <?php
    include 'score.php';
    include 'power.php';
    // echo "playersTotalToi: ".$playersTotalToi;
    $upPercent = (round($playersUpTime / ($playersUpTime + $playersDownTime + $playersTieTime) * 100, 2));
    $downPercent = (round($playersDownTime / ($playersUpTime + $playersDownTime + $playersTieTime) * 100, 2));
    $tiePercent = (round($playersTieTime / ($playersUpTime + $playersDownTime + $playersTieTime) * 100, 2));

    $shortHandedPercent = round($playersShortHandSeconds / $playersTotalToi * 100, 2);
    $powerPlayPercent = round($playersPowerPlaySeconds / $playersTotalToi * 100, 2);
    $evenStrengthPercent = round(($playersTotalToi - ($playersPowerPlaySeconds + $playersShortHandSeconds)) / $playersTotalToi * 100, 2);

    function format_time($t, $games)
    { // to be printed to screen
      $time = floor($t / ((count($games) * 60))) . ":";
      if (round((($t / ((count($games) * 60)) - floor($t / ((count($games) * 60)))) * 60)) < 10)
          $time = $time . "0";
      $time = $time . round((($t / ((count($games) * 60)) - floor($t / ((count($games) * 60)))) * 60));
      return $time;
    }

    // this is for the pie chart tooltips, because the
    // pie cahrt is picky about formatting
    function format_time_pie($t, $games)
    {
      $time = array();

      $time['minute'] = floor($t / ((count($games) * 60)));
      if (round((($t / ((count($games) * 60)) - floor($t / ((count($games) * 60)))) * 60)) < 10)
          $time['second'] = "0".round((($t / ((count($games) * 60)) - floor($t / ((count($games) * 60)))) * 60));
      else {
          $time['second'] = round((($t / ((count($games) * 60)) - floor($t / ((count($games) * 60)))) * 60));
      }
      if ($time['second'] < 10)
          $time['second'] = $time['second'] . "0";
      return $time;
    }
    $evSeconds = $playersTotalToi - ($playersPowerPlaySeconds + $playersShortHandSeconds);
    ?>
    <table>
      <?php
      echo "<tr>"."number of games played: ".count($games)."</tr><br>";
      echo "<tr>"."number of goals: ".$goals ."</tr><br>";
      echo "<tr>"."number of assists: ".$assists ."</tr><br>";
      echo "<tr>"."points: ".($assists + $goals) ."</tr><br>";
      echo "<tr>"."+/-: ".($plusminus) . "</tr><br>";
      echo "<tr>"."average minutes per game: ".format_time($playersTotalToi, $games) . "</tr><br>";
      echo "<tr>"."average shorthanded minutes per game: ".format_time($playersShortHandSeconds, $games) . "</tr><br>";
      echo "<tr>"."average EV minutes per game: ".format_time($evSeconds, $games) . "</tr><br>";
      echo "<tr>"."average powerplay minutes per game: ".format_time($playersPowerPlaySeconds, $games) . "</tr><br>";
      echo "<tr>"."average minutes per game while tied: ".format_time($playersTieTime, $games) . "</tr><br>";
      echo "<tr>"."average minutes per game while winning: ".format_time($playersUpTime, $games) . "</tr><br>";
      echo "<tr>"."average minutes per game while losing: ".format_time($playersDownTime, $games) . "</tr><br>";
      echo "<tr>"."<b>Corsi (EV)</b>"."</tr><br>";
      echo "<tr>"."Corsi For at Even Strength: ".$corsiFor . "</tr><br>";
      echo "<tr>"."Corsi Against at even strength: ".$corsiAgainst . "</tr><br>";
      echo "<tr>"."Corsi:".($corsiFor - $corsiAgainst) . "</tr><br>";
      echo "<tr>"."Corsi For percentage at even Strength: ".round((100 * $corsiFor / ($corsiFor + $corsiAgainst)), 2)."%"."</tr><br>";
      echo "<tr>"."Relative Corsi For percentage at even Strength: ".round(((100 * $corsiFor / ($corsiFor + $corsiAgainst)) - (100 * $corsiForOff / ($corsiForOff + $corsiAgainstOff))), 2) . "%"."</tr><br>";
      echo "<tr>"."<b>Fenwick (EV)</b>"."</tr><br>";
      echo "<tr>"."Fenwick For at Even Strength: ".$fenwickFor . "</tr><br>";
      echo "<tr>"."Fenwick Against at Even Strength: ".$fenwickAgainst . "</tr><br>";
      echo "<tr>"."Fenwick: ".($fenwickFor - $fenwickAgainst) . "</tr><br>";
      echo "<tr>"."Fenwick For percentage at Even Strength: ".round((100 * $fenwickFor / ($fenwickFor + $fenwickAgainst)), 2) . "%"."</tr><br>";
      echo "<tr>"."Relative Fenwick For percentage at even Strength: ".round(((100 * $fenwickFor / ($fenwickFor + $fenwickAgainst)) - (100 * $fenwickForOff / ($fenwickForOff + $fenwickAgainstOff))), 2) . "%"."</tr><br>";
      echo "<tr>"."<b>PDO  (EV)</b>"."</tr><br>";
      echo "<tr>"."oiSH%: ".round((100 * ($GoalonIceEV / ($GoalonIceEV + $corsiFor))), 2) . "</tr><br>";
      echo "<tr>"."oiSV%: ".round((100 * ($saveOn / ($GoalonIceEV + $corsiFor))), 2) . "</tr><br>";
      echo "<tr>"."PDO: ".round((100 * (($GoalonIceEV / ($GoalonIceEV + $corsiFor)) + ($saveOn / ($GoalonIceEV + $corsiFor)))), 2) . "</tr><br>";
      ?>
    </table>
	</div>

<script>
var config = {
    type: 'pie',
        data: {
            labels:["Winning", "Losing","Tied"],
            datasets: [{
                data: [<?php  echo $upPercent ?>, <?php echo $downPercent?>, <?php echo $tiePercent?>],
                minutes:[<?php echo  format_time_pie($playersUpTime,$games)['minute'] ?>,
                <?php echo format_time_pie($playersDownTime,$games)['minute'] ?>,
                <?php echo format_time_pie($playersTieTime,$games)['minute']?>],
                seconds:[<?php echo format_time_pie($playersUpTime,$games)['second'] ?>,
                        <?php echo format_time_pie($playersDownTime,$games)['second'] ?>,
                                <?php echo format_time_pie($playersTieTime,$games)['second'] ?>],
                borderColor: ['rgba(255, 0, 0, 1)', 'rgba(0, 255, 0, 1)','rgba(0, 0, 255, 1)'],
                backgroundColor: ['rgba(255, 0, 0, 0.2)', 'rgba(0, 255, 0, 0.2)','rgba(0, 0, 255, 0.2)'],
                borderAlign: ['inner', 'inner','inner'],
                  }],
        },
        options: {
            responive: true,
            legend: {
                display: false
            },
	        tooltips: {
	           	 caretSize: 0,
    	         mode: 'index',
    	         backgroundColor: 'rgba(0,0,0,0,0)',
    	    	 bodyFontSize: 18,
    	    	 callbacks: {
    	    		 title: function(tooltipItem, data) {
    	    	          return data['labels'][tooltipItem[0]['index']];
    	    	        },
    	    	        label: function(tooltipItem, data) {var pct = 0;
    	    	            pct =  data['datasets'][0]['data'][tooltipItem['index']];
    	    	            pct = pct + "%";
    	    	            return pct;
    	    	          },
        	    	 footer: function (tooltipItems, data){
            	    	 var minutes = 0 ;
            	    	 var seconds = 0;
        	    	     tooltipItems.forEach(function(tooltipItem) {
            	    	    minutes =  data.datasets[tooltipItem.datasetIndex].minutes[tooltipItem.index];
            	    	    seconds =  data.datasets[tooltipItem.datasetIndex].seconds[tooltipItem.index];
        	    	     });
        	    	     return minutes + ":"+seconds+" minutes";
        	    	 },
	    	         },
	    	      footerFontSize: 18,
	    	      footerFontStyle: 'normal',
	    	      titleFontSize: 18,
          },
      }
}

var config2 = {
    type: 'pie',
    borderAlign: 'inner',
    data: {
        labels: ["Short Handed","Power Play","Even Strength"],
        datasets:[{
            data: [<?php echo $shortHandedPercent?>,<?php echo $powerPlayPercent ?>,<?php echo $evenStrengthPercent ?>],
            minutes:[<?php echo format_time_pie($playersShortHandSeconds,$games)['minute'] ?>,
            <?php echo format_time_pie($playersPowerPlaySeconds,$games)['minute'] ?>,
            <?php echo format_time_pie($evSeconds,$games)['minute']?>],
            seconds:[<?php echo format_time_pie($playersShortHandSeconds,$games)['second']?>,
                    <?php echo format_time_pie($playersPowerPlaySeconds,$games)['second'] ?>,
                    <?php echo format_time_pie($evSeconds,$games)['second']?>],
            borderColor: ['rgba(255,0, 0, 1)', 'rgba(0, 255, 0, 1)','rgba(0, 0, 255, 1)'],
            backgroundColor: ['rgba(255, 0, 0, 0.2)', 'rgba(0, 255, 0, 0.2)','rgba(0, 0, 255, 0.2)'],
              }],
    },
    options: {
        responive: true,
        legend: {
            display: false
         },
         tooltips: {
	        	caretSize: 0,
 	         mode: 'index',
 	         backgroundColor: 'rgba(0,0,0,0,0)',
 	    	 bodyFontSize: 18,
 	    	 callbacks: {
 	    		 title: function(tooltipItem, data) {
 	    	          return data['labels'][tooltipItem[0]['index']];
 	    	        },
 	    	        label: function(tooltipItem, data) {var pct = 0;
 	    	            pct =  data['datasets'][0]['data'][tooltipItem['index']];
 	    	            pct = pct + "%";
 	    	            return pct;
 	    	          },
     	    	 footer: function (tooltipItems, data){
         	    	 var minutes = 0 ;
         	    	 var seconds = 0;
     	    	     tooltipItems.forEach(function(tooltipItem) {
         	    	    minutes =  data.datasets[tooltipItem.datasetIndex].minutes[tooltipItem.index];
         	    	   seconds =  data.datasets[tooltipItem.datasetIndex].seconds[tooltipItem.index];
     	    	     });
     	    	     return minutes + ":"+seconds+" minutes";
     	    	 },
    	         },
    	      footerFontSize: 18,
    	      footerFontStyle: 'normal',
    	      titleFontSize: 18,

         },
    }
}
window.onload = function() {
  var pie = document.getElementById("chart-area").getContext('2d');
  var pie2 = document.getElementById("chart-area2").getContext('2d');
  window.myChart = new Chart(pie, config);
  window.myChart2 = new Chart(pie2, config2);
};
</script>

</body>

</html>
