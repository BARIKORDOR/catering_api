<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Plugins\Http\Exceptions;
use App\Plugins\Http\Response as Status;
use PDO;
use PDOException;

class FacilityController extends BaseController
{

    public function __construct()
    {
        SELF::validateapi();
    }

    /**
     * @param int $cursor
     * @param int $limit
     * @param string $search
     */
    public function index()
    {

        // Validate and sanitize cursor
        $cursor = isset($_REQUEST['cursor']) ? intval($_REQUEST['cursor']) : null;
        if ($cursor !== null  && !is_int($cursor)) {
            (new Status\BadRequest(['message' => 'Invalid Cursor']))->send();
            die();
        }

        // Validate and sanitize limit
        $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 10;
        if ($limit <= 0) {
            (new Status\BadRequest(['message' => 'Limit should be a positive number']))->send();
        }
        //validate and sanitize search
        $search = (isset($_REQUEST['search']) && !empty($_REQUEST['search']) ? SELF::sanitizestring($_REQUEST['search']) : "");

        // Fetch facility details with cursor pagination
        $facilities = SELF::getFacilityDetails($cursor, $limit, $search);

        // Extract the last facility's ID as the cursor for the next page
        $nextCursor = null;
        if (!empty($facilities)) {

            $lastfacility = end($facilities);
            $nextCursor = $lastfacility['facility_id'];
        }

        // (new Status\Ok(['data' => $facilities]))->send();
        (new Status\Ok(['data' => $facilities, "next_cursor" => $nextCursor]))->send();
    }

