<?php

namespace App\Controllers;
use App\Plugins\Db\Adapters\Mysql;
use App\Plugins\Http\Response as Status;
use App\Plugins\Http\Exceptions;
use App\Plugins\Db\Db;
use PDO;

class IndexController extends BaseController {

   
    /**
     * Controller function used to test whether the project was set up properly.
     * @return void
     */
    public function test() {
        // Respond with 200 (OK):
        (new Status\Ok(['message' => 'Hello world!']))->send();
    }

    
}
