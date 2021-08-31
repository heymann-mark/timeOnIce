<?php
// get the complete list of everybody's time on ice during his games, both his team and opponents
// examples:
// Danick Martel start-time: 20:00 end-time: 19:21 period 1 game 19
// Danick Martel start-time: 18:44 end-time: 17:59 period 1 game 19
// i made a timepoint roster for every timepoint of every game that our
// player was in, so for example game:19, period: 1, time: 19:32
// will have two rosters associated with it,
// $game[$gameid][$period][$i]['playersTeam']
// $game[$gameid][$period][$i]['opponentsTeam']
// our team and the opponents team
// normally, both rosters will have 6 players a piece on them, but
// if one of the opponents team roster is less than six, its a powerplay
$toiquery = "select * from toi
              where game_id in  (select distinct game_id from toi
              where player_id = 58)
              order by game_id asc,
              shift_period asc,
              start_time desc,
              end_time desc
              ";

$toiresult = pg_query($connect, $toiquery);
$playersTotalToi = 0;//Player's total time on ice
$games = array();//holds all info we need about each game
if (pg_num_rows($toiresult) > 0) {

    while ($row = pg_fetch_array($toiresult)) {
        $playerTeam = 'LV'; // hardcoded, should be player.team_id
        $gameid = $row['game_id'];
        $period = $row['shift_period'];
        $startTime = ($row['start_time'][3] . $row['start_time'][4]) * 60 + $row['start_time'][6] * 10 + $row['start_time'][7];
        $endTime = ($row['end_time'][3] . $row['end_time'][4]) * 60 + $row['end_time'][6] * 10 + $row['end_time'][7];
        // if the row is one of the player we're seeking information about,
        // subtract end time from start time and add to the total time on ice for the player
        // add 1 because 1190 - 1180 = 10, but that's 11 seconds

        // look at each row
        // if row has 58 player id, add to seconds
        if ($row['player_id'] == 58) {
            $playersTotalToi = $playersTotalToi + ($startTime - $endTime) + 1;
        }
        // The following block of code looks at the start time and end time of an individual row
        // And adds that player to all the timepoint rosters fromm start time to end time
        for ($i = $startTime; $i >= $endTime; -- $i) {

            if (! isset($games[$gameid][$period][$i]['playersTeam']))
                $games[$gameid][$period][$i]['playersTeam'] = array();

            if (! isset($games[$gameid][$period][$i]['opponentsTeam']))
                $games[$gameid][$period][$i]['opponentsTeam'] = array();

            if ($row['team_id'] == $playerTeam) {
                array_push($games[$gameid][$period][$i]['playersTeam'], $row['player_id']);
            } else {
                array_push($games[$gameid][$period][$i]['opponentsTeam'], $row['player_id']);
            }
        }
    }
}

$playersPowerPlaySeconds = 0;//players total time on ice during powerplay
$playersShortHandSeconds = 0;//players total time on ice while shorthandced
$playersUpTime = 0;//players total time on ice while his team was winning
$playersDownTime = 0;//players total time on ice while his team was losing
$playersTieTime = 0;//players total time on ice while his team was tied
$i = 0; // index for $gamekeys. $gamekeys[0] will be the first game_id, etc...
$powerplayMin = 5; // the minimum number of seconds of having more players to count as a powerplay
$shortHandMin = 5; // the minimum number of seconds of having less players to count as shorthanded
$gamekeys = array_keys($games); // all our players games
$corsiFor = 0; // saves plus blocks plus misses while player on ice
$corsiAgainst = 0; // opponent saves, blocks, and misses while player is on ice
$fenwickAgainst = 0; // //opponent saves and misses while player is on ice
$fenwickFor = 0; // saves and misses by player's team while player is on ice
$fenwickAgainstOff = 0; // saves and misses by opponent team while player id off the ice
$fenwickForOff = 0; // saves and misses by player's team while player id off the ice
$corsiForOff = 0; // saves misses and blocks by player's team while player is off the ice
$corsiAgainstOff = 0; // saves, misses, and blocks by opponents team while player is off theice
$GoalonIceEV = 0; // player's team scores goal while player is on ice
$saveOn = 0; // player's team saves while player is on ice

