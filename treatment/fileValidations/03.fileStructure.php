<?php
    //FILE VALIDATION Part 3

    /**
     * Checks if the first line of the file contains the authorized separation marker.
     *
     * @param string $firstLine: The first line of the file.
     *
     * @return array
     *  - success = [true]
     *  - failure = [false, error message]
     */
    function checkAndGetMarker($firstLine, $marker){ 
        
        if($firstLine !== null && $firstLine !== ""){
            if (str_contains($firstLine, $marker) !== false) {
                return [true];
            }else{ //marker not found
                return [false, "Marqueur de séparation des données incorrects !"];
            }
        }else{
            return [false, "Première ligne vide !"];
        }
    }

    /**
     * Checks if all headers in the file are valid.
     *
     * @param array $fileContent: The file content.
     * @param string $marker: The separation marker.
     * @param array $headers: authorizated headers.
     *
     * @return array
     *  - success = [true, array of authorized headers]
     *  - failure = [false, error message]
     */
    function checkHeaders($fileContent, $marker, $headers){
        $headerDbName = $headers[array_search("db", $headers)];

        $header = null;
        $errorMsg = null;
        $lineNumber = 0; 

        foreach ($fileContent as $line) { //check all lines
            $lineData = str_getcsv($line, $marker);
                                        
            if ($lineData !== false) { 
                $lineNumber += 1;
                $header = $lineData[0];
                
                if(!in_array($header, $headers)){ //check if header is authorizated
                    if($header == ""){
                        $errorMsg .= " L".$lineNumber." : entête ou ligne vide ! <br>";
                    }else{
                        $errorMsg .= " L".$lineNumber." : entête " .$header." non autorisée ! <br>";
                    }
                }else{
                    if($header === $headerDbName && $lineNumber !== 1){ 
                        $errorMsg .= " L".$lineNumber." : entête " .$header." ne peut être utilisé que sur la première ligne !";
                    }
                }
            }              
        }

        if($errorMsg !== null){
            return [false, $errorMsg];
        }else{
            return [true];
        }

    }
  
    /**
     * Checks if the name is valid with pattern accepted by most DBMSs.
     *
     * @param string $name: The name to check.
     *
     * @return bool
     *  - success = true
     *  - failure = false
     */
    function checkNameValidity($name){ 
        $pattern ='/^[a-zA-Z_][a-zA-Z0-9_]*$/'; // characters accepted by most DBMSs
        return boolval(preg_match($pattern, $name));
    }

    /**
     * Checks if each name in the array is unique.
     *
     * @param array $array: The array containing names to be checked for uniqueness.
     *
     * @return array
     *  - success = [true]
     *  - failure = [false, duplicate names]
     */
    function checkNameIsUnique($array){
        $valuesCount = array_count_values($array); //Count the number of occurrences of values
        $valuesDuplicated = ""; //stock duplicated values
        foreach($valuesCount as $key => $value){
            if($value != 1){
                $valuesDuplicated .= $key . ", "; 
            }
        }

        if($valuesDuplicated != ""){ //if duplicated values then send for error message
            $valuesDuplicated = rtrim($valuesDuplicated, ', ');
            return [false, $valuesDuplicated];
        }
        return [true];
    }

    /**
     * Checks the validity of the database name  and return it.
     *
     * @param array $line: The line containing the database name.
     * @param string $fileName: The name of the file.
     *
     * @return array
     *  - success = [true, database name]
     *  - failure = [false, error message]
     */
    function checkAndGetDbName($line, $fileName){
        $dbName = null;
        $setInFile = false;
        if($line[1] !== ""){ // set name in file
            $dbName = $line[1];
            $setInFile = true;
        }else{// get file name
            $tmpName = explode(".csv", $fileName);
            $dbName = $tmpName[0];
        }   

        if($dbName !== null){ //db name not found
            $nameValidityResult = checkNameValidity($dbName);
            if($nameValidityResult){ //valid name for db
                return [true, $dbName];
            }else{
                if($setInFile){
                    $errorMsg = "Le nom déclaré dans le fichier n'est pas utilisable pour nommer la base de données ! <br>";
                }else{
                    $errorMsg = "Vous n'avez pas déclaré de nom dans le fichier et le nom du fichier n'est pas utilisable pour nommer la base de données ! <br>";
                }
                return [false, $errorMsg];
            }
        }else{
            return [false, "Impossible de trouver un nom pour la Base de Données !"];
        }
        
    }

    /**
     * Extracts structured information from table content.
     * check tables name validity ("" not authorizated) and unicity
     * check columns name validity ("" not authorizated) and unicity
     *
     * @param array $tablesContent: The content containing tables to be structured.
     * @param array $headers: authorizated headers.
     *
     * @return array
     *  - success = [true, structured tables]
     *  - failure = [false, error message]
     */
    function getTablesStructuredContent($tablesContent, $headers){ 
        $headerTableName = $headers[array_search("table_name", $headers)];
        $headerColumnName = $headers[array_search("column_name", $headers)];
        $headerValues = $headers[array_search("value", $headers)];

        $header = null;
        $errorMsg = null;
        $tables = array();
        $tablesName = array();

        for($i = 0; $i < sizeof($tablesContent); $i++){ //[] table entière
            $columnsName = array();
            $ColumnsAttributs = array();
            $tablesValues = array();

            for($j = 0; $j < sizeof($tablesContent[$i]); $j++){ //[][] ligne
                $header = $tablesContent[$i][$j][0];

                if($header === $headerTableName){ //table name
                    //check table name validity (empty not authorizated)
                    if(!checkNameValidity($tablesContent[$i][$j][1])){ 
                        $errorMsg .= "Table n° ". $i.", le nom : ".$tablesContent[$i][$j][1]. ", est invalide pour une table ! <br>";
                    }
  
                    $tablesName[] = $tablesContent[$i][$j][1]; //stock table name for further check

                }else if($header === $headerColumnName){ //column name
                    for($w = 1; $w < sizeof($tablesContent[$i][$j]); $w ++){
                        //no column name found at first for this table
                        if($w === 1 && $tablesContent[$i][$j][$w] === ""){
                            $errorMsg .= "Table n° ". $i." : ".$tablesName[$i].", ne possède pas de colonne ! <br>";
                        //empty name after first column then stop collecting column data for this table
                        }else if($w > 1 && $tablesContent[$i][$j][$w] === ""){ 
                            break;
                        }else{ 
                            if(!checkNameValidity($tablesContent[$i][$j][$w])){ 
                                $errorMsg .= "Table n° ". $i." : ".$tablesName[$i].", le nom : ".$tablesContent[$i][$j][$w]. ", est invalide pour une colonne ! <br>";
                            }
                            //stock column name for checking if they are unique within this table
                            $columnsName[] = $tablesContent[$i][$j][$w]; 
                        }
                    }

                    //check if columns name are unique within this table
                    $columnNameUniqueResult = checkNameIsUnique($columnsName);
                    if(!$columnNameUniqueResult[0]){ //names are duplicated
                        $errorMsg .= "Table n° ". $i." : ".$tablesName[$i].", le(s) nom(s) de colonne : ".$columnNameUniqueResult[1].", est/sont dupliqué(s) ! <br>";
                    }
                    
                }else if($header === $headerValues){ //values
                    $tmpValue = array();
                    for($y = 1; $y < sizeof($columnsName) + 1; $y ++){
                        $tmpValue[] = $tablesContent[$i][$j][$y];
                    }
                    
                    if(!empty(array_filter($tmpValue))){ //check if there is almost one value in line
                        $tablesValues[] = $tmpValue;
                    }
                    

                }else{ //column attributes
                    for($z = 1; $z < sizeof($columnsName) + 1; $z ++){
                        $ColumnsAttributs[$columnsName[$z-1]][$header] = $tablesContent[$i][$j][$z];
                    }
                }

                //stock structured tables (columns, attributes and values)
                $tables[$tablesName[$i]] = $ColumnsAttributs; 
                if(!empty(array_filter($tablesValues))){ //check if there is almost one value in the table
                    $tables[$tablesName[$i]]["value"] = $tablesValues;
                }
            }
        }
        //check if tables name are unique
        $tableNameUniqueResult = checkNameIsUnique($tablesName);
        if(!$tableNameUniqueResult[0]){
            $errorMsg .= "Le(s) nom(s) de table : ".$tableNameUniqueResult[1].", est/sont dupliqué(s) ! <br>";
        }

        if($errorMsg != null){
            return [false, $errorMsg];
        }else{
            return [true, $tables];
        }
    }
    
    /**
     * Extracts structured content from the file.
     *
     * @param array $fileContent: The content of the file.
     * @param string $fileName: The name of the file.
     * @param string $marker: The separation marker.
     * @param array $headers: The array of authorized headers.
     *
     * @return array
     *  - success = [true, structured db]
     *  - failure = [false, error message]
     */
    function getStructuredContent($fileContent, $fileName, $marker, $headers){
        $headerDbName = $headers[array_search("db", $headers)];
        $headerTableName = $headers[array_search("table_name", $headers)];

        $structuredContent = array();
        $tablesContent = array(); // collecting all tables
        $tableInfo = array(); // collecting data for current table
        $isCollectingTableInfo = false; // check if table data collection is in progress
        $errorMsg = null;
        $header = null;
        $dbName = null;

        foreach ($fileContent as $line) { 
            $lineData = str_getcsv($line, $marker);
                                                
            if ($lineData !== false) {
                
                $header = $lineData[0];

                if($header === $headerDbName){ //db name
                    
                    $dbNameResult = checkAndGetDbName($lineData, $fileName); //check validity and got it if its ok
                    if($dbNameResult[0]){ // is valid
                        $dbName = $dbNameResult[1];
                    }else{ //db name is not valid then return error 
                        $errorMsg .= $dbNameResult[1];
                    }

                } elseif ($header == $headerTableName) { //get informations about table 
                    
                    if ($isCollectingTableInfo) {
                        $tablesContent[] = $tableInfo; // add informations collected in general table array
                    }
                    
                    $tableInfo = array(); // collecting informations for new table
                    $tableInfo[] = $lineData; // get table_name
                    $isCollectingTableInfo = true;

                } elseif ($isCollectingTableInfo) {
                    $tableInfo[] = $lineData; // collecting information for current table
                }
            }
        }

        // if exist informations for current table after loop, then add them
        if ($isCollectingTableInfo && !empty($tableInfo)) {
            $tablesContent[] = $tableInfo;
        }
        
        if($errorMsg !== null){
            return [false, $errorMsg];
        }else{
            //get structured tables informations
            $tablesStructuredContent = getTablesStructuredContent($tablesContent, $headers);
            if($dbName != null && $tablesStructuredContent[0]){
                $structuredContent["db"] = $dbName;
                $structuredContent["tables"] = $tablesStructuredContent[1];
                return [true, $structuredContent];
            }else if($dbName === null){
                return [false, "Impossible de trouver un nom pour la Base de Données !"];
            }else{
                return $tablesStructuredContent; // [false, "Error message"];
            }
            
        }

    }
    
    /**
     * Checks the structure of the file content.
     *
     * @param array $fileContent: The content of the file.
     * @param string $fileName: The name of the file.
     * @param array $headers: The array of authorized headers.
     *
     * @return array
     *  - success = [true, structured content]
     *  - failure = [false, error message]
     */
    function checkFileStructure($fileContent, $fileName, $marker, $headers){
        
        $MarkerResult = checkAndGetMarker($fileContent[0], $marker);
        if($MarkerResult[0]){ //marker authorizated found

            $HeadersResult = checkHeaders($fileContent, $marker, $headers);

            if($HeadersResult[0]){ //headers are all authorizated
                
                //get content structured content for further check
                $structuredContentResult = getStructuredContent($fileContent, $fileName, $marker, $headers); 
                return $structuredContentResult; // = [true, DATA STRUCTURED] OR [false, "Error message"];

            }else{
                return $HeadersResult;  // = [false, "Error message"];
            }
        }else{
            return $MarkerResult; // = [false, "Error message"];
        }
    }

?>