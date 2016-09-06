<?php

/**
 * 
 * Box technology will send out these parameters which this class handles
 * 
 * {
	"configuration": "null",
	"access_token": "CXJEPT4YcFMMXvSG2623YIXtisaevLQ3",
	"expires_in": 4088,
	"restricted_to": [{
		"scope": "item_readwrite",
		"object": {
			"type": "file",
			"id": "69222313141",
			"file_version": {
				"type": "file_version",
				"id": "73057549829",
				"sha1": "54bb2f1998ce9ef68796c7a5584f9394b50a51f2"
			},
			"sequence_id": "1",
			"etag": "1",
			"sha1": "54c7cf1998ce9ef68796c7a5584f9394b50a51f2",
			"name": "sfpoints.geojson"
		}
	}],
	"refresh_token": "ehgxaj5HEBd1e3YzySvyp3veW0odBPg95DtJXZVX7rwFx5tt8e89PXa4ec7SEQwb",
	"token_type": "bearer"
* }
 */
namespace Esri\Auth;

use Esri\Utils\Utils;

class Box extends Utils
{
    public $api_key = "yum01mqat08e2ahdsjjj7dhroy1gkw11";
    
    public $client_id = "m5m01mqat08e2ahdsjjj7dhroy1gkw33";
    
    public $secret = "95J1hmWTNGgoIArOhyCzHHV4m6iYG7pj";
    
    public $auth_url = "https://api.box.com/oauth2/token";
    
    public $redirect_uri_route = 'https://<server>/boxauth/';
    
    public $access_token = null;
    
    public $refresh_token = null;
    
    public $auth_code = null;
    
    public $query_string = null;
    

    public function __construct($auth_code = null)
    {
        if(!isset($_GET) || !isset($_POST) || !empty($auth_code)){
            
            $this->auth_code = $auth_code;
            
            $this->addToSession(array('box_auth_code'=> $auth_code));
            
        }elseif (isset($_GET)  || isset($_POST)){
            
            $params = $this->getParameters();
            
            if(!empty($params['auth_code'])){
                
                $this->auth_code = $params['auth_code'];
                
                $this->addToSession(array('box_auth_code'=> $params['auth_code']));
            }
            
            if(!empty($params['refresh_token'])){
                
                $this->refresh_token = $params['refresh_token'];
            
            }
            
            if(!empty($params['access_token'])){
                
                $this->access_token = $params['access_token'];
            
            }
            
            $this->query_string = $params;
        }
        
//         if(empty($this->auth_code) && empty($this->refresh_token) && empty($this->refresh_token)){
            
//             die('Do not have the proper parameters to access Box.');
//         }

    }
    
    public function authorize(){
        
        $params = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->secret,
            'code' => $this->auth_code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirect_uri_route
        );

        $response = $this->post($params, $this->auth_url);
        
        $response = json_decode($response);
        
        if(array_key_exists("access_token",$response) && array_key_exists("refresh_token",$response)){

            $this->query_string = $response;
            
            $this->startCredentialSession();
            
        }else{
            
            die("Unable to authorize.");
        }
 
    }
    
    public function startCredentialSession(){

        $access_token = isset($this->query_string->access_token) ? $this->query_string->access_token : null;
        
        $expires_in = isset($this->query_string->expires_in) ? $this->query_string->expires_in : null;
        
        $refresh_token = isset($this->query_string->refresh_token) ? $this->query_string->refresh_token : null;
        
        $boxcreds = array(
            'box_access_token' => $access_token, 
            'box_expires_in' => $expires_in,
            'box_refresh_token' => $refresh_token
        );
        
        $this->addToSession($boxcreds);
        
        return;
        
    }
    
    public function outputProperties(){
        
        $props = (array)$this;
        
        $props = json_encode($props);
        
        $this->output($props);
            
    }

    function __destruct()
    {
        
    }
}