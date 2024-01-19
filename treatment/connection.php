<?php

    session_start();

    //variables declaration
    $linkPage = '../index.php';
    $conn = null;
    $dsn = null;
    $host = "localhost";
    $user = null;
    $password = null;
    $port = null;
    $script = null;
    $errorMessage = null;

    if(isset($_POST["user"]) && !empty($_POST["user"]) 
        && isset($_POST["password"]) && !empty($_POST["password"])
        && isset($_POST["port"]) && !empty($_POST["port"])
        && isset($_POST["script"]) && !empty($_POST["script"]))
    {
        $user = $_POST["user"];
        $password = $_POST["password"];
        $port = $_POST["port"];
        $script = $_POST["script"];
    }

    if($user != null && $password != null && $port != null && $script != null){
        $dsn = 'mysql:host='.$host.';port='.$port.';charset=utf8';
        try {
            $conn = new PDO($dsn, $user, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            try{
                $sth = $conn->prepare($script);
                if ($sth->execute()) {
                    $_SESSION["success"] = "La base de données a été créée avec succès.";
                } else {
                    $_SESSION["error"] = "La base de données n'a pas été créée.";
                }
            }catch (PDOException $e) {
                $errorMessage = "Erreur lors de l'exécution du script SQL : <br> " . $e->getMessage();
            }
        } catch(PDOException $e) {
            $errorMessage = "Erreur lors de la connexion à votre base de données : <br> ". $e->getMessage();
        }
    }else{
        $errorMessage = "Erreur lors de la connexion à votre base de données <br> ";
    }

    if($errorMessage != null){ 
        $_SESSION["error"] = $errorMessage;
    }

    header('location:'. $linkPage); //return to principal page

?>