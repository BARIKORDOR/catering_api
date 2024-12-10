<?php


namespace App\Controllers;

use App\Plugins\Di\Injectable;
use App\Plugins\Http\Response as Status;

class BaseController extends Injectable {
  /**
     * validate all incominge request with api key
     */
    public function validateapi()
    {
        $apikey = "351d048f-5777-4789-8a24-f08b0e246266";
        $header = apache_request_headers();
        $API_KEY = (isset($header['api-key']) && !empty($header['api-key'])? $header['api-key'] : "");      
        if ($API_KEY != $apikey) {
            (new Status\BadRequest(['error' => 'Invalid request']))->send();
            die();
        }        
    }
    
    /**
     * sanitize input string for prevention of xss attack
     */
    public function sanitizeString($input)
    {
        return  htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    
}
