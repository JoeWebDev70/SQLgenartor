<?php

    //FILE VALIDATION Part 1

    /**
     * Checks if the file was loaded correctly.
     *
     * @param array $file: The file to check.
     *
     * @return array 
     *  - success = [true, uploaded file (temp name, full path), original file name] 
     *  - failure = [false, error message] 
     */
    function checkLoading($file){
        if($file['error'] === UPLOAD_ERR_OK) {
            return [true, $file['tmp_name'], $file['name']];
        }else{
            return [false, "Erreur lors du téléchargement !"];
        }
    }
 
    /**
     * Checks if the file meets the size, extension, and readability requirements.
     *
     * @param string $uploadedFile: The temporary path of the uploaded file.
     * @param string $fileName: The name of the uploaded file.
     * @param int $maxSize: The max size authorizated.
     *
     * @return array 
     *  - success = [true] 
     *  - failure = [false, error message] 
     */
    function checkRequirementsFile($uploadedFile, $fileName, $maxSize){

        if(!file_exists($uploadedFile)){ // check if exist
            return [false, "Fichier inexistant !"];
        }

        if($_FILES['csvFile']['size'] >= $maxSize){ // check size  
            return [false, "Le Fichier dépasse la taille autorisée !"];
        }

        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== "csv") { // check extension
            return [false, "Le Fichier n'est pas un CSV !"];
        }

        if (!is_readable($uploadedFile)) { // check if readable
            return [false, "Le Fichier n'est pas lisible !"];
        }

        return [true];
    }

    /**
     * Checks the validity of the file.
     *
     * @param array $file: The file to check.
     * @param int $maxSize: The max size authorizated.
     *
     * @return array  
     *  - success = [true, uploaded file (temp name, full path), original file name] 
     *  - failure = [false, error message] 
     */
    function ckeckFileValidity($file, $maxSize){

        $loadingResult = checkLoading($file);
        if($loadingResult[0]){ //file loaded correctly
            
            $uploadedFile = $loadingResult[1]; 
            $fileName = $loadingResult[2]; 
            
            $requirementsResult = checkRequirementsFile($uploadedFile, $fileName, $maxSize);
            // file uploaded exist, had correct size and extension, is readable
            if($requirementsResult[0]){ 
                return [true, $uploadedFile, $fileName];
            }else{ // file uploaded have some problemes with requirements
                return $requirementsResult;  // = [false, "Error message"];
            }
        }else{ //file not loaded correctly
            return $loadingResult;  // = [false, "Error message"];
        }
    }
?>