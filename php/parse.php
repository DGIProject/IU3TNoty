<?php
include 'bdd_connect.php';
include 'simple_html_dom.php';

//Remove Warnings
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('Europe/Paris');
echo '<h1>IU3TNoty - V1.0</h1>It\'s now : ' . date("d/m/y G:i:s") . '.<hr>';

$separator = ' - ';
$months = ['Janvier' => '01', 'Février' => '02', 'Septembre' => '09', 'Octobre' => '10', 'Novembre' => '11', 'Décembre' => '12'];

$urls = gUrls();

$classesTomorrow = gTimetable(1);
$classesToday = gTimetable(0);

//Actualisation de l'emploi du temps
echo '<h2>Lessons Actualize</h2><ul>';
$done = false;
$i = 0;

while(!$done) {
    $countClassTimeTable = saveTimetable($i);

    if($countClassTimeTable <= 0) {
        $done = true;
    }
    else {
        echo '<li>Day ' . $i . ' : ' . $countClassTimeTable . ' lesson(s) </li>';
    }

    $i++;
}

echo '</ul><hr>';

$iTonight = 0;
$iBeforeClass = 0;

echo '<h2>Timetable messages</h2><ul>';

foreach($urls as $url) {
    if($url['tonight'] == 1) {
        echo '<li>' . $url['name'] . $separator;

        $tonightTime = explode(':', $url['tonightTime']);

        $tonightTime[0] = intval($tonightTime[0]);
        $tonightTime[1] = intval($tonightTime[1]);

        $beginTime = mktime((($tonightTime[1] == 0) ? ($tonightTime[0] - 1) : $tonightTime[0]), (($tonightTime[1] < 1) ? '55' : ($tonightTime[1] - 2)), 0, date('m'), date("d") , date("Y"));
        $endTime = mktime($tonightTime[0], ($tonightTime[1] + 2), 0, date('m'), date("d") , date("Y"));

        $dateBeginTime = getdate($beginTime);
        $dateEndTime = getdate($endTime);

        $dateBeginClass = gTimeLeftBeginClass(0, $url);

        echo $url['tonightTime'] . ' (' . $dateBeginTime['hours'] . ':' . (($dateBeginTime['minutes'] < 10) ? '0' : '') . $dateBeginTime['minutes'] . $separator . $dateEndTime['hours'] . ':' . (($dateBeginTime['minutes'] < 10) ? '0' : '') . $dateEndTime['minutes'] . ')' . $separator . $dateBeginClass;

        if($dateBeginClass != 'NONE' && $dateBeginClass < ($url['beforeBeginTime'] + 60) && $dateBeginClass > ($url['beforeBeginTime'] - 60) && $url['beforeBegin'] == 1) {
            sendMessage($url['url'], 'Salut Salut ! Il faut maintenant se lever car les cours commencent.');
            sAlreadyDone($url['id'], $dateBeginTime, 'BEFORE_BEGIN');
        }

        if(time() > $beginTime && time() < $endTime) {
            if(count($classesTomorrow) > 0)  {
                if(alreadyDone($url['id'], $classesTomorrow[0]['id'], 'TONIGHT') < 1) {
                    $message = 'Votre emploi du temps pour demain :' . "\r\n";

                    foreach ($classesTomorrow as $class) {
                        if($url['semestre'] == $class['semestre'] && ($url['groupTD'] == $class['groupTD'] || $class['groupTD'] == 'NONE') && ($url['groupTP'] == $class['groupTP'] || $class['groupTP'] == 'NONE') && $url['tonight'] == 1) {
                            $newMessage = 'Un ' . str_replace("\r\n", "", $class['typeClass']) . 'à ' . explode(' ', $class['dateClass'])[1] . ' de ' . $class['timeClass'] . ' est prévu à ' . $class['timeBegin'] . ' en ' . (($class['groupTD'] == 'NONE') ? 'classe entière' : 'groupe') . ' à la salle ' . $class['room'] . '. Le professeur sera ' . $class['teacher'] . ' et le module enseigné est ' . str_replace("\r\n", '', str_replace(' ', '', $class['moduleClass'])) . '.' . "\r\n";
                            echo '<br> - ' . $class['semestre'] . ' - ' . $class['groupTD'] . ' - ' . $class['groupTP'] . ' - ' . $newMessage;

                            $message .=  $newMessage;
                        }
                    }

                    sendMessage($url['url'], $message);
                    sAlreadyDone($url['id'], $classesTomorrow[0]['id'], 'TONIGHT');

                    $iTonight++;
                }
                else {
                    echo $separator . 'already sent';
                }
            }
            else {
                echo $separator . 'no timetable tomorrow';
            }
        }
        else {
            echo $separator . ' not good time';
        }

        echo '</li>';
    }
}

