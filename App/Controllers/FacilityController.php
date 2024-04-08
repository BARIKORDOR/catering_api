<?php

namespace App\Controllers;

use App\Controllers\BaseController;
// use App\Plugins\Db\Db;
use App\Plugins\Http\Exceptions;
use App\Plugins\Http\Response as Status;
use PDO;

class FacilityController extends BaseController {
    
    public function __construct()
    {      
        SELF::validateapi();       
    }

    public function index()
    {   

         // Pagination parameters
         $page = (isset($_REQUEST['page_number']) && !empty($_REQUEST['page_number']) ? SELF::sanitizestring($_REQUEST['page_number']) : 1);; // Current page
         $perPage = 10; // Number of items per page
         $offsets = ($page - 1) * $perPage;
         $where = "";
         $countBind = array();
 
         // Search term (for the name and tag_name columns)
         $searchTerm = (isset($_REQUEST['search']) && !empty($_REQUEST['search']) ? SELF::sanitizestring($_REQUEST['search']) : "");
 
         // SQL query with pagination and like condition
         if (!empty($searchTerm)) {
             $where = '  WHERE f.name LIKE :searchTerm OR tag.tag_name LIKE :searchTerm ';
             //$bind[':searchTerm'] = '%' . $searchTerm . '%'; 
 
             // Bind values for search term
             $countBind = array(':searchTerm' => '%' . $searchTerm . '%');
         }
 
         // get data 
         $query = "SELECT f.facility_id, f.name AS facility_name, tag.tag_id, 
         tag.tag_name, loc.location_id, loc.city, loc.address, loc.zip_code,
          loc.country_code, loc.phone_number 
          FROM facility f 
          LEFT JOIN facility_Tag ft ON f.facility_id = ft.facility_id 
          LEFT JOIN tag ON ft.tag_id = tag.tag_id 
          LEFT JOIN location loc ON f.location_id = loc.location_id 
          WHERE f.name LIKE :searchTerm OR tag.tag_name LIKE :searchTerm 
          LIMIT " . (int)$perPage . " OFFSET " . (int)$offsets;
 
         // Bind values for search term, pagination parameters
         $bind = array(
             ':searchTerm' => '%' . $searchTerm . '%' // Surround search term with '%' for a partial match
         );
 
         // Execute the query
         $this->db->executeQuery($query, $bind);
 
         // Fetch all rows as an associative array
         $results = $this->db->getStatement()->fetchAll(PDO::FETCH_ASSOC);
 
         // SQL query to count total data rows (without LIMIT and OFFSET)
         $countQuery = "SELECT COUNT(*) 
                FROM facility f 
                LEFT JOIN facility_Tag ft ON f.facility_id = ft.facility_id 
                LEFT JOIN tag ON ft.tag_id = tag.tag_id 
                LEFT JOIN location loc ON f.location_id = loc.location_id 
                " . $where; 
         // Execute the count query
         $this->db->executeQuery($countQuery, $countBind);
 
         // Fetch total count
         $totalCount = $this->db->getStatement()->fetchColumn();
 
         // Respond with 200 (OK):
         (new Status\Ok(['data' => $results, "total_count" => $totalCount, "page_number" => $page, "per_page" => $perPage]))->send();
     
    }
    

    /**
     * Controller function to Create Facility API
     * @return void
     */
    public function create()
    {
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {    
        // Get the data from the request body
        $data = json_decode(file_get_contents('php://input'), true); 

          // Sanitize data to remove HTML tags        
       $facilityname =  htmlspecialchars(strip_tags($data['name']));
       $tag_name =  htmlspecialchars(strip_tags($data['tag_name']));
       $datatime = date('Y-m-d H:i:s');
       
        
        //Get Tag ID
        $TagId = SELF::gettag($tag_name); 
        if (empty($TagId)) {
            (new Status\Ok(['message' => 'Tag id is not avaliable']))->send();
            die();
        }
        // Get the Location ID    
        $LocationId = SELF::setlocation($data);
        if (empty($LocationId)) {
            (new Status\Ok(['message' => 'Location Id is not avaliable']))->send();
            die();
        }

        //Insert in Facility table
        $query =   "INSERT INTO facility (name, creation_date, location_id)
                    VALUES (?,?,?)";
        $bind = array( $facilityname, $datatime, $LocationId);  
        // Execute query
         $result = $this->db->executeQuery($query, $bind);
        $FacilityId = $this->db->getLastInsertedId(); 
        if (empty($FacilityId)) {
            (new Status\Ok(['message' => 'Somthing went wrong']))->send();
            die();
        }

        //Insert in Facility tag table
        $query =   "INSERT INTO facility_tag (facility_id,tag_id)
        VALUES (?,?)";
        $bind = array( $FacilityId, $TagId );
        $result = $this->db->executeQuery($query, $bind);   
      
    // Respond with 200 (OK):
        (new Status\Ok(['message' => 'Added Successfully!']))->send();
    } 
    else {
        (new Status\Ok(['message' => 'Whoops! Something went wrong!']))->send();
        }
}
/**
 * Tag Methods
 */

function gettag($tagName)
{
    $tag_query = "SELECT tag_id from tag where tag_name = '".$tagName."'";
    $bind = array();
    $this->db->executeQuery($tag_query, $bind);    
    $results = $this->db->getStatement()->fetch(PDO::FETCH_ASSOC);
    // print_r( $results);die();
    if(isset($results['tag_id']) && !empty($results['tag_id']))
    {
        return $results['tag_id'];
    }
    else 
    {
        $query =   "INSERT INTO tag (tag_name)
                     VALUES (?)";
         $bind = array($tagName);     
         $this->db->executeQuery($query, $bind);    
         return $this->db->getLastInsertedId(); 

    }
}
/**
 * Location Methods
 */

function setlocation($data)
{  
    //Fetching required data for Location 
    $address = isset($data['address']) && !empty($data['address']) ? SELF::sanitizestring($data['address']) : "";
    $city = isset($data['city']) && !empty($data['city']) ? SELF::sanitizestring($data['city']) : "";
    $zip_code = isset($data['zip_code']) && !empty($data['zip_code']) ? SELF::sanitizestring($data['zip_code']) : "";
    $phone_number = isset($data['phone_number']) && !empty($data['phone_number']) ? SELF::sanitizestring($data['phone_number']): "";
    $country_code = isset($data['country_code']) && !empty($data['country_code']) ? SELF::sanitizestring($data['country_code']) : "";
    $currentdatetime = date('Y-m-d H:i:s');
   //Query to insert in Location 
   $query =   "INSERT INTO location (city,address,zip_code,country_code,phone_number,creation_date)
        VALUES (?,?,?,?,?,?)";
    $bind = array($city,$address,$zip_code,$country_code,$phone_number,$currentdatetime);   
    //Execute Query  
    $this->db->executeQuery($query, $bind);    
    return $this->db->getLastInsertedId(); 
   
}
}