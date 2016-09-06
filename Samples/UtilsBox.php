<?php
namespace Esri\Utils;

class Utils
{
    public $fileHandle;

    public $result;
    
    public $timeFormat = 'm-d-y H:i:s';
    
    public $seperator = ' | ';
    
    public $eol = "\r\n";
    
    public $indent = " ";
    
    public $logfile;

    function __construct(){
        
        $this->logfile = 'app.log';
        
        $this->result = array('header'=>'','body'=>'','curl_error'=>'','http_code'=>'','last_url'=>'');
        
    }
    
    public function hasErrors($response, $ch){
        
        $hasError = false;
        
        if($response === false){
            
            $this->log(curl_error($ch));
            
            $hasError = true;
            
        }
        
        return $hasError;
    }
    
    public function setSpecialHeader(){
        
        header('X-Frame-Options: BOXPOPUP');
        
    }
    
    public function get($params, $url){
        
        $result = null;
        
        $querystring = http_build_query($params);
        
        try {
            
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $querystring);
            
            $result = curl_exec($ch);
            
            if($this->hasErrors($result, $ch)){
                
                exit();
                
            }
            
            curl_close($ch);
            
        } catch (Exception $e) {

            $this->log($e->getMessage());
            
        }
        
        return $result;
    }
    
    public function post($params, $url){
        
        $result = null;

        try {
    
            $ch = curl_init();
    
            curl_setopt($ch, CURLOPT_URL, $url);
    
            curl_setopt($ch, CURLOPT_POST, true);
    
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
            curl_setopt($ch, CURLOPT_HEADER, true);
    
            $response = curl_exec($ch);
            
            if($this->hasErrors($response, $ch)){
            
                exit();
            
            }
 
            $result = $this->getResult($ch, $response);
            
            curl_close($ch);

        } catch (Exception $e) {
    
            $this->log($e->getMessage());
    
        }
        
        return $result;

    }
    
    public function upload($file, $filename, $params, $url)
    {
        $result = null;
        
        $filesize = $file['size'];

        $file_path = getcwd() ."/".$filename;
        
        move_uploaded_file($file['tmp_name'], $file_path);
        
        try {
            
            $ch = curl_init();
            
            $headers = array("Content-Type:multipart/form-data"); 
            
            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => true,
                CURLOPT_POST => 1,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $params,
                CURLOPT_INFILESIZE => $filesize,
                CURLOPT_RETURNTRANSFER => true
            );
            
            curl_setopt_array($ch, $options);
            
            $response = curl_exec($ch);
            
            if($this->hasErrors($response, $ch)){
            
                exit();
            
            }
 
            $result = $this->getResult($ch, $response);
            
            curl_close($ch);
 
        } catch (Exception $e) {
            
            $this->log($e->getMessage());
            
        }
        
        return $result;
    }
    
    public function output($val){
        echo '<pre>';
        print_r($val);
        echo  '</pre>';
    }
    
    public function makeDirectory($dir, $mode = 0777) //Not implemented.
    {
        if (is_dir($dir) || @mkdir($dir,$mode)){
            return getcwd() . DIRECTORY_SEPARATOR . $dir;
        }
        if (!$this->makeDirectory(dirname($dir),$mode)){
            return null;
        }
        @mkdir($dir,$mode);
        return getcwd() . DIRECTORY_SEPARATOR . $dir;
    }
    
    public function fetchBoxFile($url, $filename, $file_id, $token){
        $time = microtime(true);
        $name = str_replace('.', '_', $time);
        $path = $this->makeDirectory($name);
        if(!$path){
            return;
        }
    
        $filepath = $path . DIRECTORY_SEPARATOR . $filename;
        //$filename = $path . DIRECTORY_SEPARATOR . $filename;
        $this->addToSession(array("myfilepath"=>$filepath));
    
        set_time_limit(0);
    
        # Open the file for writing...
        $this->fileHandle = fopen($filepath, 'w+');
    
        $headers = array("Authorization: Bearer $token");
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FILE, $this->fileHandle);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "MY+USER+AGENT"); //Make this valid if possible
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); # optional
        curl_setopt($ch, CURLOPT_TIMEOUT, -1); # optional: -1 = unlimited, 3600 = 1 hour
        curl_setopt($ch, CURLOPT_VERBOSE, false); # Set to true to see all the innards
    
        # Only if you need to bypass SSL certificate validation
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
        # Assign a callback function to the CURL Write-Function
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this,'writeFile'));
    
        # Exceute the download - note we DO NOT put the result into a variable!
        curl_exec($ch);
    
        # Close CURL
        curl_close($ch);
    
        # Close the file pointer
        fclose($this->fileHandle);
        
        $p = strtok('http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}", '?');
        
        $f = basename($p);
        
        $url = rtrim($p,$f);
    
        return $url . $name . "/" . $filename;
    }
    
    public function writeFile($cp, $data) {
        $len = fwrite($this->fileHandle, $data);
        return $len;
    }
    
    public function getResult($ch, $response){
        
        $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
        
        $this->result['header'] = substr($response, 0, $header_size);
        
        $this->result['body'] = substr( $response, $header_size );
        
        $this->result['http_code'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        
        $this->result['last_url'] = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
        
        return $this->result['body'];  //Only return the body
        
    }
    
    public function write($m)
    {
        if (isset($this->logfile)) {
            try {
                $fh = null;
                $fh = fopen($this->logfile, (file_exists($this->logfile)) ? 'a' : 'w');
                if (is_writable($this->logfile)) {
                    fwrite($fh, $this->eol);
                    fwrite($fh, $this->getTime());
                    fwrite($fh, $this->seperator);
                    fwrite($fh, $m);
                }else{
                    header('Status: 200', true, 200);
                    header('Content-Type: application/json');
                    $configError = array(
                        "error" => array("code" => 412,
                            "details" => array("Make sure this app has write permissions to log file."),
                            "message" => "Failure occured."
                        ));
                    
                    echo json_encode($configError);
                    
                    exit();
                }
                fclose($fh);
            } catch (Exception $e) {
                
                $this->log($e->getMessage());
                
            }
        } else {
            header('Status: 200', true, 200);
            header('Content-Type: application/json');
            $configError = array(
                "error" => array("code" => 412,
                    "details" => array("Make sure this app has write permissions to log file."),
                    "message" => "Failure occured."
                ));
            
            echo json_encode($configError);
            
            exit();
        }
    }
    
    
    public function replaceCRLF($lineString, $replaceString)
    {
        $filteredString = str_replace("\n", $replaceString, $lineString);
        
        $filteredString = str_replace("\r", $replaceString, $filteredString);
        
        return $filteredString;
    }
    
    public function log($message)
    {
        $message = $this->replaceCRLF($message, "__");
        
        $this->write($message);
        
        return;
    }
    public function getTime() {
        
        return date($this->timeFormat);
    }
    
    public function getParameters(){
        
        $result = array();
        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $result = array_merge($result,$_POST);
        
        }elseif ($_SERVER['REQUEST_METHOD'] === 'GET'){
            
            $result = array_merge($result,$_GET);
            
        }
        
        if(isset($_FILES)){
            /**
             * $target = getcwd() ."/".$_POST['filename'];
             * 
             * move_uploaded_file($_FILES["file"]["tmp_name"], $target); 
            **/
            
            $this->log(json_encode($_FILES));
            ////die('there is a file here');
            
            $mime_type = $_FILES['file'];
            
            $result = array_merge($result, array("file" =>  $_FILES['file']));
        }
        
        return $result;
        
    }
    
    public function addToSession($params){
        
        if(isset($_SESSION)){
        
            foreach($params as $key => $value){
                
                $_SESSION[$key] = $value;
                
            }
            
        }else{
            
            $this->log("addToSession::failed");
        }
    }
    
    function __destruct()
    {
        
    }
}

?>