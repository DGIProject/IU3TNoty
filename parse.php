<?php
include 'bdd_connect.php';
include 'simple_html_dom.php';

//Remove Warnings
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('Europe/Paris');
echo date("d/m/y G:i:s");
echo time();

function gUrls() {
    global $bdd;
    
    $req = $bdd->prepare('SELECT iu3tnoty_urls.id AS id, iu3tnoty_urls.name AS name, iu3tnoty_urls.url AS url, iu3tnoty_properties.semestre AS semestre, iu3tnoty_properties.groupTD AS groupTD, iu3tnoty_properties.groupTP AS groupTP, iu3tnoty_properties.tonight AS tonight, iu3tnoty_properties.beforeClass AS beforeClass FROM iu3tnoty_urls, iu3tnoty_properties WHERE iu3tnoty_properties.urlId = iu3tnoty_urls.id');
    $req->execute();
    
    return $req->fetchAll();
}

$urls = gUrls();

$beginTime = mktime(20, 0, 0, date('m'), date("d") , date("Y"));
$endTime = mktime(21, 0, 0, date('m'), date("d") , date("Y"));

if(time() > $beginTime && time() < $endTime) {
    if(!file_exists( dirname(__FILE__) . '/alreadyDone.file')) {
        $contentHTML = getTimeTable(1);
        $classes = parseHTML($contentHTML);
        
        $message = 'Votre emploi du temps pour demain :' . "\r\n";
        
        $i = 0;
        
        foreach($urls as $url) {
            if($url['tonight'] == 1) {
                sendMessage($url['url'], $message);
                
                $i++;
            }
        }
        
        foreach ($classes as $class) {
            $message =  'Un ' . str_replace("\r\n", "", $class['typeClass']) . ' est prévu à ' . $class['time'] . ' en ' . (($class['groupTD'] == 'NONE') ? 'classe entière' : 'groupe') . ' à la salle "' . $class['room'] . '". Le professeur sera ' . $class['teacher'] . ' et le module enseigné est ' . str_replace("\r\n", '', str_replace(' ', '', $class['module'])) . '.' . "\r\n";
            
            foreach($urls as $url) {
                echo ' - ' . $url['semestre'] . ' - ' . $class['semestre'] . ' - ' . $url['groupTD'] . ' - ' . $class['groupTD'] . ' - ' . $url['groupTP'] . ' - ' . $class['groupTP'] . ' - ' . $message . '<br>';
                if($url['semestre'] == $class['semestre'] && ($url['groupTD'] == $class['groupTD'] || $class['groupTD'] == 'NONE') && ($url['groupTP'] == $class['groupTP'] || $class['groupTP'] == 'NONE') && $url['tonight'] == 1) {
                    sendMessage($url['url'], $message);
                    
                    $i++;
                }
            }
        }
        
        echo $i . ' messages sent';
        
        $myfile = fopen(dirname(__FILE__) . "/alreadyDone.file", "w") or die("Unable to open file!");
        fwrite($myfile, 'done=true');
        fclose($myfile);
    }
    else {
        echo 'alreadyDone';
    }
}
else {
    if(file_exists( dirname(__FILE__) . '/alreadyDone.file')) {
	unlink(dirname(__FILE__) . '/alreadyDone.file');
    }

    $contentHTML = getTimeTable(0);
    $classes = parseHTML($contentHTML);
    
    $i = 0;
    
    foreach ($classes as $class) {
        if($class['remainingTime'] != 'DONE') {
            $message =  'Prochain ' . str_replace("\r\n", "", $class['typeClass']) . ' dans ' . ($class['remainingTime'] / 60) . 'min en ' . $class['room'] . ' avec M./Mme. ' . $class['teacher'] . ', module enseigné sera ' . str_replace(' ', '', $class['module']) . '.';
        
            echo ' - ' . $class['remainingTime'] . ' - ' . $class['semestre'] . ' - ' . $class['groupTD'] . ' - ' . $class['groupTP'] . ' - ' . $message . '<br>';
            
            foreach($urls as $url) {
                if($class['remainingTime'] < ($url['beclassClassTime'] + 60) && $class['remainingTime'] > ($url['beforeClassTime'] - 60) $url['semestre'] == $class['semestre'] && ($url['groupTD'] == $class['groupTD'] || $class['groupTD'] == 'NONE') && ($url['groupTP'] == $class['groupTP'] || $class['groupTP'] == 'NONE') && $url['beforeClass'] == 1) {
                    sendMessage($url['url'], $message);
                    
                    $i++;
                }
            }
        }
    }
    
    echo $i . ' messages sent';
}

function parseHTML($html) {
    foreach(array_slice($html->find('div.edt3'), 0, 22) as $div) {
        $tabTimes[] = (sizeof(explode(':', $div->plaintext)) > 1) ? $div->plaintext : ($div->plaintext . ':00');
    }

    foreach(array_slice($html->find('div.edthoraire'), 0, 22) as $div) {
        $tabBarTimes[] = intval(str_replace('px' , '', explode(':', explode(';', $div->style)[2])[1]));
    }
    
    foreach($html->find('div.edt') as $div) {
        $leftPixel = intval(str_replace('px' , '', explode(':', explode(';', $div->style)[4])[1]));
        
        //TIME
        for ($i = 0; $i < sizeof($tabBarTimes); $i++)
        {
            if ($tabBarTimes[$i] == $leftPixel)
                $time = $tabTimes[$i];
        }
        
        $explodedTimeCours = explode(':', $time);
        $timeClass = mktime($explodedTimeCours[0], $explodedTimeCours[1], 0, date('m'), date("d") , date("Y"));
        $remainingTime = $timeClass - time();
        
        if (($timeClass - time()) > 0)
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
        
        $tabClasses[] = ['typeClass' => $typeClass, 'time' => $time, 'remainingTime' => $remainingTimeText, 'semestre' => $semestre, 'groupTD' => $groupTD, 'groupTP' => $groupTP, 'room' => $room, 'teacher' => $teacher, 'module' => $module];
    }
    
    return $tabClasses;
}

function sendMessage($url, $messageText) {
    echo $url . '&msg=' . rawurlencode($messageText);
    
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

print_r($tabTimes);