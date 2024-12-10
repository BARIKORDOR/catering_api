<?php

namespace App\Models;

use App\Plugins\Di\Injectable;
use PDO;
use App\Plugins\Http\Response as Status;

class facility extends Injectable
{
    /**
     * Listing Facility Details      * 
     */
    public function getFacilityDetails($cursor = '', $limit = 10, $search = "")
    {

        //Query to insert in Facility
        $query = "SELECT f.facility_id, f.name AS facility_name, tag.tag_id, 
      tag.tag_name, loc.location_id, loc.city, loc.address, loc.zip_code,
      loc.country_code, loc.phone_number 
      FROM facility f 
      LEFT JOIN facility_Tag ft ON f.facility_id = ft.facility_id 
      LEFT JOIN tag ON ft.tag_id = tag.tag_id 
      LEFT JOIN location loc ON f.location_id = loc.location_id 
      WHERE f.name LIKE :search1 OR tag.tag_name LIKE :search2 
        AND f.facility_id > :cursor 
      ORDER BY f.facility_id ASC LIMIT :limit";

        $bind = array(
            ':search1' => '%' . $search . '%',
            ':search2' => '%' . $search . '%',
            ':limit' => $limit,
            ':cursor' => $cursor,
        );

        // Execute the query
        $this->db->executeQuery($query, $bind);
        // Fetch all rows as an associative array
        $facilities = $this->db->getStatement()->fetchAll(PDO::FETCH_ASSOC);
        return $facilities;
    }

    public function facilityData(array $data)
    {
       
        $validatedRequest = self::validateRequest($data);
        if ($validatedRequest) {       
            
            $TagId = self::getTagID($data['tag_name']);
            $LocationId = self::setLocation($data);
            if ($LocationId) {
                $datatime = date('Y-m-d H:i:s');
                // Insert into Facility
                $query = "INSERT INTO facility (name, creation_date, location_id)
                    VALUES (?, ?, ?)";
                $bind = array(
                     $data['name'],  $datatime,  $LocationId
                    );
                // Execute query
                $result = $this->db->executeQuery($query, $bind);
                $FacilityId = $this->db->getLastInsertedId();
                if ($TagId) {
                    //Insert in Facility tag table            
                    $query = "INSERT INTO facility_tag (facility_id,tag_id)
                VALUES (?,?)";
                    $bind = array($FacilityId, $TagId
                    );
                    $this->db->executeQuery($query, $bind);
                }
                return true;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Tag Methods
     */
    public function getTagID(string $tagName): int
    {
        //Input Filtering
        $tag_query = "SELECT tag_id from tag where tag_name = :tagname1";
        $bind = array(':tagname1' => $tagName);
        $this->db->executeQuery($tag_query, $bind);
        $results = $this->db->getStatement()->fetch(PDO::FETCH_ASSOC);       
        if (isset($results['tag_id']) && !empty($results['tag_id'])) {
            return $results['tag_id'];
        } else {
            $query = "INSERT INTO tag(tag_name)
                     VALUES (?) ON DUPLICATE KEY UPDATE tag_name=VALUES(tag_name)";
            // $bind = array($tagName); 
            $bind = array( $tagName);          
            //Execute Query  
            $this->db->executeQuery($query, $bind);
            return $this->db->getLastInsertedId();
        }
    }

    /**
     * To get location 
     * @param string $data   
     */
    public function setLocation($data): string
    {
        //Fetching required data for Location 
        $currentdatetime = date('Y-m-d H:i:s');
        //Query to insert in Location 
        $query = "INSERT INTO location (city,address,zip_code,country_code,phone_number,creation_date)
        VALUES (?,?,?,?,?,?)";
        $bind = array(
           $data['city'], $data['address'],$data['zip_code'], $data['country_code'], $data['phone_number'], $currentdatetime);
        //Execute Query  
        $this->db->executeQuery($query, $bind);
        return $this->db->getLastInsertedId();
    }
    /**
     * Validate Request
     */
    function  validateRequest($data)
    {
        $errors = [];
        if (!isset($data['name']) || empty($data['name'])) {
            $errors['name'] = "Facility name is required";
        }
        if (!isset($data['tag_name']) || empty($data['tag_name'])) {
            $errors['tag_name'] = "Tag name is required";
        }
        if (!isset($data['address']) || empty($data['address'])) {
            $errors['address'] = "Address is required";
        }
        if (!isset($data['city']) || empty($data['city'])) {
            $errors['city'] = "City name is required";
        }
        if (!isset($data['zip_code']) || empty($data['zip_code'])) {
            $errors['zip_code'] = "Zip code is required";
        } else if (!preg_match('/^[0-9]{5}$/', $data['zip_code'])) {    
            $errors['zip_code'] = "Zip Code must be 5 digits!";          
    }
        if (!isset($data['zip_code']) || empty($data['phone_number'])) {
            $errors['phone_number'] = "Phone number is required!";            
        } else if(!filter_var($data['phone_number'], FILTER_VALIDATE_INT)) {         
            $errors['phone_number'] = "Phone number is Invalid3!"; 
        }        
        if (!isset($data['country_code']) || empty($data['country_code'])) {
            $errors['country_code'] = "Country code is required";
        } else if (!preg_match('/^[0-9]{2}$/', $data['country_code'])) {    
            $errors['country_code'] = "Country Code must be 2 digits!";          
    }
        //  $city = filter_var($data['city'], FILTER_SANITIZE_STRING);
        if (!empty($errors)) {
            (new Status\BadRequest(['message' => $errors]))->send();
            exit();
        }
        return true;
    }
}