echo '</ul>';

echo $iTonight . ' messages sent';

echo '<hr><h2>Before class messages</h2><ul>';

$countClassToday = 0;

foreach ($classesToday as $class) {
    $splitTimeBegin = explode(':', explode(' ', $class['dateClass'])[1]);
    $timeBeginClass = mktime($splitTimeBegin[0], $splitTimeBegin[1], 0, date('m'), date('d') , date('Y'));

    $remainingTime = $timeBeginClass - time();
    $remainingTime = ($remainingTime > 0) ? $remainingTime : 'DONE';

    if($remainingTime != 'DONE') {
        $message =  (isClassProblem($class['id'], 'DUPLICATE') ? 'DUPLICATION - ' : '') . 'Vous avez ' . $class['timeClass'] . ' de ' . str_replace("\r\n", "", $class['typeClass']) . ' en ' . $class['room'] . ' avec M./Mme. ' . $class['teacher'] . ' .  dans ' . ceil($remainingTime / 60) . 'min, le module enseigné sera ' . str_replace(' ', '', $class['moduleClass']) . '.';

        echo '<li>' . $remainingTime . $separator . $class['semestre'] . $separator . $class['groupTD'] . $separator . $class['groupTP'] . $separator . $message;

        foreach($urls as $url) {
            if(alreadyDone($url['id'], $class['id'], 'BEFORE_CLASS') < 1 && $remainingTime < ($url['beforeClassTime'] + 60) && $remainingTime > ($url['beforeClassTime'] - 60) && $url['semestre'] == $class['semestre'] && ($url['groupTD'] == $class['groupTD'] || $class['groupTD'] == 'NONE') && ($url['groupTP'] == $class['groupTP'] || $class['groupTP'] == 'NONE') && $url['beforeClass'] == 1) {
                echo '<br>' . $url['name'];

                sendMessage($url['url'], $message);
                sAlreadyDone($url['id'], $class['id'], 'BEFORE_CLASS');

                $iBeforeClass++;
            }
        }

        echo '</li>';

        $countClassToday++;
    }
}

echo '</ul>';

if($countClassToday < 1) {
    echo 'All classes are ended<br><br>';
}

echo $iBeforeClass . ' messages sent';

