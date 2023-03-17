<?php

namespace App\Http\Controllers;
use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Http\Request;
use Jfelder\OracleDB\Connectors\OracleConnector;



class pcashOracle extends OracleConnector implements ConnectorInterface
{

    public function connecting()
    {
        $connectionString = '10.0.0.162:1521/lapo.lapo-nigeria.org';
        $username = 'NOVAJI';
        $password = 'password';
        $conn = oci_connect($username, $password, $connectionString);
        
        if (! $conn) {
            $e = oci_error();
            throw new \Exception("Could not connect to Oracle: " . $e['message']);
        }
        var_dump($conn);
        return 'working';
    }

    public function oraclePcash(){
     
#echo 'Oracle..'; exit;
$conn = oci_connect('novaji', 'password', 'novaji-pcash.lapo-nigeria.org:1521/lapo');
if ($conn) {
   #print "Success, connected!\n";
   // Prepare the statement
$stid = oci_parse($conn, 'SELECT * FROM dept');
if (!$stid) {
    $e = oci_error($conn);
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Perform the logic of the query
$r = oci_execute($stid);
if (!$r) {
    $e = oci_error($stid);
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Fetch the results of the query
print "<table border='1'>\n";
while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    print "<tr>\n";
    foreach ($row as $item) {
        print "    <td>" . ($item !== null ? htmlentities($item, ENT_QUOTES) : "&nbsp;") . "</td>\n";
    }
    print "</tr>\n";
}
print "</table>\n";

oci_free_statement($stid);
#oci_close($conn);
} else {
   $m = oci_error();
   echo $m['message'], "\n";
}

oci_close($conn);
    }
}
