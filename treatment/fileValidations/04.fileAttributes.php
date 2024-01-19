<?php
    //FILE VALIDATION Part 4

    /**
     * Formats the attribute values in the table for XSS (Cross-site scripting) and injection vulnerabilities.
     *
     * @param array $table: The table to be checked and structured.
     * @param array $columnsName: Names of the columns in the table.
     *
     * @return array
     *  - [true, table with sanitized attributes]
     */
    function getFormattedAttributes($table, $columnsName){
        foreach($columnsName as $ColumnName){
            $headersSet = array_keys($table[$ColumnName]); //get Attributs headers for this column

            foreach($headersSet as $header){
                $table[$ColumnName][$header] = htmlspecialchars(htmlspecialchars(strip_tags(trim($table[$ColumnName][$header])))); 
            }
        }
        
        return [true, $table]; //return table with modifications
    }

    /**
     * Checks if the data type value contain only letters and return it to upper case.
     *
     * @param string $attribute: The data type value to be checked.
     *
     * @return bool
     *  - success = [true, upper case data type]
     *  - failure = [false]
     */
    function getDataTypeUpper($attribute){
        $pattern = '/^[a-zA-Z]*$/'; 
        if(boolval(preg_match($pattern, $attribute))){//check pattern for Data type : can only be letters 
            return [true, strtoupper($attribute)];
        }else{
            return [false];
        }       
    }

    /**
     * Checks if columns contain valid data types and converts them to uppercase.
     *
     * @param array $table: The table to be checked and structured.
     * @param array $columnsName: Names of the columns in the table.
     * @param string $header: The header indicating the data type (= 'data_type').
     *
     * @return array
     *  - success = [true, modified table]
     *  - failure = [false, error message]
     */
    function checkAndGetDataType($table, $columnsName, $header){ 
        $errorMsg = null;
        $result = null;
        foreach($columnsName as $ColumnName){
            if(!array_key_exists($header, $table[$ColumnName])){ //check if data_type header is set 
                $errorMsg .= "ne contient pas le data_type ! <br>"; 
            }else{ 
                if(empty($table[$ColumnName][$header])){ //check if data_type value is set 
                    $errorMsg .= "la colonne : ".$ColumnName." ne contient pas le data_type ! <br>"; 
                }else{
                    $result = getDataTypeUpper($table[$ColumnName][$header]);
                    if($result[0]){
                        $table[$ColumnName][$header] = $result[1];
                    }else{
                        $errorMsg .= "La colonne : " . $ColumnName . " contient un ".$header." invalide ! <br>";
                    }
                }
            }
        }

        if($errorMsg !== null){
            return [false, $errorMsg];
        }else{
            return [true, $table]; 
        }
    }

    /**
     * Checks if the max size value contains valid integers or is blank.
     *
     * @param array $table: The table to be checked and structured.
     * @param array $columnsName: Names of the columns in the table.
     * @param string $header: The header indicating the max size value.
     *
     * @return array
     *  - success = [true, int (none = 0) / [int, int]]
     *  - failure = [false, error message]
     */
    function checkAndGetIntMaxSize($table, $columnsName, $header){
        //check pattern : can be totaly blank or only be int and not 0 if 2 parameters then "x,x"
        $pattern = '/^([1-9]\d*|)(,[1-9]\d*)?$/'; 
        $errorMsg = null;

        foreach($columnsName as $ColumnName){
            $attribute = $table[$ColumnName][$header];
            if(preg_match($pattern, $attribute, $matches)){
                if(isset($matches[2])) {
                    $parts = explode(',', $attribute); //if comma separation, then get array of int
                    if(sizeof($parts) > 1){
                        if(intval($parts[0]) > 0){ //first part need to be set 
                            $table[$ColumnName][$header] = [intval($parts[0]), intval($parts[1])];
                        }else{
                            $errorMsg .= "La colonne : " . $ColumnName . " contient un ".$header." invalide ! <br>";
                        }
                    }else{
                        return [false];
                    }
                } else { //one value or blank if blank then return 0
                    $table[$ColumnName][$header] = intval($attribute);
                }
            } else {
                $errorMsg .= "La colonne : " . $ColumnName . " contient un ".$header." invalide ! <br>";      
            }      
        }

        if($errorMsg !== null){
            return [false, $errorMsg];
        }else{
            return [true, $table]; 
        }
    }
 
    /**
     * Checks if the rest of the attributes' values are valid boolean representations.
     *
     * @param array $table: The table to be checked and structured.
     * @param array $columnsName: Names of the columns in the table.
     * @param array $headersBool: Headers indicating boolean attributes.
     *
     * @return array
     *  - success = [true, modified table]
     *  - failure = [false, error message]
     */
    function checkAndGetBoolValues($table, $columnsName, $headersBool){
        $pattern ='/^(1|0|)?$/';  //check pattern : 1 = true, 0 or blank = false
        $errorMsg = null;

        foreach($columnsName as $ColumnName){
            foreach($headersBool as $header){
                $attribute = $table[$ColumnName][$header];
                if(boolval(preg_match($pattern, $attribute))){
                    if($attribute === ""){$attribute = 0;}
                    $table[$ColumnName][$header] = boolval($attribute);
                }else{
                    $errorMsg .= "La colonne : " . $ColumnName . " contient une valeur invalide pour l'attribut : " . $header . " ! <br>";
                }
            }
        }
        
        if($errorMsg !== null){
            return [false, $errorMsg];
        }else{
            return [true, $table]; 
        }

    }

    /**
     * Sets the 'Not Null' attribute to true for columns that are Primary Keys.
     *
     * @param array $table: The table containing column attributes.
     * @param array $columnsName: Names of the columns in the table.
     * @param string $headerPrimaryKey: The header indicating the primary key attribute.
     * @param string $headerNotNull: The header indicating the 'Not Null' attribute.
     *
     * @return array
     *  - success = [true, modified table]
     *  - failure = [false]
     */
    function setPrimarykeyNotNull($table, $columnsName, $headerPrimaryKey, $headerNotNull){

        foreach($columnsName as $ColumnName){
            if($table[$ColumnName][$headerPrimaryKey] === boolval(true)){
                $table[$ColumnName][$headerNotNull] = true;
            }
        }

        return [true, $table]; //return table with modifications
    }

    /**
     * Checks if auto-increment is defined only once per table, either with a primary key or with unique (both with not null).
     *
     * @param array $table: The table containing column attributes.
     * @param array $columnsName: Names of the columns in the table.
     * @param string $headerAutoIncrement: The header indicating the auto-increment attribute.
     * @param string $headerPrimaryKey: The header indicating the primary key attribute.
     * @param string $headerUnique: The header indicating the unique attribute.
     * @param string $headerNotNull: The header indicating the 'Not Null' attribute.
     *
     * @return array
     *  - success = [true, modified table]
     *  - failure = [false, error message]
     */
    function checkAutoIncrement($table, $columnsName, $headerAutoIncrement, $headerPrimaryKey, $headerUnique, $headerNotNull){
        $errorMsg = null;
        $autoIncrementCount = 0; // Counter for auto-increment occurrences

        foreach($columnsName as $ColumnName){
            
            if($table[$ColumnName][$headerAutoIncrement]){ //auto_increment is define
                $autoIncrementCount ++;
                //neither pk nor unique is define
                if(!$table[$ColumnName][$headerPrimaryKey] && !$table[$ColumnName][$headerUnique]){
                    $errorMsg .= "l'attribut : " . $headerAutoIncrement. " ne peut être défini qu'avec les attributs : ".$headerPrimaryKey." ou ".$headerUnique." ! <br>";
                }
                //pk or unique is define but not null is not set then set it to true
                else if(($table[$ColumnName][$headerPrimaryKey] || $table[$ColumnName][$headerUnique]) && !$table[$ColumnName][$headerNotNull]){
                    $table[$ColumnName][$headerNotNull] = true;

                }
            }
        }

        // Check if auto-increment is defined more than once
        if($autoIncrementCount > 1){
            $errorMsg .= "L'attribut : " . $headerAutoIncrement . " ne peut être défini qu'une seule fois par table ! <br>";
        }
                
        if($errorMsg !== null){
            return [false, $errorMsg];
        }else{
            return [true, $table]; 
        }
    }

    /**
     * Checks if the reference value contain only authorizated characters 
     * table and column name separated by a dot or blank.
     *
     * @param string $attribute: The reference value to be checked.
     *
     * @return bool
     *  - success = true
     *  - failure = false
     */
    function checkForeignReference($attribute){
        $pattern ='/^([a-zA-Z_][a-zA-Z0-9_]*)(\.[a-zA-Z_][a-zA-Z0-9_]*)$|^$/'; 
        //check pattern : same name validity check as for tables and columns or, 
        //combination of the two separated by a dot or blank
        return boolval(preg_match($pattern, $attribute));
    }

    /**
     * Checks if the referenced table in a foreign key constraint is valid.
     *
     * @param string $tableName: Name of the current table.
     * @param string $ColumnName: Name of the column containing the foreign key constraint.
     * @param string $fkTableName: Name of the referenced table in the foreign key constraint.
     * @param array $tablesName: Names of the existing tables.
     *
     * @return array
     *  - success = [true]
     *  - failure = [false, error message]
     */
    function checkForeignTable($tableName, $ColumnName, $fkTableName, $tablesName){
        $errorMsg = null;

        if(!in_array($fkTableName, $tablesName)){ //table doesn't exist
            $errorMsg = "La colonne : " . $ColumnName . " contient une référence de table invalide ! <br>";
        }else if(in_array($fkTableName, $tablesName)){ //table exist then check is set before
            $fkTablePosition = array_search($fkTableName, $tablesName);
            $currentTablePosition = array_search($tableName, $tablesName);
            if($fkTablePosition > $currentTablePosition){
                $errorMsg = "La table de référence : ".$fkTableName." doit être déclarée avant la table : " .$tableName. " qui contient la référence ! <br>";
            }
        }

        if($errorMsg !== null){
            return [false, $errorMsg];
        }else{
            return [true];
        }
    }

    /**
     * Checks if the referenced column in a foreign key constraint is valid.
     *
     * @param string $ColumnName: Name of the current column in the table.
     * @param array $fkTable: Attributes of the referenced table.
     * @param string $fkColumnName: Name of the referenced column in the foreign key constraint.
     * @param string $headerPrimaryKey: Header name for primary key attribute.
     * @param string $headerUnique: Header name for unique attribute.
     *
     * @return array
     *  - success = [true]
     *  - failure = [false, error message]
     */
    function checkForeignColumn($ColumnName, $fkTable, $fkColumnName, $headerPrimaryKey, $headerUnique){
        $errorMsg = null;
        
        if(!array_key_exists($fkColumnName, $fkTable)){ //column doesn't exist for table
            $errorMsg = "La colonne : " . $ColumnName . " contient une référence de colonne invalide ! <br>";
        //column is not PK or Unique
        }else if(!$fkTable[$fkColumnName][$headerPrimaryKey] && !$fkTable[$fkColumnName][$headerUnique]){ 
            $errorMsg = "La colonne : " . $ColumnName . " contient une référence qui n'est ni une clé primaire ni unique ! <br>";
        }

        if($errorMsg !== null){
            return [false, $errorMsg];
        }else{
            return [true];
        }
    }

    /**
     * Validates foreign key references in the provided table columns.
     *
     * @param array $table: The table containing data to be validated.
     * @param string $tableName: The name of the current table.
     * @param array $columnsName: Names of the columns in the table.
     * @param array $tables: Array of all tables and their attributes.
     * @param array $tablesName: Names of the existing tables.
     * @param string $headerForeignKey: The header indicating the foreign key attribute.
     * @param string $headerReferences: The header indicating the references attribute.
     * @param string $headerPrimaryKey: The header indicating the primary key attribute.
     * @param string $headerUnique: The header indicating the unique attribute.
     *
     * @return array
     *   - success = [true]
     *   - failure = [false, error message]
     */
    function checkForeignKey($table, $tableName, $columnsName, $tables, $tablesName, $headerForeignKey, $headerReferences, $headerPrimaryKey, $headerUnique){

        $errorMsg = null;

        foreach($columnsName as $ColumnName){
            $foreignKey = $table[$ColumnName][$headerForeignKey];
            $foreignKeyReference = $table[$ColumnName][$headerReferences]; 
            
            if($foreignKey){ //FK is set 
                if($foreignKeyReference === ""){//reference is not set
                    $errorMsg .= "La colonne : " . $ColumnName . " est une clé étrangère mais ne contient pas de référence ! <br>";
                }else{
                    $referenceResult = checkForeignReference($foreignKeyReference);
                    if($referenceResult){ //reference contain authorizated characters 
                        //Explode on dot to get foreign table and column names
                        $foreignKeyRefExplode = explode(".", $foreignKeyReference);
                        
                        if($foreignKeyRefExplode[0] != "" && $foreignKeyRefExplode[1] != ""){
                            $fkTableName = $foreignKeyRefExplode[0];
                            $fkColumnName = $foreignKeyRefExplode[1];
                            //check if the foreign table exist and if is declared before the current table
                            $tableResult = checkForeignTable($tableName, $ColumnName, $fkTableName, $tablesName);
                            if($tableResult[0]){ 
                                //check if the column exist in the foreign table
                                $columnResult = checkForeignColumn($ColumnName, $tables[$fkTableName], $fkColumnName, $headerPrimaryKey,$headerUnique);
                                if(!$columnResult[0]){ //column doesn't exist in the foreign table
                                    $errorMsg .= $columnResult[1];
                                }
                            }else{ //the foreign table doesn't exist or is not defined before
                                $errorMsg .= $tableResult[1];
                            }
                        }else{
                            $errorMsg .= "La colonne : " . $ColumnName . " contient une référence invalide : ".$foreignKeyReference." ! <br>";
                        }
                    }else{
                        $errorMsg .= "La colonne : " . $ColumnName . " contient une référence invalide : ".$foreignKeyReference." ! <br>";
                    }
                }
            //FK not set but reference is set
            }else if(!$foreignKey && $foreignKeyReference != ""){ 
                $errorMsg .= "La colonne : " . $ColumnName . " n'est pas une clé étrangère mais contient la référence : ".$foreignKeyReference." ! <br>";
            }
        }

        if($errorMsg !== null){
            return [false, $errorMsg];
        }else{
            return [true];
        }
    }

    /**
     * Validates the usage of the 'Generated' attribute in the provided table columns.
     *
     * @param array $table: The table containing data to be validated.
     * @param array $columnsName: Names of the columns in the table.
     * @param string $headerGenerated: The header indicating the 'Generated' attribute.
     * @param string $headerDefaultExpression: The header indicating the 'Default Expression' attribute.
     *
     * @return array
     *   - success = [true]
     *   - failure = [false, error message]
     */
    function checkGenerated($table, $columnsName, $headerGenerated, $headerDefaultExpression){
        $errorMsg = null;

        foreach($columnsName as $ColumnName){
            
            if($table[$ColumnName][$headerGenerated]){ //generated is define
                if($table[$ColumnName][$headerDefaultExpression] === ""){
                    $errorMsg .= "l'attribut : " .$headerGenerated. " ne peut être défini qu'avec l'attribut : ".$headerDefaultExpression." ! <br>";
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
     * Validates and checks various attributes within the structured content of tables.
     *
     * @param array $structuredContent: The structured content containing tables and their attributes.
     * @param array $headers: Array of header names for attributes.
     * @param array $headersBool: Array of boolean attribute header names.
     *
     * @return array
     *   - success = [true, modified tables]
     *   - failure = [false, error message]
     */
    function checkFileAttributes($structuredContent, $headers, $headersBool){
        $headerDataType = $headers[array_search("data_type", $headers)]; 
        $headerMaxSize = $headers[array_search("max_size", $headers)]; 
        $headerPrimaryKey = $headers[array_search("primary_key", $headers)];
        $headerNotNull = $headers[array_search("not_null", $headers)];
        $headerUnique = $headers[array_search("unique", $headers)];
        $headerAutoIncrement = $headers[array_search("auto_increment", $headers)];
        $headerGenerated = $headers[array_search("generated", $headers)];
        $headerDefaultExpression = $headers[array_search("default_expression", $headers)];
        $headerForeignKey = $headers[array_search("foreign_key", $headers)];
        $headerReferences = $headers[array_search("references", $headers)];

        $errorMsg = null;
        $tables = $structuredContent["tables"];
        $tablesName = array_keys($tables); //get all tables name
        
        foreach($tablesName as $tableName){ 
            //exctract values to get only columns name
            $tmptable = $tables[$tableName]; 
            if(array_key_exists("value", $tmptable)){
                unset($tmptable["value"]);
            }
                
            $columnsName = array_keys($tmptable); //get columns name for current table

            //get values of attributes with htmlspecialchar and strip_tags
            $attributesResult = getFormattedAttributes($tables[$tableName], $columnsName, $headers); 
            if($attributesResult[0]){ 
                $tables[$tableName] = $attributesResult[1]; //get table with modifications
            }
                
            //check if data type is set and if is only letters
            $dataTypeResult = checkAndGetDataType($tables[$tableName], $columnsName, $headerDataType); 
            if($dataTypeResult[0]){ //data type is correct then get it to upper case
                $tables[$tableName] = $dataTypeResult[1];
            }else{
                $errorMsg .= "Table : " .$tableName. "<br>" .$dataTypeResult[1];
            }

            //check and get max_size or parameters to int
            $maxSizeResult = checkAndGetIntMaxSize($tables[$tableName], $columnsName, $headerMaxSize); 
            if($maxSizeResult[0]){ //data type is correct then get it to upper case
                $tables[$tableName] = $maxSizeResult[1];
            }else{
                $errorMsg .= "Table : " .$tableName. "<br>" .$maxSizeResult[1];
            }

            //get bool value where is necessary
            $boolValuesResult = checkAndGetBoolValues($tables[$tableName], $columnsName, $headersBool);
            if($boolValuesResult[0]){ //data type is correct then get it to upper case
                $tables[$tableName] = $boolValuesResult[1];
            }else{
                $errorMsg .= "Table : " .$tableName. "<br>" .$boolValuesResult[1];
            }

            //set not null with primary key
            $primarykeyResult = setPrimarykeyNotNull($tables[$tableName], $columnsName, $headerPrimaryKey, $headerNotNull);
            if($primarykeyResult[0]){ 
               $tables[$tableName] = $primarykeyResult[1]; //get table with modifications
            }

            //check auto_increment
            $primarykeyResult = checkAutoIncrement($tables[$tableName], $columnsName, $headerAutoIncrement, $headerPrimaryKey, $headerUnique, $headerNotNull);
            if($primarykeyResult[0]){ 
                $tables[$tableName] = $primarykeyResult[1]; //get table with modifications
            }else{
                $errorMsg .= "Table : " .$tableName. "<br>" .$primarykeyResult[1];
            }

            //check Foreign Key and his reference
            $foreignKeyResult = checkForeignKey($tables[$tableName], $tableName, $columnsName, $tables, $tablesName, $headerForeignKey, $headerReferences, $headerPrimaryKey, $headerUnique);
            if(!$foreignKeyResult[0]){
                $errorMsg .= "Table : " .$tableName. "<br>" .$foreignKeyResult[1];
            }
            
            //check generated : need a value on default_expression
            $generatedResult = checkGenerated($tables[$tableName], $columnsName, $headerGenerated, $headerDefaultExpression);
            if(!$generatedResult[0]){
                $errorMsg .= "Table : " .$tableName. "<br>" .$generatedResult[1];
            }
        }

        if($errorMsg !== null){
            return [false, $errorMsg];
        }else{
            $structuredContent["tables"] = $tables;
            return [true, $structuredContent];
        }

    }


?>