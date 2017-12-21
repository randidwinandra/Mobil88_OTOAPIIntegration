<?php
/**
 * GET /cars.php
 * Optional parameter: date_from={date_from} // Y-m-d formatted & validated
 * Required headers: Api-Token: Pa9M9X9KgOqz48MI4HAf286hueQuhqHi
 */
if ($_SERVER['HTTP_API_TOKEN'] != 'Pa9M9X9KgOqz48MI4HAf286hueQuhqHi') {
    $data['status'] = false;
    $data['message'] = 'Api-Token is missing or invalid';
    $data['data'] = [];

    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
// SQL Server Credentials
$serverName = "mobil88server.database.windows.net";
$connectionOptions = array(
    "Database" => "mobil88db",
    "Uid" => "admin88",
    "PWD" => "Mobil8888"
);
//Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

$where = "";
if (isset($date_from) && validateDate($date_from, 'Y-m-d')) {
    $where = "WHERE LAST_MODIFIED_DATE >= '{$date_from}' AND LAST_MODIFIED_DATE <= GETDATE()";
}

// Get cars
$tsql = "SELECT * FROM MI_CAR $where";

$getResults = sqlsrv_query($conn, $tsql);

$data = [];
if ($getResults === false) { // error
    $code = 400;
    $data['status'] = false;
    $data['message'] = 'Unable to fetch cars data';
    $data['data'] = sqlsrv_errors();
} else { // success
    $code = 200;
    $data['status'] = true;
    $data['message'] = 'Cars data has been successfully received';
    $data['data'] = [];
    while ($row = sqlsrv_fetch_array($getResults, SQLSRV_FETCH_ASSOC)) {
        $car_id = $row['ID'];
        $image_sql = "SELECT * FROM MI_CAR_IMAGE WHERE CAR_ID = '{$car_id}'";
        $imageResults = sqlsrv_query($conn, $image_sql);
        $image = []; // pre-emptied the image variable (image from last CAR_ID will be removed)
        if ($imageResults !== false) { // image is exist
            while ($img_row = sqlsrv_fetch_array($imageResults, SQLSRV_FETCH_ASSOC)) {
                $image[] = $img_row ;
            }
        }
        sqlsrv_free_stmt($imageResults);
        $row['IMAGES'] = $image;
        $data['data'][] = $row;
    }
}
sqlsrv_free_stmt($getResults);
echo ($data)
break;
// Send data
http_response_code($code);
header('Content-Type: application/json');
echo json_encode($data);

function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}