function parseHTML($html) {
    global $months;

    $tabDates = [];
    $tabTopPxDates = [];
    $tabFormation = [];

    $countClass = 0;

    foreach($html->find('div.edtjour') as $div) {
        $topPx = intval(str_replace('px' , '', explode(':', explode(';', $div->style)[2])[1]));
        $leftPx = intval(str_replace('px' , '', explode(':', explode(';', $div->style)[3])[1]));

        if($leftPx < 1) {
            $splitInfo = explode(' ', $div->plaintext);

            $date = substr($splitInfo[3], 0, 4) . '-' . $months[$splitInfo[2]] . '-' . $splitInfo[1];

            $tabDates[] = $date;
            $tabTopPxDates[] = $topPx;
            $tabFormation[] = substr($splitInfo[3], 10);
        }
    }

    $i = 0;
    $tabTimes = [];

    foreach(array_slice($html->find('div.edt3'), 0, 22) as $div) {
        $splitHour = explode(':', $div->plaintext);

        if($i > 1) {
            $tabTimes[] = ((count($splitHour) > 1) ? ($splitHour[0] . ':15') : (intval($splitHour[0]) - 1) . ':45');
        }

        $tabTimes[] = (count($splitHour) > 1) ? $div->plaintext : ($div->plaintext . ':00');

        $i++;
    }

    $i = 0;
    $tabBarTimes = [];

    foreach(array_slice($html->find('div.edthoraire'), 0, 22) as $div) {
        $topPx = intval(str_replace('px' , '', explode(':', explode(';', $div->style)[2])[1]));

        if($i > 1) {
            $tabBarTimes[] = $topPx - (($topPx - $tabBarTimes[count($tabBarTimes) - 1]) / 2);
        }

        $tabBarTimes[] = $topPx;

        $i++;
    }
    
    foreach($html->find('div.edt') as $div) {
        $topPx = intval(str_replace('px' , '', explode(':', explode(';', $div->style)[3])[1]));

        $leftPixel = intval(str_replace('px' , '', explode(':', explode(';', $div->style)[4])[1]));
        $leftWidthPixel = $leftPixel + intval(str_replace('px' , '', explode(':', explode(';', $div->style)[6])[1]));

        //DATE
        $dateClass = 'NONE';
        $formation = 'NONE';

        for($i = 0; $i < count($tabDates); $i++) {
            if(($tabTopPxDates[$i] - 10) <= $topPx && ($tabTopPxDates[$i] + 300) >= $topPx) {
                $formation = $tabFormation[$i];
                $dateClass = $tabDates[$i];
            }
        }

        //TIME
        $timeBegin = 0;
        $timeEnd = 0;

        for ($i = 0; $i < count($tabTimes); $i++)
        {
            if ($tabBarTimes[$i] >= ($leftPixel - 5) && $tabBarTimes[$i] <= ($leftPixel + 5))
                $timeBegin = $tabTimes[$i];

            if ($tabBarTimes[$i] >= ($leftWidthPixel - 5) && $tabBarTimes[$i] <= ($leftWidthPixel + 5))
                $timeEnd = $tabTimes[$i];
        }

        $splitTimeBegin = explode(':', $timeBegin);
        $timeBeginClass = mktime($splitTimeBegin[0], $splitTimeBegin[1], 0, date('m'), date('d') , date('Y'));

        $splitTimeEnd = explode(':', $timeEnd);
        $timeEndClass = mktime($splitTimeEnd[0], $splitTimeEnd[1], 0, date('m'), date('d'), date('Y'));

        $timeClass = gTime($timeEndClass - $timeBeginClass);

        //INFORMATIONS
        $child = $div->first_child();

        $teacher = $child->find('i')[0]->plaintext;

        if(is_null($teacher)) {
            $teacher = '(Aucun professeur)';
        }

        $module = $child->find('b')[0]->plaintext;
        $room = $child->find('b')[1]->plaintext;
        
        $info = explode(' ', $child->plaintext);
        
        $group = $info[0];
        $typeClass = $info[1];
        
        //GROUP & SEMESTRE
        $semestre = 'S0';

        $groupTD = 'NONE';
        $groupTP = 'NONE';

        if(substr($group, 0, 2) == 'LP') {
            $groupTD = 1;
            $groupTP = substr($group, strlen($group) - 1, 1);
        }
        else {
            $parseGroup = explode('-', $group);

            $semestre = substr($parseGroup[0], 0, 2);

            if(strlen($parseGroup[1]) > 0) {
                $groupTD = substr($parseGroup[1], 0, 1);

                if(strlen($parseGroup[1]) > 1) {
                    $groupTP = substr($parseGroup[1], 1, 2);
                }
            }
        }

        $splitDate = explode('-', $dateClass);

        addClassTimetable($formation, $typeClass, $module, $room, $teacher, $semestre, $groupTD, $groupTP, $timeClass, ($splitDate[0] . '-' . $splitDate[1] . '-' . $splitDate[2] . ' ' . $splitTimeBegin[0] . ':' . $splitTimeBegin[1] . ':00'));

        $countClass++;
    }

    return $countClass;
}