// the code below loops through our players games, all those games' periods, and all those periods' timepoints(1200 - 0)
forEach ($games as $game) {// for each game
    $p = 0; // The period, if I loop through the periods, I'll vary p from 0 to three or 4
    forEach ($game as $period) { // Theres 3 or 4(4 if we went to overtime)
        $consecutivePowerPlaySeconds = 0; // I'm only counting it as a powerplay if it lasts more than 5 seconds
        $consecutiveShortHandedSeconds = 0; // Same for a shorthanded play
        $periodkeys = array_keys($period);
        $tp = 0; // $tp will vary from 0 to 1200, because there are 1200 timepoints in a period
        forEach ($period as $timepoint) { // for each timepoint in the shift, there's 1201
            if (in_array(58, $timepoint['playersTeam'])) { // Player was on the ice    
                //anytime player was on ice 
                if ($score[$gamekeys[$i]][$p + 1][$periodkeys[$tp]] == 'winning')
                    ++ $playersUpTime;
                if ($score[$gamekeys[$i]][$p + 1][$periodkeys[$tp]] == 'losing')
                    ++ $playersDownTime;
                if ($score[$gamekeys[$i]][$p + 1][$periodkeys[$tp]] == 'tied')
                    ++ $playersTieTime;
                // Sometimes there were 15 or 16 people on the ice, and so one team would have less players than the others,
                // but this wasn't a powerplay because those are 4 on 5, 3 on 5 etc....
                if ((count($timepoint['playersTeam'])<=6) && (count($timepoint['opponentsTeam'])) <= 6) { // Player was on the ice, less than 12 people were on the ice in total
                                                                                                    // if our team has less players on theice than the other team, and this has been the case for more than 5 seconds...it counts

                    // player was on the ice, less than 6 players  in total on ice, powerplay
                    if (count($timepoint['playersTeam']) > count($timepoint['opponentsTeam'])) {
                        ++ $consecutivePowerPlaySeconds; // keep track of how long this powerplay has been going on
                        if ($consecutivePowerPlaySeconds >= $powerplayMin) {
                            ++ $playersPowerPlaySeconds;
                        } // if long enough add this tp to powerplay

                        if (! empty($eventsAgainst['goal'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) {
                            -- $plusminus;
                        } // if they scored while they were shorthanded, and our player is on ice, minus
                    } else {
                        $consecutivePowerPlaySeconds = 0;
                    }

                    // player was on the ice, less than 12 people in total on ice, not EV, players team shorthanded
                    if (count($timepoint['playersTeam']) < count($timepoint['opponentsTeam'])) {
                        ++ $consecutiveShortHandedSeconds;
                        if ($consecutiveShortHandedSeconds >= $shortHandMin) {
                            ++ $playersShortHandSeconds;
                        }

                        if (! empty($eventsFor['goal'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) {
                            ++ $plusminus;
                        } // player on ice, shorthanded goal
                    } else {
                        $consecutiveShortHandedSeconds = 0;
                    }
                } // Close Player was on the ice, less than 12 people were on the ice in total
                
                    if ((count($timepoint['playersTeam']) == count($timepoint['opponentsTeam']))) { // Player was on the ice, EV

                        // corsiFor = goals + saves + blocked_shots + missed_shots
                        // corsiAgainst = goals by opponent,  saves by opponent + blocked_shots by opponent + missed_shots by opponent
                        // corsi = corsiFor - corsiAgainst
                        // fenwickFor = goals, saves + missed_shots
                        // fenwickAgainst = goals, saves by opponent + missed shots by opponent
                        // fenwick = fenwickFor - fenwickAgainst

                        if (! empty($eventsFor['save'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // players team shot saved while player on ice and EV
                            ++ $saveOn; // keeps track of saves while player on ice
                            ++ $corsiFor;
                            ++ $fenwickFor; 
                        }

                        if (! empty($eventsFor['blocked_shot'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // player's team shot blocked while player on ice and EV
                            ++ $corsiFor;
                        }
                        if (! empty($eventsFor['missed_shot'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // player's team misses shot while player on ice and EV
                            ++ $corsiFor;
                            ++ $fenwickFor; 
                        }

                        if (! empty($eventsFor['goal'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // player's team score while player on ice and EV
                            ++ $GoalonIceEV;
                            ++ $plusminus;
                            ++ $corsiFor;
                            ++ $fenwickFor; 
                        }

                        if (! empty($eventsAgainst['save'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // opponent's shot saved while player on ice and EV
                            ++ $corsiAgainst;
                            ++ $fenwickAgainst; 
                        }
                        if (! empty($eventsAgainst['blocked_shot'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // opponent's shot blocked while player on ice and EV
                            ++ $corsiAgainst; 
                        }
                        if (! empty($eventsAgainst['missed_shot'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // opponent misses shot while player on ice and EV
                            ++ $corsiAgainst;
                            ++ $fenwickAgainst; 
                        }
                        if (! empty($eventsAgainst['goal'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // opponent scores while player on ice and EV
                            -- $plusminus; 
                            ++ $corsiAgainst;
                            ++ $fenwickAgainst;
                        }
                    } // Close Player was on the ice, EV.
            } else { // Player is not on ice at this timepoint
                if ((count($timepoint['playersTeam']) == count($timepoint['opponentsTeam']))) { // Player is not on the ice, EV

                    if (! empty($eventsFor['save'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // saves, player off ice, EV
                        ++ $corsiForOff;
                        ++ $fenwickForOff;
                    }
                    if (! empty($eventsFor['missed_shot'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // missed shots, player off ice, EV
                        ++ $corsiForOff;
                        ++ $fenwickForOff;
                    }
                    if (! empty($eventsFor['blocked_shot'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // blocked shots, player off ice EV
                        ++ $corsiForOff;
                    }
                    if (! empty($eventsFor['goal'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // goals, player off ice, EV
                        ++ $corsiForOff;
                        ++ $fenwickForOff;
                    }

                    if (! empty($eventsAgainst['save'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // saves by opponent,player off ice
                        ++ $corsiAgainstOff;
                        ++ $fenwickAgainstOff;
                    }
                    if (! empty($eventsAgainst['blocked_shot'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // blocked_shot_by_opponent, player off ice
                        ++ $corsiAgainstOff;
                    }
                    if (! empty($eventsAgainst['missed_shot'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // missed_shot_by_opponent, player off ice
                        ++ $corsiAgainstOff;
                        ++ $fenwickAgainstOff;
                    }
                    if (! empty($eventsAgainst['goal'][$gamekeys[$i]][$p + 1][$periodkeys[$tp]])) { // goal by opponent, player off ice
                        ++ $corsiAgainstOff;
                        ++ $fenwickAgainstOff;
                    }
                } //close playeroff, EV
            } //close player off ice
            ++ $tp;
        } //increment second, Close for each period as timepoint
        ++ $p;
    } //increment period, close for each game as period
    ++ $i;
}//increment $i for the gamekeys array, advancing us to th e next game, close for each games as game
