<?php
include 'bdd_connect.php';
include 'simple_html_dom.php';

//Remove Warnings
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('Europe/Paris');
echo '<h1>IU3TNoty - V1.0</h1>It\'s now : ' . date("d/m/y G:i:s") . '.<hr>';

$separator = ' - ';

$urls = gUrls();

$classesTomorrow = parseHTML(getTimeTable(1));
$classesToday = parseHTML(getTimeTable(0));

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

        echo $url['tonightTime'] . ' (' . $dateBeginTime['hours'] . ':' . (($dateBeginTime['minutes'] < 10) ? '0' : '') . $dateBeginTime['minutes'] . $separator . $dateEndTime['hours'] . ':' . (($dateBeginTime['minutes'] < 10) ? '0' : '') . $dateEndTime['minutes'] . ')';

        if(time() > $beginTime && time() < $endTime) {
            if(alreadyDone($url['id'], 'TONIGHT') < 1) {
                $message = 'Votre emploi du temps pour demain :' . "\r\n";

                foreach ($classesTomorrow as $class) {
                    if($url['semestre'] == $class['semestre'] && ($url['groupTD'] == $class['groupTD'] || $class['groupTD'] == 'NONE') && ($url['groupTP'] == $class['groupTP'] || $class['groupTP'] == 'NONE') && $url['tonight'] == 1) {
                        $newMessage = 'Un ' . str_replace("\r\n", "", $class['typeClass']) . ' de ' . gTime($class['timeClass']) . ' est prévu à ' . $class['timeBegin'] . ' en ' . (($class['groupTD'] == 'NONE') ? 'classe entière' : 'groupe') . ' à la salle ' . $class['room'] . '. Le professeur sera ' . $class['teacher'] . ' et le module enseigné est ' . str_replace("\r\n", '', str_replace(' ', '', $class['module'])) . '.' . "\r\n";
                        echo '<br> - ' . $class['semestre'] . ' - ' . $class['groupTD'] . ' - ' . $class['groupTP'] . ' - ' . $newMessage;

                        $message .=  $newMessage;
                    }
                }

                sendMessage($url['url'], $message);
                sAlreadyDone($url['id'], 'TONIGHT');

                $iTonight++;
            }
            else {
                echo $separator . 'already sent';
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
    if($class['remainingTime'] != 'DONE') {
        $message =  'Prochain ' . str_replace("\r\n", "", $class['typeClass']) . ' de ' . gTime($class['timeClass']) . ' dans ' . ceil($class['remainingTime'] / 60) . 'min en ' . $class['room'] . ' avec M./Mme. ' . $class['teacher'] . ', module enseigné sera ' . str_replace(' ', '', $class['module']) . '.';

        echo '<li>' . $class['remainingTime'] . $separator . $class['semestre'] . $separator . $class['groupTD'] . $separator . $class['groupTP'] . $separator . $message;

        foreach($urls as $url) {
            if(alreadyDone($url['id'], 'BEFORE_CLASS') < 1 && $class['remainingTime'] < ($url['beforeClassTime'] + 60) && $class['remainingTime'] > ($url['beforeClassTime'] - 60) && $url['semestre'] == $class['semestre'] && ($url['groupTD'] == $class['groupTD'] || $class['groupTD'] == 'NONE') && ($url['groupTP'] == $class['groupTP'] || $class['groupTP'] == 'NONE') && $url['beforeClass'] == 1) {
                echo '<br>' . $url['name'];

                sendMessage($url['url'], $message);
                sAlreadyDone($url['id'], 'BEFORE_CLASS');

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
    $tabClasses = [];

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
        $leftPixel = intval(str_replace('px' , '', explode(':', explode(';', $div->style)[4])[1]));
        $leftWidthPixel = $leftPixel + intval(str_replace('px' , '', explode(':', explode(';', $div->style)[6])[1]));

        $timeBegin = 0;
        $timeEnd = 0;
        
        //TIME
        for ($i = 0; $i < count($tabBarTimes); $i++)
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

        $remainingTime = $timeBeginClass - time();
        $timeClass = $timeEndClass - $timeBeginClass;
        
        if ($remainingTime > 0)
        {
            $remainingTimeText = $remainingTime;
        }
        else {
            $remainingTimeText = 'DONE';
        }
        
        //INFORMATIONS
        $child = $div->first_child();
        
        $teacher = $child->find('i')[0]->plaintext;
        $module = $child->find('b')[0]->plaintext;
        $room = $child->find('b')[1]->plaintext;
        
        $info = explode(' ', $child->plaintext);
        
        $group = $info[0];
        $typeClass = $info[1];
        
        //GROUP
        $parseGroup = explode('-', $group);
        
        $semestre = substr($parseGroup[0], 0, 2);
        
        $groupTD = 'NONE';
        $groupTP = 'NONE';
        
        if(strlen($parseGroup[1]) > 0) {
            $groupTD = substr($parseGroup[1], 0, 1);
            
            if(strlen($parseGroup[1]) > 1) {
                $groupTP = substr($parseGroup[1], 1, 2);
            }
        }
        
        $tabClasses[] = ['typeClass' => $typeClass, 'timeBegin' => $timeBegin, 'timeClass' => ($timeClass / 60), 'remainingTime' => $remainingTimeText, 'semestre' => $semestre, 'groupTD' => $groupTD, 'groupTP' => $groupTP, 'room' => $room, 'teacher' => $teacher, 'module' => $module];
    }
    
    return $tabClasses;
}

function sendMessage($url, $messageText) {
    $ch = curl_init($url . '&msg=' . rawurlencode($messageText));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    $output = curl_exec($ch);
}

function getTimeTable($daysToLoad = 0)
{
    if($daysToLoad == 0) {
        return file_get_html('http://iutsa.unice.fr/gpushow2/?dept=RT&interactive&filiere=Journee');
    }
    else {

    // Create the initial link you want.
    $nextDayURL = "http://iutsa.unice.fr/gpushow2/?dept=RT&interactive&date=plus&filiere=RT1,RT1M,RT2,LPRT-RSFS";

    
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
            echo 'Faild to get the Day i+'. $i;
        exit();
        }

    }
    
    return str_get_html($html);
    }
}

function gTime($minutes) {
    return ((floor($minutes / 60) > 0) ? ((floor($minutes / 60) . ' heure' . ((floor($minutes / 60) > 1) ? 's' : ''))) : '') . ((($minutes % 60) > 0) ?  (' ' . ($minutes % 60) . ' minutes ') : '');
}

function gUrls() {
    global $bdd;

    $req = $bdd->prepare('SELECT iu3tnoty_urls.id AS id, iu3tnoty_urls.name AS name, iu3tnoty_urls.url AS url, iu3tnoty_properties.semestre AS semestre, iu3tnoty_properties.groupTD AS groupTD, iu3tnoty_properties.groupTP AS groupTP, iu3tnoty_properties.tonight AS tonight, iu3tnoty_properties.tonightTime AS tonightTime, iu3tnoty_properties.beforeClass AS beforeClass, iu3tnoty_properties.beforeClassTime AS beforeClassTime FROM iu3tnoty_urls, iu3tnoty_properties WHERE iu3tnoty_properties.urlId = iu3tnoty_urls.id');
    $req->execute();

    return $req->fetchAll();
}

function alreadyDone($urlId, $type) {
    global $bdd;

    $date = getdate(time());

    $req = $bdd->prepare('SELECT COUNT(*) AS countUrl FROM iu3tnoty_done WHERE urlId = ? AND typeNoty = ? AND date > "' . $date['year'] . '-' . (($date['mon'] < 10) ? '0' : '') . $date['mon'] . '-' . (($date['mday'] < 10) ? '0' : '') . $date['mday'] . ' 00:00:00"');
    $req->execute(array($urlId, $type));

    return $req->fetch()['countUrl'];
}

function sAlreadyDone($urlId, $type) {
    global $bdd;

    $req = $bdd->prepare('INSERT INTO iu3tnoty_done(urlId, typeNoty) VALUES (? , ?)');

    return $req->execute(array($urlId, $type));
}
