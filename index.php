<?php
include 'php/main.php';
    
if(is_null($_SESSION['userId'])) {
    header('Location: ../../index.php');
}
else {
    if(!isSubscribingProject(1))
        header('Location: ../../index.php');
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>IU3TNoty</title>

        <!-- Bootstrap -->
      <link href="../../css/bootstrap.min.css" rel="stylesheet">
      <link href="../../css/style.css" rel="stylesheet">
      <link rel="stylesheet" type="text/css" href="css/jquery.datetimepicker.css"/ >
      <link href="../../css/animate.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
      
    <!-- Fixed navbar -->
    <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">IU3TNoty</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="../../index.php">Retour DGIProject</a></li>
          </ul>
          <ul class="nav navbar-nav pull-right">
              <?php 
                if (!is_null($_SESSION['userId']))
                {
                  echo '<p class="navbar-text">Connecté en tant que ' . gUsername() . ' <a href="../../logout.php"><span class="glyphicon glyphicon-off"></span></a></p>';
                }
                else {
                    echo '<a class="navbar-btn btn btn-info" href="register.php" role="button">Inscription</a>  ';
                    echo '<a class="navbar-btn btn btn-info" href="login.php" role="button">Connexion</a>';
                }
              ?>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>

    <!-- Begin page content -->
    <div class="container">
        <div class="panel panel-primary">
            <div class="panel-heading">Vos notifications</div>
                <div class="panel-body">
                    <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Nom</th>
                      <th>Url</th>
                      <th>Semestre</th>
                      <th>Groupe</th>
                      <th>Sous groupe</th>
                      <th>Options</th>
                      <th>Opérations</th>
                    </tr>
                  </thead>
                  <tbody>
                        <?php
                        foreach (gUrls() as $url)
                        {?>
                        <tr>
                            <th scope="row"><?php echo $url['id']; ?></th>
                          <td><?php echo $url['name'] ?></td>
                          <td><?php echo $url['url']?></td>
                          <td><?php echo $url['semestre'] ?></td>
                          <td><?php  echo $url['groupTD']?></td>
                          <td><?php echo $url['groupTP'] ?></td>
                          <td>
                              <input type="checkbox" <?php echo ($url['beforeClass'] == 1)? 'checked' : ''; ?> disabled> Avant le cours <?php echo $url['beforeClassTime']/60?>min<br>
                              <input type="checkbox" <?php echo ($url['beforeBegin'] == 1)? 'checked' : ''; ?> disabled class="disabled" name="beforeBegin"/> Reveil matin <?php echo $url['beforeBeginTime']/60?>min
                              <br>
                              <input type="checkbox" <?php echo ($url['tonight'] == 1)? 'checked' : ''; ?> disabled class="disabled" name="toNight"/> Le soir à <?php echo $url['tonightTime']?>

                          </td>
                          <td><button type="button" onclick="deleteUrl(<?php echo $url['id'] ?>);" id="buttonDelete<?php echo $url['id'] ; ?>" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></button></td>
                        </tr>
                       <?php }
                        ?>
                    <tr>
                        <td></td>
                        <td><input type="text" class="form-control" name="Name" placeHolder="Nom" id="nameNoty"/></td>
                        <td><input type="text" class="form-control" name="url" placeHolder="Url de notification" id="urlNoty"/></td>
                        <td><select class="form-control" name="sem" id="semestreNoty">
                            <option val="S1">S1</option>
                            <option val="S2">S2</option>
                            <option val="S3">S3</option>
                            <option val="S4">S4</option>
                        </select></td>
                        <td><input type="number" min="0" class="form-control" value=1 name="grp" id="groupNoty"/></td>
                        <td><select class="form-control" name="ssGrp" id="underGroupNoty"/>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        </select></td>
                        <td><input type="checkbox" class="" name="beforeClass" id="beforeClassNoty"/> Avant le cours* <input id="beforeClassTime" class="form-control " type="text" disabled size="4" maxlength="7"> <br>
                            <input type="checkbox" class="" name="beforeClass" id="beforeBegin"/> Reveil Matin*  <input id="beforeBeginTime" class="form-control " type="text" size="4" disabled maxlength="7"><br>
                          <input type="checkbox" class="" name="toNight" id="toNightNoty"/> Le soir* a : <input id="toNightTime" class="form-control " type="text" size="4" disabled maxlength="7"></td>
                          <td><button id="buttonUrl" type="button" onclick="addUrl()" class="btn btn-success">Ajouter</button></td>
                    </tr>
                  </tbody>
                  </table>
                    <span class="text-muted">*Reveil Matin : Programme un SMS de notification x minutes avant le premier cours de la journée <br></span>
                  <span class="text-muted">*Avant le cours : une notification vous est envoyé vous précisant la salle et d'autres informations.</span><br>
                  <span class="text-muted">*Le soir : A partir de l'heure choisie, vous recevrez l'emploi du temps du lendemain.</span><br>
                  <span class="text-muted">La variable "msg" est renvoyée en GET dans l'url. Exemple : http://monsite.com/page?id=15454(&msg=LE_MESSAGE).</span>
                 </<div>
            </div>
        </div>
    </div>

    <footer class="footer">
      <div class="container">
        <p class="text-muted text-center">Villena Guillaume, Delaporte Dylan - DGIProject  - 2015 </p>
      </div>
    </footer>
    
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="js/jquery.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../../js/bootstrap.min.js"></script>
    <script src="js/jquery.datetimepicker.js"></script>
    <script type="text/javascript" src="../../js/jquery.noty.packaged.min.js"></script>
    <script type="text/javascript" src="js/main.js"></script>
  </body>
</html>