<?php
    //FILE VALIDATION Part 5


    /**
     * Formats the values to prevent XSS (Cross-site scripting) and injection vulnerabilities.
     *
     * @param array $table: The table containing the values to be secured.
     * @param string $header: The header indicating the column to be processed.
     *
     *  @return array
     *  - [true, table with sanitized values]
     */
    function getFormatedValues($table, $header){   

        foreach($table[$header] as $rowIndex => &$row){ 
            // Use '&', pass by reference to directly modify the value in the original table
            for($i = 0; $i < sizeof($row); $i++){
                $row[$i] =  htmlspecialchars(htmlspecialchars(strip_tags(trim($row[$i]))));                
            }
        }

        return [true, $table]; //return table with modifications
    }

    /**
     * Validates if the values for specified columns respect primary key constraints, allowing blanks only if auto-incremented.
     *
     * @param array $table: The table containing the values to be checked.
     * @param array $columnsName: Names of the columns in the table.
     * @param string $headerPrimaryKey: The header indicating the primary key attribute.
     * @param string $headerAutoIncrement: The header indicating the auto-increment attribute.
     *
     * @return array
     *   - success = [true]
     *   - failure [false, error message]
     */
    function checkPrimaryKey($table, $columnsName, $headerValue, $headerPrimaryKey, $headerAutoIncrement){   
        
        $errorMsg = null;

        foreach($table[$headerValue] as $rowIndex => $rowValue){
            $line = $rowIndex + 1;

            foreach($columnsName as $ColumnName){

                //get the value for the corresponding column in $rowValue
                $valueForColumn = $rowValue[array_search($ColumnName, $columnsName)];

                //check value for Primary Key can be blank only if auto-increment
                $isPrimaryKey = $table[$ColumnName][$headerPrimaryKey];
                $isAutoIncrement = $table[$ColumnName][$headerAutoIncrement];
                if ($isPrimaryKey && $isAutoIncrement === false && empty($valueForColumn)) {
                    $errorMsg .= "La valeur en ligne ".$line." pour : " .$ColumnName." ne peut pas être vide car elle est la clé primaire et n'est pas auto-incrémentée ! <br>";
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
     * Validates if the values for specified columns are unique within the table.
     *
     * @param array $table: The table containing the values to be checked.
     * @param array $columnsName: Names of the columns in the table.
     * @param string $headerValue: The header indicating the values in the table.
     * @param string $headerUnique: The header indicating the uniqueness attribute.
     *
     * @return array
     *   - success = [true]
     *   - failure [false, error message]
     */
    function checkUnique($table, $columnsName, $headerValue, $headerUnique){   
        
        $errorMsg = null;

        foreach($table[$headerValue] as $rowIndex => $rowValue){
            $line = $rowIndex + 1;

            foreach($columnsName as $ColumnName){

                //get the value for the corresponding column in $rowValue
                $valueForColumn = $rowValue[array_search($ColumnName, $columnsName)];

                //check if value is unique in this table
                $isUnique = $table[$ColumnName][$headerUnique];
                if ($isUnique) {
                    $count = 0;
                    foreach ($table[$headerValue] as $otherRowIndex => $otherRowValue) {
                        $otherValueForColumn = $otherRowValue[array_search($ColumnName, $columnsName)];
                        
                        if ($otherValueForColumn === $valueForColumn) {
                            $count++;
                            if ($count > 1) {
                                $errorMsg .= "La valeur en ligne ".$line." pour : " .$ColumnName." n'est pas unique dans cette table ! <br>";
                                break;
                            }
                        }
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
     * Validates the values in the table against specified foreign key constraints.
     *
     * @param array $table: The table containing the values to be checked.
     * @param array $columnsName: Names of the columns in the table.
     * @param string $headerValue: The header indicating the values in the table.
     * @param string $headerForeignKey: The header indicating the foreign key attribute.
     * @param string $headerReferences: The header indicating the referenced attribute.
     * @param array $tables: Associative array of tables and their values.
     *
     * @return array
     *   - success = [true]
     *   - failure = [false, error message]
     */
    function checkReference($table, $columnsName, $headerValue, $headerForeignKey, $headerReferences, $tables){   
        
        $errorMsg = null;

        foreach($table[$headerValue] as $rowIndex => $rowValue){
            $line = $rowIndex + 1;

            foreach($columnsName as $ColumnName){

                //get the value for the corresponding column in $rowValue
                $valueForColumn = $rowValue[array_search($ColumnName, $columnsName)];

                //check value for Reference if it's Foreign Key
                $isForeignKey = $table[$ColumnName][$headerForeignKey];
                $referenceForColumn = $table[$ColumnName][$headerReferences];
                //it's foreign key but reference is not set
                if ($isForeignKey && empty($valueForColumn)) { 
                    $errorMsg .= "La référence à la clé étrangère : ".$referenceForColumn." en ligne ".$line." pour : " .$ColumnName." ne peut pas être vide ! <br>";
                //it's foreign key and reference is set 
                }else if($isForeignKey && !empty($valueForColumn)){ 
                    $referenceExists = false;
                    $referenceExplode = explode(".", $referenceForColumn);
                    
                    if($referenceExplode[0] != "" && $referenceExplode[1] != ""){
                        $fkTableName = $referenceExplode[0];
                        $fkColumnName = $referenceExplode[1];

                        //get values from foreign table
                        $foreignValues = $tables[$fkTableName][$headerValue];
                        $colIndex = array_search($fkColumnName,array_keys($tables[$fkTableName]));
                        //check if value exist in foreign table 
                        foreach($foreignValues as $row){ 
                            if($row[$colIndex] === $valueForColumn){$referenceExists = true;}
                        }
                    }

                    if (!$referenceExists) { //reference value doesn't exist
                        $errorMsg .= "La référence à la clé étrangère : ".$referenceForColumn." en ligne ".$line." pour : " .$ColumnName." n'existe pas dans la table de référence ! <br>";
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
     * Checks and validates various attributes' values in structured content against predefined constraints.
     *
     * @param array $structuredContent: The structured content to be validated.
     * @param array $headers: Headers specifying attributes in the structured content.
     *
     * @return array
     *   - success = [true, modified structured content]
     *   - failure = [false, error message] 
     */
    function checkFileValues($structuredContent, $headers){
        $headerPrimaryKey = $headers[array_search("primary_key", $headers)];
        $headerUnique = $headers[array_search("unique", $headers)];
        $headerAutoIncrement = $headers[array_search("auto_increment", $headers)];
        $headerForeignKey = $headers[array_search("foreign_key", $headers)];
        $headerReferences = $headers[array_search("references", $headers)];
        $headerValue = $headers[array_search("value", $headers)];

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

            if(isset($tables[$tableName][$headerValue])){ //check on values if exist

                //get values with htmlspecialchar and strip_tags
                $valuesResult = getFormatedValues($tables[$tableName], $headerValue);
                if($valuesResult[0]){ 
                    $tables[$tableName] = $valuesResult[1]; //get table with modifications
                }

                //check if value of primary key is set or auto increment 
                $primaryKeyResult = checkPrimaryKey($tables[$tableName], $columnsName, $headerValue, $headerPrimaryKey, $headerAutoIncrement);
                if(!$primaryKeyResult[0]){ 
                    $errorMsg .= "Table : " .$tableName. "<br>" .$primaryKeyResult[1];
                }

                //check if value is unique in this table 
                $uniqueResult = checkUnique($tables[$tableName], $columnsName,$headerValue, $headerUnique);
                if(!$uniqueResult[0]){ 
                    $errorMsg .= "Table : " .$tableName. "<br>" .$uniqueResult[1];
                }

                //check if foreign Key reference exist in the foreign table
                $referenceResult = checkReference($tables[$tableName], $columnsName,$headerValue, $headerForeignKey, $headerReferences, $tables);
                if(!$referenceResult[0]){ 
                    $errorMsg .= "Table : " .$tableName. "<br>" .$referenceResult[1];
                }
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