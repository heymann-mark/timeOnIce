<?php
$connect = pg_connect("host = hockeydb-prod.cqnkxgkqtyxw.us-west-1.rds.amazonaws.com dbname = hockeydb port=5432 user = readonly password=nJEqr66UkT08") or die("Could not connect to DB: ");
// here i get all rows where a goal is scored by martels team or the other team
$eventsquery = " select  events.team_id, event_id,game_id,period, time, type, 
                 main_player_id, second_player, third_player, main_location, player_id, position
                from events, players
				where player_id = main_player_id
				and (type = 'goal'
                OR type = 'shot'
                OR type = 'blocked_shot'
                OR type = 'missed_shot')
                and game_id in (select distinct game_id from toi
                                where player_id = 58)
								order by 
								game_id asc,
								period asc,
								time desc
                                ";

$eventsresult = pg_query($connect, $eventsquery);
$score = array();
$gameid = 999999;
$goals = 0;
$assists = 0;
$plusminus = 0;
// here i go through the rows, so for every game, every period, every timepoint, ill know if martel was winning, losing, or tied
//So at first, every second of the game is marked as tied,
//then, if a row says that a goal was scored, we mark our time as winning or losing acording to who scored the goal
//and mark every timepoint in the game after that as being the same, then continue to the next row and repeat.
//for example:
//.....tttttttttttttttttttttttttttttttttttttt...
//goal scored by opponent
//.....tttttlllllllllllllllllllllllllllllllll...
//goal scored by us
//.....tttttllllllllllllltttttttttttttttttttt...
//another goal scored by us
//.....tttttlllllllllllllttttttttttwwwwwwwwww...

if (pg_num_rows($eventsresult) > 0) {
    while ($row = pg_fetch_array($eventsresult)) {
        $time = (($row['time'][3] . $row['time'][4]) * 60 + $row['time'][6] * 10 + $row['time'][7]);
        
        if($row['type'] == 'goal'){
            
            if($row['main_player_id'] == 58){++$goals;}
            
            if(($row['second_player'] == 58) ||($row['third_player'] == 58)){++$assists;}
           
            if($row['team_id'] == 'LV'){
             $eventsFor['goal'][$row['game_id']][$row['period']][$time] = $row['team_id'];
            }else{$eventsAgainst['goal'][$row['game_id']][$row['period']][$time] = $row['team_id'];}
        }
        if($row['type'] == 'shot'){
            if($row['team_id'] == 'LV'){
                $eventsFor['save'][$row['game_id']][$row['period']][$time] = $row['team_id'];
            }else{$eventsAgainst['save'][$row['game_id']][$row['period']][$time] = $row['team_id'];}
        }
        if($row['type'] == 'missed_shot'){
            if($row['team_id'] == 'LV'){
                $eventsFor['missed_shot'][$row['game_id']][$row['period']][$time] = $row['team_id'];
            }else{$eventsAgainst['missed_shot'][$row['game_id']][$row['period']][$time] = $row['team_id'];}
        }
        if($row['type'] == 'blocked_shot'){
            if($row['team_id'] == 'LV'){
                $eventsFor['blocked_shot'][$row['game_id']][$row['period']][$time] = $row['team_id'];
            }else{$eventsAgainst['blocked_shot'][$row['game_id']][$row['period']][$time] = $row['team_id'];}
        }
      //  print_r($corsiFor);
        if($row['type'] == 'goal'){
        if ($gameid != $row['game_id']) {
            for ($i = 1; $i < 5; ++ $i) {
                for ($k = 1200; $k > - 1; -- $k) {
                    $score[$row['game_id']][$i][$k] = 'tied';
                }
            }
            $myscore = 0;
            $theirscore = 0;
        }
        $gameid = $row['game_id'];
        $period = $row['period'];
        $time = (($row['time'][3] . $row['time'][4]) * 60 + $row['time'][6] * 10 + $row['time'][7]); // 1190

        if ($row['team_id'] == 'LV')
            ++ $myscore;
        if ($row['team_id'] != 'LV')
            ++ $theirscore;

        if ($myscore < $theirscore)
            $currentvalue = 'losing';
        if ($myscore == $theirscore)
            $currentvalue = 'tied';
        if ($myscore > $theirscore)
            $currentvalue = 'winning';

        for ($i = $time; $i > - 1; -- $i) {
            $score[$gameid][$period][$i] = $currentvalue; // all ws from 1190 on in this period
        }
        for ($i = ($period + 1); $i < 5; ++ $i) {//and for all periods after
            for ($k = 1200; $k > - 1; -- $k) {
                $score[$gameid][$i][$k] = $currentvalue;
            }
        } //for the periods (4- currentperiod)
    }
    }
}

?>