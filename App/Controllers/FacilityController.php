<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Plugins\Http\Response as Status;
use App\Models\facility;

class FacilityController extends BaseController
{
     /**
     * Method to list the Facilities
     */
    public function index()
    {
        // Validate and sanitize cursor
        // $cursor = isset($_GET['cursor']) ? intval($_GET['cursor']) : null;
        $cursor = $_GET['cursor'];

        // Validate and sanitize limit
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        if ($limit <= 0) {
            (new Status\BadRequest(['message' => 'Limit should be a positive number']))->send();
        }
        //validate search
        $search = $_GET['search'] ?? "";

        // Fetch facility details with cursor pagination   
        $facilities = new facility;
        $result =  $facilities->getFacilityDetails($cursor, $limit, $search);

        // Extract the last facility's ID as the cursor for the next page   
        $nextCursor = $result[array_key_last($result)]['facility_id'] ?? null;

        // Send statuses
       (new Status\Ok(['data' => $result, "next_cursor" => $nextCursor]))->send();
    }

    /**
     * Method to Create Facility API    
     */
    public function create()
    {
        // Get the data from the request body
        $data = json_decode(file_get_contents('php://input'), true);             
        if ($data) {                   
            //data for insertion 
            $facility = new facility();
            $InsertData = $facility->facilityData($data);
            if($InsertData){
             // Respond with 200 (OK):
              (new Status\Ok(['message' => 'Added Successfully!']))->send();   
            }            
        }else{
            (new Status\BadRequest(['message' => 'Whoops!. Something is wrong']))->send();  
        }
    }
}
 
    