function sendMessage($url, $messageText) {
    $ch = curl_init($url . '&msg=' . rawurlencode($messageText));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    $output = curl_exec($ch);
}

function saveTimetable($daysToLoad = 0)
{
    if($daysToLoad == 0) {
        return parseHTML(file_get_html('http://iutsa.unice.fr/gpushow2/?dept=RT&interactive&filiere=Journee'));
    }
    else {
        $nextDayURL = "http://iutsa.unice.fr/gpushow2/?dept=RT&interactive&date=plus&filiere=RT1,RT1M,RT2,LPRT-RSFS";
        $html = '';

        unlink(dirname(__FILE__) . '/cookie.txt');

        for ($i = 0; $i<$daysToLoad ; $i++)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nextDayURL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt');
            curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');

            $html = curl_exec($ch);
            curl_close($ch);

            if(!$html){
                echo 'Failed to get the Day '. $i;
                echo $html;
                exit();
            }
        }

        return parseHTML(str_get_html($html));
    }
}

function gTime($seconds) {
    $minutes = $seconds / 60;

    return ((floor($minutes / 60) > 0) ? (floor($minutes / 60)) : '00') . ':' . ((($minutes % 60) > 0) ?  ($minutes % 60) : '00');
}

function gUrls() {
    global $bdd;

    $req = $bdd->prepare('SELECT iu3tnoty_urls.id AS id, iu3tnoty_urls.name AS name, iu3tnoty_urls.url AS url, iu3tnoty_properties.semestre AS semestre, iu3tnoty_properties.groupTD AS groupTD, iu3tnoty_properties.groupTP AS groupTP, iu3tnoty_properties.tonight AS tonight, iu3tnoty_properties.tonightTime AS tonightTime, iu3tnoty_properties.beforeBegin AS beforeBegin, iu3tnoty_properties.beforeBeginTime AS beforeBeginTime, iu3tnoty_properties.beforeClass AS beforeClass, iu3tnoty_properties.beforeClassTime AS beforeClassTime FROM iu3tnoty_urls, iu3tnoty_properties WHERE iu3tnoty_properties.urlId = iu3tnoty_urls.id');
    $req->execute();

    return $req->fetchAll();
}

function alreadyDone($urlId, $timetableId, $type) {
    global $bdd;

    $date = getdate(time());

    $req = $bdd->prepare('SELECT COUNT(*) AS countUrl FROM iu3tnoty_done WHERE urlId = ? AND timetableId = ? AND typeNoty = ? AND date > "' . $date['year'] . '-' . (($date['mon'] < 10) ? '0' : '') . $date['mon'] . '-' . (($date['mday'] < 10) ? '0' : '') . $date['mday'] . ' 00:00:00"');
    $req->execute(array($urlId, $timetableId, $type));

    return $req->fetch()['countUrl'];
}

function sAlreadyDone($urlId, $timetimeId, $type) {
    global $bdd;

    $req = $bdd->prepare('INSERT INTO iu3tnoty_done(urlId, timetableId, typeNoty) VALUES (?, ?, ?)');

    return $req->execute(array($urlId, $timetimeId, $type));
}

