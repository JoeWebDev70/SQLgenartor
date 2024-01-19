<?php

    session_start();

    $errorMessage = null;
    $successMessage = null;
    $result = null;
    $script = null;

    if (isset($_SESSION["error"]) && !empty($_SESSION["error"])) {
        $errorMessage = $_SESSION["error"];
        unset($_SESSION["error"]);
    }

    if (isset($_SESSION["result"]["html"]) && !empty($_SESSION["result"]["html"])
    && isset($_SESSION["result"]["script"]) && !empty($_SESSION["result"]["script"])) {
        $result = $_SESSION["result"]["html"];
        $script = $_SESSION["result"]["script"];
        unset($_SESSION["result"]);
    }

    if (isset($_SESSION["success"]) && !empty($_SESSION["success"])) {
        $successMessage = $_SESSION["success"];
        unset($_SESSION["success"]);
    }

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css">
    <!-- preload font -->
    <link rel="preload" as="font" href="./ressources/fonts/nunito_sans_400.woff2" type="font/woff2" crossorigin="anonymous">
    <link rel="preload" as="font" href="./ressources/fonts/nunito_sans_700.woff2" type="font/woff2" crossorigin="anonymous">
    <title>Générateur de Script SQL</title>
</head>

<body>
    <main>
        <div class="component">
            <div>
                <h2>Générer votre Base de Données</h2>
                <div class="download">
                    <p>
                        Télécharger ici : le
                        <a class="cta" href="./ressources/csv/template.csv" download="template.csv">Modèle</a>
                        vierge, les
                        <a class="cta" href="./ressources/instructions.txt" download="instructions.txt">Instructions</a>
                        ainsi qu'un
                        <a class="cta" href="./ressources/csv/sample.csv" download="exemple.csv">Exemple</a>
                        .
                    </p>
                </div>
                <p>Sélectionner le fichier CSV pour générer le Script de votre Base de Données SQL</p>
            </div>
            <form action="./treatment/main.php" method="post" enctype="multipart/form-data">
                <div class="formRow">
                    <label for="csvFile">Sélectionnez un fichier CSV</label>
                    <input type="file" name="csvFile" id="csvFile" accept=".csv">
                </div>
                <div class="formRow errorDiv">
                    <?php if (!is_null($errorMessage)) { ?>
                        <p class="errorOnForm"> <?php echo $errorMessage; ?> </p>
                    <?php $errorMessage = null;}  ?>
                    <?php if (!is_null($successMessage)) { ?>
                        <p class="success"> <?php echo $successMessage; ?> </p>
                    <?php $successMessage = null;}  ?>
                </div>
                <button type="submit">Valider</button>
            </form>
        </div>

        <?php if (!is_null($result)) { ?>
            <div class="component">
                <div>
                    <p>
                        Voici votre script SQL, si vous n'avez pas de modification à faire, vous pouvez vous connecter à votre à MySQL en Localhost en remplissant le formulaire ci-dessous. 
                    </p>
                    <p>
                        Si vous souhaitez modifier le script, vous pouvez le copier en cliquant sur le bouton prévu à cet effet.
                    </p>
                    <form action="./treatment/connection.php" method="post">
                        <div class="connection">
                            <label for="user">User</label>
                            <input type="text" id="user" name="user">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password">
                            <label for="port">Port</label>
                            <input type="text" id="port" name="port" class="port" value="3306" pattern="\d+">
                            <input type="hidden" name="script" value="<?php echo $script; ?>">
                        </div>
                        <button type="submit">Connection</button>
                    </form>
                </div>
                <div class="scriptContainer">
                    <button id="copyButton" class="copy">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16">
                            <path d="M8.5 0a.5.5 0 0 0-.5.5V2H2a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V8.5a.5.5 0 0 0-1 0V15a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h6V.5a.5.5 0 0 0 .5.5z" />
                            <path d="M11.5 2a.5.5 0 0 1 .5.5V15a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5V2a.5.5 0 0 1 .5-.5h6V2z" />
                        </svg>
                    </button>
                    <div id="sqlScript">
                        <p class="script"><?php echo $result; ?></p>
                    </div>
                </div>
            </div>
        <?php $result = null; $script = null;}  ?>

    </main>
    <script src="./script.js"></script>
</body>

</html>