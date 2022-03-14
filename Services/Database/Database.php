<?php 
    
    class Database {

        public $query = null;
        public $params = null; 
        public $options = null;

        public $host = null;
        public $port = null;
        public $charset = null; 
        public $username = null;
        public $password = null;
        public $database = null; 

        public $statement = null;
        public $connection = null;
        public $reusableParams = false;

        public $fetchType = PDO::FETCH_ASSOC;

        public function __construct($MANUAL = false) 
        {
            
            if($MANUAL != true) {

                $this->host     = DBHOST;
                $this->port     = DBPORT;
                $this->username = DBUSER;
                $this->password = DBPASS;
                $this->database = DBNAME;
                $this->charset  = DBCHAR;

                $this->options  = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset
                ];

                $this->connect();

            }

            else $this->connect();

        }
        
        public function connect()
        {
            var_dump($this);die();
            try {

                $conn = new PDO("mysql:host=$this->host;dbname=$this->database;charset=$this->charset", $this->username, $this->password, $this->options);
                $this->connection = $conn;

                // echo "Connected successfully";

            } catch(PDOException $e) {

                echo "Connection failed: " . $e->getMessage();
                die();

            }
        }

        public function GET($sqlQuery , $params = null, $PRINT = false) 
        {

            $this->query = $sqlQuery;
            if($params != null) 
                $this->params = $params;
            
            if($PRINT == true)
                $this->print_query($sqlQuery);
            else 
                return $this->executeQuery($sqlQuery)->fetchAll($this->fetchType);

        }

        public function SET($sqlQuery , $params, $print = false) 
        {

            $this->query = $sqlQuery;
            if($params != null) 
                $this->params = $params;

            if($print == true)
                $this->print_query($sqlQuery);
            else
                return $this->executeQuery($sqlQuery)->rowCount();

        }

        public function executeQuery($sqlQuery) 
        {
            
            if($sqlQuery == "" || $sqlQuery == null)  {
                echo " <br><br> !!!!!! query is not defined !!!!!! <br><br> ";
                return null;
            }

            if($this->params == null) {

                $stmt = $this->connection->query($sqlQuery);
                $stmt->execute();
                $this->statement = $stmt;
                return $stmt;

            }
            
            else {

                /* check in() -s and makes array to fills with array data from $this->params in foreach */
                $this->prepareInOperator($sqlQuery);
                $stmt = $this->connection->prepare($this->query);

                foreach ($this->params as $key => $value) 
                    $stmt->bindValue(':' . $key, $value);
                

                $stmt->execute();
                $this->statement = $stmt;

                return $stmt;

            }

        }

        public function print_query($sqlQuery) 
        {
            
            /* check in() -s and makes array to fills with array data from $this->params in foreach */
            $this->prepareInOperator($sqlQuery);

            // print_r($this->params);
            /* fills placeholder parameters with data to print the query (!! DOES NOT EXECUTE !!) */
            foreach ($this->params as $key => $value) 
                $this->query = str_replace(":" . $key, $value, $this->query);
            

            echo $this->query;

        }
        
        private function prepareInOperator($sqlQuery)
        {
            $in_params = [];
            $last_in_ind = 1;

            foreach ($this->params as $key => $value) {             
                if(is_array($value)) {

                    $in_size = COUNT($this->params[$key]);
                    $query_in_param_str = "";

                    $i = 0;

                    while ($i < $in_size) {

                        $generateKey = 'in_' . $last_in_ind;
                        $in_params[$generateKey] = $this->params[$key][$i];
                        
                        $query_in_param_str .= ":" . $generateKey;

                        if( $i < $in_size - 1 ) 
                            $query_in_param_str .= ",";

                        $last_in_ind++;
                        $i++;

                    }
                    
                    $sqlQuery = str_replace(":".$key, $query_in_param_str, $sqlQuery);
                    unset($this->params[$key]);

                }
            }

            $this->params = array_merge($this->params, $in_params);
            $this->query = $sqlQuery;

        }

        public function GetNumRows()
        {
            return $this->statement->rowCount();
        }
        
        public function GetLastId()
        {
            return $this->connection->lastInsertId();
        }

        public function Exists()
        {
            return ( $this->statement->rowCount() > 0 ) ? true : false;
        }

    }