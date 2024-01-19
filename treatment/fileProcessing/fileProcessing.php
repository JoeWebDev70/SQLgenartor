<?php

    /**
     * Generates SQL script for database, table creation, and data insertion based on provided parameters.
     *
     * @param string $database: Name of the database to create.
     * @param array $tables: Associative array containing tables and their columns.
     * @param array $headers: An array defining column attributes.
     *
     * @return string The generated SQL script for database setup, table creation, and data insertion.
     */
    function createScript($database, $tables, $headers){
        $script = "";

        $hDataType = $headers[array_search("data_type", $headers)]; 
        $hMaxSize = $headers[array_search("max_size", $headers)]; 
        $hPrimaryKey = $headers[array_search("primary_key", $headers)];
        $hNotNull = $headers[array_search("not_null", $headers)];
        $hUnique = $headers[array_search("unique", $headers)];
        $hBinary = $headers[array_search("binary", $headers)];
        $hUnsigned = $headers[array_search("unsigned", $headers)];
        $hZeroFill = $headers[array_search("zero_fill", $headers)];
        $hAutoIncrement = $headers[array_search("auto_increment", $headers)];
        $hGenerated = $headers[array_search("generated", $headers)];
        $hDefaultExpression = $headers[array_search("default_expression", $headers)];
        $hForeignKey = $headers[array_search("foreign_key", $headers)];
        $hReferences = $headers[array_search("references", $headers)];
        $hPasswordHash = $headers[array_search("password_hash", $headers)];
        $hValue = $headers[array_search("value", $headers)];

        //drop and create db
        $script = "DROP DATABASE IF EXISTS `$database`;";
        $script .= "CREATE DATABASE `$database`;";
        $script .= "USE `$database`;";

        $tablesName = array_keys($tables); //get all tables name
        
        foreach($tablesName as $tableName){ 
            $tmpScriptFK = "";
            $tmpScriptPK = "";
            $tmpColValue = array();
            $autoIncrementColumnIndex = "";

            //drop and create table
            $script .= "DROP TABLE IF EXISTS `$tableName`;";
            $script .= "CREATE TABLE `$tableName` (";

            //exctract values to get only columns name
            $tmptable = $tables[$tableName]; 
            if(array_key_exists($hValue, $tmptable)){
                unset($tmptable[$hValue]);
            }
                
            $columnsName = array_keys($tmptable); //get columns name for current table

            foreach($columnsName as $columnName){
                //get attributes of column
                $columnAttributes = $tables[$tableName][$columnName];
                
                //data type
                $dataType = $columnAttributes[$hDataType];
                $script .= "`$columnName` $dataType";
                //max size or parameters
                if(isset($columnAttributes[$hMaxSize]) && !empty($columnAttributes[$hMaxSize])){
                    $maxSize = $columnAttributes[$hMaxSize];
                    if(is_array($maxSize)){
                        $script .= "($maxSize[0],$maxSize[1])";
                    }else if(is_int($maxSize) && $maxSize > 0){
                        $script .= "($maxSize)";
                    }
                }

                //not null
                if(isset($columnAttributes[$hNotNull]) && !empty($columnAttributes[$hNotNull]) && $columnAttributes[$hNotNull] === true){
                    $script .= " NOT NULL";
                }

                //unique
                if(isset($columnAttributes[$hUnique]) && !empty($columnAttributes[$hUnique]) && $columnAttributes[$hUnique] === true){
                    $script .= " UNIQUE";
                }

                //binary
                if(isset($columnAttributes[$hBinary]) && !empty($columnAttributes[$hBinary]) && $columnAttributes[$hBinary] === true){
                    $script .= " BINARY";
                }
                
                //unsigned
                if(isset($columnAttributes[$hUnsigned]) && !empty($columnAttributes[$hUnsigned]) && $columnAttributes[$hUnsigned] === true){
                    $script .= " UNSIGNED";
                }

                //zeo fill
                if(isset($columnAttributes[$hZeroFill]) && !empty($columnAttributes[$hZeroFill]) && $columnAttributes[$hZeroFill] === true){
                    $script .= " ZERO FILL";
                }

                //autoincremente
                if(isset($columnAttributes[$hAutoIncrement]) && !empty($columnAttributes[$hAutoIncrement]) && $columnAttributes[$hAutoIncrement] === true){
                    $autoIncrementColumnIndex = array_search(true, array_column($tables[$tableName], $hAutoIncrement));

                    $script .= " AUTO_INCREMENT";
                }

                //generated
                if(isset($columnAttributes[$hGenerated]) && !empty($columnAttributes[$hGenerated]) && $columnAttributes[$hGenerated] === true){
                    $script .= " GENERATED";
                }

                //default/expression
                if(isset($columnAttributes[$hDefaultExpression]) && !empty($columnAttributes[$hDefaultExpression])){
                    $default = $columnAttributes[$hDefaultExpression];
                    $script .= " DEFAULT $default";
                }

                //foreign key
                if(isset($columnAttributes[$hForeignKey]) && !empty($columnAttributes[$hForeignKey]) && $columnAttributes[$hForeignKey] === true){
                    $tmpScriptFK .= "FOREIGN KEY (`$columnName`)";
                }

                //reference key
                if(isset($columnAttributes[$hReferences]) && !empty($columnAttributes[$hReferences])){
                    $references = explode(".", $columnAttributes[$hReferences]);
                    $tmpScriptFK .= " REFERENCES `$references[0]` (`$references[1]`),";
                }

                //primary key
                if(isset($columnAttributes[$hPrimaryKey]) && !empty($columnAttributes[$hPrimaryKey]) && $columnAttributes[$hPrimaryKey] === true){
                    $tmpScriptPK .= "`$columnName`,";
                }

                //password hash
                if(isset($columnAttributes[$hPasswordHash]) && !empty($columnAttributes[$hPasswordHash]) && $columnAttributes[$hPasswordHash] === true){
                    $tmpColValue[] = array_search($columnName, $columnsName);
                }
                
                $script .= ",";

            }

            if($tmpScriptFK != ""){ //set foreign keys and their references if exist
                $script .= $tmpScriptFK;
            }

            if($tmpScriptPK != ""){//set primary keysif exist
                $tmpScriptPK = rtrim($tmpScriptPK, ',');
                $script .= "PRIMARY KEY ($tmpScriptPK)";
            }
            
            //close table creation
            $script = rtrim($script, ',');
            $script .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci; ";

            //set values in current table
            if(isset($tables[$tableName][$hValue]) && !empty($tables[$tableName][$hValue])){
                $counter = 1;
                $tableValues = $tables[$tableName][$hValue];
                $script .= "INSERT INTO `$tableName` VALUES ";

                foreach ($tableValues as $value) {
                    $script .= "(";

                    foreach ($value as $index => $columnValue) {

                        if (in_array($index, $tmpColValue)) { // hashing values for specific columns...
                            $columnValue = password_hash($columnValue, PASSWORD_DEFAULT);
                        }

                        //autoincrement is set and value is empty
                        if($autoIncrementColumnIndex != "" && $index === $autoIncrementColumnIndex && $columnValue === ""){
                            $columnValue = $counter;
                            $counter ++;
                        }

                        if(is_numeric($columnValue)){ //is numeric
                            $script .= "$columnValue,";
                        }else{ //is string
                            $script .= "'$columnValue',";
                        }

                    }
                    
                    $script = rtrim($script, ',');
                    $script .= "),";
                }
            }
            
            $script = rtrim($script, ',');
            $script .= "; ";
        }

        // Returning the generated SQL script.
        return $script;
     
    }

    /**
     * Processes the content of a file to generate SQL script based on provided headers.
     *
     * @param array $content: The content of the file containing database and table information.
     * @param array $headers: An array defining column attributes used for script generation.
     *
     * @return array 
     *  - success = [true, HTML-formatted script, plain SQL script] 
     *  - failure = [false, error message]
     */
    function processFile($content, $headers){
        $database = null;
        $tables = null;
        $htmlScript = null;
        $script = "";
        $errorMsg = null;

        if(isset($content["db"]) && !empty($content["db"]) && isset($content["tables"]) && !empty($content["tables"])){
            $database = $content["db"];
            $tables = $content["tables"];

            $script = createScript($database, $tables, $headers);
        }else{
            $errorMsg .= "Une erreur s'est produite dans la transcription du fichier ! ";
        }

        if($script != ""){
            $htmlScript = str_replace('; ', ';<br>', $script);
            return [true, $htmlScript, $script];
        }else{
            return [false, $errorMsg];
        }

    }

?>