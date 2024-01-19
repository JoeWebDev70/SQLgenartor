<?php
    //FILE VALIDATION Part 2

    /**
     * Gets the content of the uploaded file.
     *
     * @param string $uploadedFile: The path of the uploaded file.
     *
     * @return array 
     *  - success = [true, file content] 
     *  - failure = [false, error message] 
     */
    function getFileContent($uploadedFile){
        $fileContent = file_get_contents($uploadedFile);
        if($fileContent !== false && strlen($fileContent) > 0){
            return [true, $fileContent];
        }else{
            return [false, "Erreur lors de la lecture du Fichier !"];
        } 
    }

    /**
     * Checks the encoding of the file content and encodes it to UTF-8 if it's necessary.
     *
     * @param string $fileContent: The content of the file.
     *
     * @return array 
     *  - success = [true, encoded file content] 
     *  - failure = [false, error message] 
     */
    function checkEncoding($fileContent) {

        $fileEncoding = mb_detect_encoding($fileContent); //check if detect utf8 encoding 
        if($fileEncoding !== false){
            
            if ($fileEncoding !== 'UTF-8') { // encoding is detected but not utf8
                
                // encode file in utf8
                $fileEncodedContent = mb_convert_encoding($fileContent, 'UTF-8', $fileEncoding); 
                if($fileEncodedContent === false) {
                    return [false, "Erreur d'encodage du fichier ! "];
                }
            }else{
                $fileEncodedContent = $fileContent;
            }

            $fileLines = explode(PHP_EOL, $fileEncodedContent); // Convert the file content to an array of lines
            return [true, $fileLines];

        }else{
            return [false, "Veuillez verifier l'encodage de votre fichier !"];
        }
    }

    /**
     * Gets the file with the correct encoding and returns it to be processed.
     *
     * @param string $uploadedFile The path of the uploaded file.
     *
     * @return array 
     *  - success = [true, encoded file content] 
     *  - failure = [false, error message] 
     */
    function checkEncodingFile($uploadedFile){

        $fileContentResult = getFileContent($uploadedFile);
        if($fileContentResult[0]){ //file content is get

            $encodingResult = checkEncoding($fileContentResult[1]);
            if($encodingResult[0]){ //file content is encoded
                return [true, $encodingResult[1]];
            }else{ 
                return $encodingResult; // = [false, "Error message"];
            }
        }else{
            return $fileContentResult;  // = [false, "Error message"];
        }      
    }

?>