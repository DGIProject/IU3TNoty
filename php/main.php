<?php 
include_once('bdd_connect.php');

if($_POST['f'] == 'addUrl') {
    $req = $bdd->prepare('INSERT INTO iu3tnoty_urls(userId, name, url) VALUES (?, ?, ?)');
    
    if($req->execute(array($_SESSION['userId'], $_POST['name'], $_POST['url']))) {
        $urlId = $bdd->lastInsertId();
        
        $req = $bdd->prepare('INSERT INTO iu3tnoty_properties(urlId, semestre, groupTD, groupTP, tonight, tonightTime, beforeBegin, beforeBeginTime, beforeClass, beforeClassTime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if($req->execute(array($urlId, $_POST['semestre'], $_POST['groupTD'], $_POST['groupTP'], $_POST['tonight'], $_POST['tonightTime'], $_POST['beforeBegin'], $_POST['beforeBeginTime'], $_POST['beforeClass'], $_POST['beforeClassTime']))) {
            echo 'true';
        }
        else {
            echo 'false' . $urlId . ' - ' . $_POST['semestre'] . ' - ' . $_POST['groupTD'] . ' - ' . $_POST['groupTP'] . ' - ' . $_POST['tonight'];
        }
    }
    else {
        echo 'false';
    }
}
else if($_POST['f'] == 'deleteUrl') {
    $req = $bdd->prepare('SELECT COUNT(*) AS countUrl FROM iu3tnoty_urls WHERE userId = ? AND id = ?');
    $req->execute(array($_SESSION['userId'], $_POST['urlId']));
    
    if($req->fetch()['countUrl'] > 0) {
        $req = $bdd->prepare('DELETE FROM iu3tnoty_urls WHERE id = ?');
    
        if($req->execute(array($_POST['urlId']))) {
            $req = $bdd->prepare('DELETE FROM iu3tnoty_properties WHERE urlId = ?');
    
            if($req->execute(array($_POST['urlId']))) {
                echo 'true';
            }
            else {
                echo 'false';
            }
        }
        else {
            echo 'false';
        }
    }
    else {
        echo 'false';
    }
}

function gUsername() {
    global $bdd;
    
    $req = $bdd->prepare('SELECT username FROM users WHERE id = ?');
    $req->execute(array($_SESSION['userId']));
    
    return $req->fetch()['username'];
}

function isSubscribingProject($projectId) {

    global $bdd;
    
    $req = $bdd->prepare('SELECT COUNT(*) AS countSubscribe FROM subscribeProject  WHERE userId = ? AND projectId = ?');
    $req->execute(array($_SESSION['userId'], $projectId));

    return ($req->fetch()['countSubscribe'] > 0);
}

function gUrls() {
    global $bdd;
    
    $req = $bdd->prepare('SELECT iu3tnoty_urls.id AS id, iu3tnoty_urls.name AS name, iu3tnoty_urls.url AS url, iu3tnoty_properties.semestre AS semestre, iu3tnoty_properties.groupTD AS groupTD, iu3tnoty_properties.groupTP AS groupTP, iu3tnoty_properties.tonight AS tonight, iu3tnoty_properties.beforeClass AS beforeClass, iu3tnoty_properties.tonightTime AS tonightTime, iu3tnoty_properties.beforeClassTime AS beforeClassTime, iu3tnoty_properties.beforeBeginTime AS beforeBeginTime, iu3tnoty_properties.beforeBegin AS beforeBegin FROM iu3tnoty_urls, iu3tnoty_properties WHERE iu3tnoty_urls.userId = ? AND iu3tnoty_properties.urlId = iu3tnoty_urls.id');
    $req->execute(array($_SESSION['userId']));
    
    return $req->fetchAll();
}