function addClassTimetable($formation, $typeClass, $module, $room, $teacher, $semestre, $groupTD, $groupTP, $timeClass, $dateClass) {
    global $bdd;

    $text = '<br>';

    $req = $bdd->prepare('SELECT COUNT(*) AS countClass, room AS lastRoom, teacher AS lastTeacher FROM iu3tnoty_timetable WHERE semestre = ? AND groupTD = ? AND groupTP = ? AND dateClass = ?');
    $req->execute(array($semestre, $groupTD, $groupTP, $dateClass));

    $returnExist = $req->fetch();

    $text .= $returnExist['countClass'] . ' - ' . $dateClass . ' - ' . $groupTD . ' - ' . $groupTP . ' - ' . $room . ' - ' . $returnExist['lastRoom'] . ' - ' . $teacher . ' - ' . $returnExist['lastTeacher'] . ' - ';

    if($returnExist['countClass'] > 0 && $returnExist['countClass'] < 2 && ($room != $returnExist['lastRoom'] || $teacher != $returnExist['lastTeacher'])) {

        $text .= 'add to bdd but duplicate';

        $req = $bdd->prepare('INSERT INTO iu3tnoty_timetable(formation, typeClass, moduleClass, room, teacher, semestre, groupTD, groupTP, timeClass, dateClass) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        $req->execute(array($formation, $typeClass, $module, $room, $teacher, $semestre, $groupTD, $groupTP, $timeClass, $dateClass));

        addProblemTimetable($bdd->lastInsertId(), "DUPLICATE");
    }
    else if($returnExist['countClass'] <= 0) {

        $text .= 'add to bdd';

        $req = $bdd->prepare('INSERT INTO iu3tnoty_timetable(formation, typeClass, moduleClass, room, teacher, semestre, groupTD, groupTP, timeClass, dateClass) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        $req->execute(array($formation, $typeClass, $module, $room, $teacher, $semestre, $groupTD, $groupTP, $timeClass, $dateClass));
    }
    else {
        $text .= 'nothing to add';
    }

    //echo $text;
}

function addProblemTimetable($timetableId, $typeProblem) {
    global $bdd;

    $req = $bdd->prepare('INSERT INTO iu3tnoty_problem(classId, typeProblem) VALUES (?, ?)');

    return $req->execute(array($timetableId, $typeProblem));
}

function gTimetable($moreDay) {
    $date = getdate(time() + ($moreDay * 24 * 3600));
    $finalDate = $date['year'] . '-' . (($date['mon'] < 10) ? '0' : '') . $date['mon'] . '-' . (($date['mday'] < 10) ? '0' : '') . $date['mday'] . ' ';

    global $bdd;

    $req = $bdd->prepare('SELECT * FROM iu3tnoty_timetable WHERE dateClass > ? AND dateClass < ?');
    $req->execute(array(
        $finalDate . '00:00:00',
        $finalDate . '23:00:00'
    ));

    return $req->fetchAll();
}

function isClassProblem($classId, $typeProblem) {
    global $bdd;

    $req = $bdd->prepare('SELECT COUNT(*) AS countProblem FROM iu3tnoty_problem WHERE classId = ? AND typeProblem = ?');
    $req->execute(array($classId, $typeProblem));

    return $req->fetch()['countProblem'] > 0;
}

function gTimeLeftBeginClass($moreDay, $url) {
    $done = false;
    $dateClass = 'NONE';

    foreach(gTimetable($moreDay) as $class) {
        if(!$done && $url['semestre'] == $class['semestre'] && ($url['groupTD'] == $class['groupTD'] || $class['groupTD'] == 'NONE') && ($url['groupTP'] == $class['groupTP'] || $class['groupTP'] == 'NONE')) {
            $dateClass = $class['dateClass'];
            $done = true;
        }
    }

    if($dateClass != 'NONE') {
        $dateClassBegin = explode('-', explode(' ', $dateClass)[0]);
        $timeClassBegin = explode(':', explode(' ', $dateClass)[1]);

        $timestampClass = mktime($timeClassBegin[0], $timeClassBegin[1], $timeClassBegin[2], $dateClassBegin[1], $dateClassBegin[2], $dateClassBegin[0]);

        $dateClass = $timestampClass - time();
        $dateClass = ($dateClass > 0) ? $dateClass : 'NONE';
    }

    return $dateClass;
}