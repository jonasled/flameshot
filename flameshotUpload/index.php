<?php
if($_SERVER['REQUEST_METHOD'] == "GET") {
    die("IMG Server for flameshot");
}

//check if flameshot header is present
if ($_SERVER['HTTP_AUTHORIZATION'] != "uploadedByFlameshot") {
    die("Forbidden");
}

function obfdeobf($id, $dec)
{
    $salt = 0x1838FFAA;
    $id &= 0xFFFFFFFF;
    if ($dec) {
        $id ^= $salt;
        $id = (($id & 0xAAAAAAAA) >> 1) | ($id & 0x55555555) << 1;
        $id = (($id & 0x0000FFFF) << 16) | (($id & 0xFFFF0000) >> 16);

        return $id;
    }

    $id = (($id & 0x0000FFFF) << 16) | (($id & 0xFFFF0000) >> 16);
    $id = (($id & 0xAAAAAAAA) >> 1) | ($id & 0x55555555) << 1;

    return $id ^ $salt;
}

function obfuscateId($id)
{
    return str_pad(base_convert(obfdeobf($id, false), 10, 36), 7, 0, STR_PAD_LEFT);
}


//get the full server path
$current_url_path = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$current_url_path = substr($current_url_path, -4) == ".php" ?
    implode("/", array_slice((explode("/", $current_url_path)), 0, -1)) : $current_url_path;

//generate the filename
$id = (new Datetime())->format('U') . random_int(1, 100000000);
$name = obfuscateId($id);


//check uploaded file size
if($_SERVER['CONTENT_LENGTH'] > 2000000) {
    die("Upload > 2MB");
}

// check if uploaded file is a image
$check = getimagesize("php://input");
if($check === false) {
    die("Not an image");
}

//write image
$fp = fopen("img/$name.png", 'w');
fwrite($fp, file_get_contents('php://input'));


//send response
$response = [
    "data" => [
        "link" => $current_url_path . "img/$name.png"
    ],
    "success" => true,
    "status" => 200
];

echo (json_encode($response));
