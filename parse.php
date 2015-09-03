<?php
include("simple_html_dom.php");

//Remove Warnings
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('Europe/Paris');
echo date("d m y G i s");

// Main vars
$urls = [];

/*
$contentHTML = file_get_html('http://iutsa.unice.fr/gpushow2/?dept=RT&interactive&date=plus&filiere=RT1,RT1M,RT2,LPRT-RSFS');

foreach (parseHTML($contentHTML) as $class) {
    echo 'Un ' . $class['typeClass'] . ' est prévu à ' . $class['time'] . ' en ' . $class['group'] . ' à la salle "' . $class['room'] . '". Le professeur sera ' . $class['teacher'] . ' et le module enseigné est ' . $class['module'] . '.<br>';
}
*/

$logFile = fopen(dirname(__FILE__) . "/logParse.log", "a") or die("Unable to open file!");
        fwrite($logFile, 'done=true');
        fclose($logFile);
        
$beginTime = mktime(19, 0, 0, date('m'), date("d") , date("Y"));
$endTime = mktime(20, 0, 0, date('m'), date("d") , date("Y"));

if(time() > $beginTime && time() < $endTime) {
    if(!file_exists( dirname(__FILE__) . '/alreadyDone.file')) {
        $contentHTML = getTimeTable(1);
        $classes = parseHTML($contentHTML);
        
        $message = 'Votre emploi du temps pour demain :' . "\r\n";
        
        foreach ($classes as $class) {
            if($class['group'] != 'other group')
                $message .=  'Un ' . str_replace("\r\n", "", $class['typeClass']) . ' est prévu à ' . $class['time'] . ' en ' . $class['group'] . ' à la salle "' . $class['room'] . '". Le professeur sera ' . $class['teacher'] . ' et le module enseigné est ' . str_replace("\r\n", '', str_replace(' ', '', $class['module'])) . '.' . "\r\n";
        }
        
        echo $message;
        
        $logFile = fopen(dirname(__FILE__) . "/logParse.log", "a") or die("Unable to open file!");
        fwrite($logFile, $message . "\n");
        fclose($logFile);
        
        sendMessages($message);
        
        $myfile = fopen(dirname(__FILE__) . "/alreadyDone.file", "w") or die("Unable to open file!");
        fwrite($myfile, 'done=true');
        fclose($myfile);
    }
    else {
        echo 'alreadyDone';
        
        unlink(dirname(__FILE__) . '/alreadyDone.file');
    }
}
else {
    $contentHTML = getTimeTable(0);
    $classes = parseHTML($contentHTML);
    
    foreach ($classes as $class) {
        $message =  'Un ' . str_replace("\r\n", "", $class['typeClass']) . ' est prévu à ' . $class['time'] . ' en ' . $class['group'] . ' à la salle "' . $class['room'] . '". Le professeur sera ' . $class['teacher'] . ' et le module enseigné est ' . str_replace(' ', '', $class['module']) . '.';
        
        echo $class['remainingTime'] . ' - ' . $message . '<br>';
        
        if($class['remainingTime'] < 10 && $class['remainingTime'] != 'DONE' && $class['group'] != 'other group') {
            sendMessages($message);
        }
        
        $logFile = fopen(dirname(__FILE__) . "/logParse.log", "a") or die("Unable to open file!");
        fwrite($logFile, $message . "\n");
        fclose($logFile);
    }
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
        $timeClass = mktime($explodedTimeCours[0], $explodedTimeCours[1],0, date('m'), date("d") , date("Y"));
        $remainingTime = getdate($timeClass - time())['minutes'];
        
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
        
        if(substr($parseGroup[0], 0, 2) == 'S1') {
            if(sizeof($parseGroup) < 2) {
                $group = 'classe entière';
            }
            else {
                if($parseGroup[1] == '1' || $parseGroup[1] == '1B') {
                    $group = 'groupe';
                }
                else {
                    $group = 'other group';
                }
            }
        
            if($group != 'other group') {
                //$smsText = 'Un ' . $typeClass . ' est prévu à ' . $time . ' en ' . $group . ' à la salle "' . $room . '". Le professeur sera ' . $teacher . ' et le module enseigné ' . $module . '.';
                //echo $smsText;
                
                if (($timeClass - time()) > 0)
                {
                    if (($timeClass - time()) <= 600)
                    {
                        $remainingTimeText = $remainingTime['minutes'];
                    }
                }
                else {
                    $remainingTimeText = 'DONE';
                }
    
            }
            
            $tabClasses[] = ['typeClass' => $typeClass, 'time' => $time, 'remainingTime' => $remainingTimeText, 'group' => $group, 'room' => $room, 'teacher' => $teacher, 'module' => $module];
        }
        else {
            //echo 'not good class';
        }
    }
    
    return $tabClasses;
}

function sendMessages($messageText) {
    global $urls;
    
    foreach($urls as $url) {
        echo $url . '&msg=' . rawurlencode($messageText);
        $ch = curl_init($url . '&msg=' . rawurlencode($messageText));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $output = curl_exec($ch);
    }
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