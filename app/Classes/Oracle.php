<?php

namespace App\Classes;
use Illuminate\Support\Facades\DB;
use Doctrine\DBAL\DriverManager;





class Oracle
{
    private $connection;
    
    public function __construct()
    {
      
        $this->connection = self::connect();
    }


    public function connect()
    {
        $conn=DB::connection('oracle');
        if (!$conn) {
            var_dump($conn );
        }
         
        return $conn;
    }

    public function testConnection(){
       try {
    
    $result = DB::connection('oracle')->select("select * from app_config where tag='lasm'");
       foreach ($result as $row) {
        print_r($result);
       
    }
} catch (\Exception $e) {
    dd('Oracle connection failed: ' . $e->getMessage());
}

    }


    public function query($sql, $bind = null)
    {
        $stmt = DB::select($sql);
        return $stmt;
        
    }

    // fetch one record from db
    public function fetch_one($sql)
    {   $stmt = DB::selectOne($sql);
        return $stmt;
       
    }

   
    public function execute($sql, $bind = null)
    {
        $stmt = DB::statement($sql);
        return $stmt;
       
    }

}
