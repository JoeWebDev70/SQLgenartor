<?php

    require_once "./fileValidations/01.fileValidity.php";
    require_once "./fileValidations/02.fileEncoding.php";
    require_once "./fileValidations/03.fileStructure.php";
    require_once "./fileValidations/04.fileAttributes.php";
    require_once "./fileValidations/05.fileValues.php";
    require_once "./fileProcessing/fileProcessing.php";

    session_start();

    //variables declaration
    $linkPage = '../index.php';
    $errorMessage = null;
    $htmlScript = null;
    $script = null;
    $maxSize = 1024 * 25; //25ko
    $marker = ","; 
    $headers = ["db", "table_name", "column_name", "data_type", "max_size", "primary_key", "not_null", "unique", "binary", "unsigned", "zero_fill", "auto_increment", "generated", "default_expression", "foreign_key", "references", "password_hash", "value"]; 
    $headersBool = ["primary_key", "not_null", "unique", "binary", "unsigned", "zero_fill", "auto_increment", "generated", "foreign_key", "password_hash"]; 
    
    if(isset($_FILES['csvFile']) && $_FILES['csvFile']['tmp_name'] !== ""){
        
        //file validation part 1: Loaded, exist, size, extension, readable
        $validityResult = ckeckFileValidity($_FILES['csvFile'], $maxSize); 
        if($validityResult[0]){
            
            $uploadedFile = $validityResult[1]; 
            $fileName = $validityResult[2]; 

            //file validation part 2: encoding
            $encodingFileResult = checkEncodingFile($uploadedFile); 
            if($encodingFileResult[0]){

                $fileContent = $encodingFileResult[1];

                //file validation part 3: check content = structure and get structured content
                $fileStructureResult = checkFileStructure($fileContent, $fileName, $marker, $headers); 
                if($fileStructureResult[0]){
                   
                    //file validation part 4: check content = Attributes 
                    $fileContentResult = checkFileAttributes($fileStructureResult[1], $headers, $headersBool); 
                    if($fileContentResult[0]){

                        //file validation part 5: check content = Values
                        $fileValuesResult = checkFileValues($fileContentResult[1], $headers); 
                        if($fileValuesResult[0]){

                            $processResult = processFile($fileValuesResult[1], $headers); //file processing
                            if($processResult[0]){  //file processed
                                $htmlScript = $processResult[1];
                                $script = $processResult[2];
                            }else{ //some error in processing file 
                                $errorMessage = $processResult[1];
                            }
                        }else{
                            $errorMessage = $fileValuesResult[1];
                        }
                    }else{
                        $errorMessage = $fileContentResult[1];
                    }
                }else{
                    $errorMessage = $fileStructureResult[1];
                }
            }else{
                $errorMessage = $encodingFileResult[1];
            }
        }else{
            $errorMessage = $validityResult[1];
        }
    }else{ // $_FILES is empty
        $errorMessage = "Veuillez sélectionner un fichier !";
    }

    //set response in session
    if($errorMessage != null){ 
        $_SESSION["error"] = $errorMessage;
    }else{
        $_SESSION["result"]["html"] = $htmlScript;
        $_SESSION["result"]["script"] = $script;
    }

    unset($fileContent); // Release the file
    header('location:'. $linkPage); //return to principal page


?>