    /**
     * Controller function to Create Facility API
     * @param string $name
     * @param string $tag_name
     */
    public function create()
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get the data from the request body
            $data = json_decode(file_get_contents('php://input'), true);
            $validatedRequest = SELF::ValidateRequest($data);
            if (!empty($data) && $validatedRequest) {
                // validate and clean data    
                $facilityname = isset($data['name']) && !empty($data['name']) ? SELF::sanitizestring($data['name']) : "";
                $tag_name =    isset($data['tag_name']) && !empty($data['tag_name']) ? SELF::sanitizestring($data['tag_name']) : "";
                $datatime = date('Y-m-d H:i:s');

                //Get Tag ID
                $TagId = SELF::gettag($tag_name);
                if (empty($TagId)) {
                    (new Status\BadRequest(['message' => 'Tag id is not avaliable']))->send();
                    die();
                }
                // Get the Location ID    
                $LocationId = SELF::setlocation($data);
                if (empty($LocationId)) {
                    (new Status\BadRequest(['message' => 'Location Id is not avaliable']))->send();
                    die();
                }

                //Insert in Facility table
                $query =   "INSERT INTO facility (name, creation_date, location_id)
                    VALUES (?,?,?)";
                $bind = array($facilityname, $datatime, $LocationId);
                // Execute query
                $result = $this->db->executeQuery($query, $bind);
                $FacilityId = $this->db->getLastInsertedId();
                if (empty($FacilityId)) {
                    (new Status\BadRequest(['message' => 'Somthing went wrong']))->send();
                    die();
                }

                //Insert in Facility tag table            
                $query =   "INSERT INTO facility_tag (facility_id,tag_id)
                VALUES (?,?)";
                $bind = array($FacilityId, $TagId);
                $this->db->executeQuery($query, $bind);

                // Respond with 200 (OK):
                (new Status\Ok(['message' => 'Added Successfully!']))->send();
            } else {
                // Respond with 400 (BadRequest):
                (new Status\BadRequest(['Error' => 'No data is entered in Body!']))->send();
                die();
            }
        } else {
            // Respond with 400 (BadRequest):
            (new Status\BadRequest(['message' => 'Whoops! Something went wrong!']))->send();
        }
    }

    /**
     * Function to Get Facility details
     * 
     */
    function getFacilityDetails($cursor = null, $limit = 10, $search = "")
    {


        $query = "SELECT f.facility_id, f.name AS facility_name, tag.tag_id, 
          tag.tag_name, loc.location_id, loc.city, loc.address, loc.zip_code,
          loc.country_code, loc.phone_number 
          FROM facility f 
          LEFT JOIN facility_Tag ft ON f.facility_id = ft.facility_id 
          LEFT JOIN tag ON ft.tag_id = tag.tag_id 
          LEFT JOIN location loc ON f.location_id = loc.location_id
          WHERE f.name LIKE :search OR tag.tag_name LIKE :search ";
        if ($cursor) {
            $query .= ' and f.facility_id > :cursor ';
        }
        $query .= "ORDER BY f.facility_id ASC LIMIT $limit";

        $bind = array(':cursor' => $cursor, ':search' => '%' . $search . '%');
        // Execute the query
        $reult = $this->db->executeQuery($query, $bind);

        // Fetch all rows as an associative array
        $facilities = $this->db->getStatement()->fetchAll(PDO::FETCH_ASSOC);
        return $facilities;
    }
   
    /**
     * Tag Methods
     */

    function gettag($tagName)
    {
        try {
            $tag_query = "SELECT tag_id from tag where tag_name = '" . $tagName . "'";
            $bind = array();
            $this->db->executeQuery($tag_query, $bind);
            $results = $this->db->getStatement()->fetch(PDO::FETCH_ASSOC);
            // print_r( $results);die();
            if (isset($results['tag_id']) && !empty($results['tag_id'])) {
                return $results['tag_id'];
            } else {
                $query =   "INSERT INTO tag (tag_name)
                     VALUES (?)";
                $bind = array($tagName);
                $this->db->executeQuery($query, $bind);
                return $this->db->getLastInsertedId();
            }
        } catch (PDOException $e) {
            // GetMessage to throw
            $ErrorMessage = $e->getMessage(); // Get the error message from the exception
            // Log the error or return it to the client   
            (new Status\BadRequest(['Error' => $ErrorMessage]))->send();
        }
    }
    /**
     * To get location 
     * @param string $address
     * @param string $city
     * @param string $zip_code
     * @param string $phone_number
     * @param string $country_code
     */

    function setlocation($data)
    {
        try {
            //Fetching required data for Location 
            $address = isset($data['address']) && !empty($data['address']) ? SELF::sanitizestring($data['address']) : "";
            $city = isset($data['city']) && !empty($data['city']) ? SELF::sanitizestring($data['city']) : "";
            $zip_code = isset($data['zip_code']) && !empty($data['zip_code']) ? SELF::sanitizestring($data['zip_code']) : "";
            $phone_number = isset($data['phone_number']) && !empty($data['phone_number']) ? SELF::sanitizestring($data['phone_number']) : "";
            $country_code = isset($data['country_code']) && !empty($data['country_code']) ? SELF::sanitizestring($data['country_code']) : "";
            $currentdatetime = date('Y-m-d H:i:s');
            //Query to insert in Location 
            $query =   "INSERT INTO location (city,address,zip_code,country_code,phone_number,creation_date)
        VALUES (?,?,?,?,?,?)";
            $bind = array($city, $address, $zip_code, $country_code, $phone_number, $currentdatetime);
            //Execute Query  
            $this->db->executeQuery($query, $bind);
            return $this->db->getLastInsertedId();
        } catch (PDOException $e) {
            // GetMessage to throw
            $ErrorMessage = $e->getMessage(); // Get the error message from the exception
            // Log the error or return it to the client   
            (new Status\BadRequest(['Error' => $ErrorMessage]))->send();
        }
    }
     /**
     * Validate Request
     */
    function  ValidateRequest($data)
    {
        $error = [];
        $facilityname = isset($data['name']) && empty($data['name']) ? "Facilty name is required" : "";
        $tag = isset($data['tag']) && empty($data['tag']) ? "Tag name is required" : "";
        $address = isset($data['address']) && empty($data['address']) ? "Address name is required" : "";
        $city = isset($data['city']) && empty($data['city']) ? "City name is required" : "";
        $zip_code = isset($data['zip_code']) && empty($data['zip_code']) ? "Zip code is required" : "";
        $phone_number = isset($data['phone_number']) && empty($data['phone_number']) ? "Phone number is required" : "";
        $country_code = isset($data['country_code']) && empty($data['country_code']) ? "Country code is required" : "";

        // Assign error messages to the $error array if fields are empty
        if (!empty($facilityname)) {
            $error['name'] = $facilityname;
        }
        if (!empty($tag)) {
            $error['tag'] = $tag;
        }
        if (!empty($address)) {
            $error['address'] = $address;
        }
        if (!empty($city)) {
            $error['city'] = $city;
        }
        if (!empty($zip_code)) {
            $error['zip_code'] = $zip_code;
        }
        if (!empty($phone_number)) {
            $error['phone_number'] = $phone_number;
        }
        if (!empty($country_code)) {
            $error['country_code'] = $country_code;
        }
        if (count($error) > 0) {
            // Respond with 400 (BadRequest):
            (new Status\BadRequest(['message' =>  $error]))->send();
            die();
        } else {
            return true;
        }
    }
}
