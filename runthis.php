<?php
require './api/googleapi.php';

$imgFolderId = '11PJEUZl8QmZlNSl23_AfF3cjxKa-wgJH';
$spreadsheetId = '1a1DYrNYKEh7Tsult8OvAMMJ_a2hUHIbq0SXyrRFPrPI';
$rootFolderId = '1mJB35J8pWiUYVChB7NLuA8neHp8xwrvr';
$newImgFolder = createFolder($rootFolderId, 'image-output-1204-MattKang', $drService); 

$imgFiles = getImages($imgFolderId, $drService);
$productNames = getProducts($spreadsheetId, $shService);

$validIds = findMatch($productNames, $imgFiles);

echo "**Downloading: ";
foreach ($validIds as $key => $value) {
    downloadImage($value["name"], $value["id"], $drService);
}

echo "**Uploading: ";
foreach ($validIds as $key => $value) {
    uploadImage($value["name"], $newImgFolder, $drService);
}

insertIntoSheet($validIds, $shService);

echo "\r\n==Task Complete==";

// Returns all image file names and Ids
function getImages($parentId, $service) {
    $imgFiles = [];
    $optParams = array(
    'pageSize' => 200,
    'fields' => "nextPageToken, files(id,name,parents)",
    'q' => "'".$parentId."' in parents"
   );

   $results = $service->files->listFiles($optParams);

   // Loops through subfolders to get the image files
   foreach ($results->getFiles() as $subfolder) {
       $subfolderId = $subfolder->getId();

       $listParams = array(
           'pageSize' => 200,
           'fields' => "nextPageToken, files(id,name,fileExtension,mimeType,parents)",
           'q' => "'".$subfolderId."' in parents"
       );
       $res = $service->files->listFiles($listParams);
       

       // Puts the images in an array
       // key = name, value = id
       foreach ($res->getFiles() as $image) {

           if ($image->getMimeType() == 'image/jpeg') {
               $imgFiles[strstr($image->getName(), '.', true)] = $image->getId();
           }
       }
   }
   return $imgFiles;
}

// Returns product names from google sheets
function getProducts($sheetId, $service) {
    $range = 'Sheet1!A2:A';
    $response = $service->spreadsheets_values->get($sheetId, $range);
    $values = $response->getValues();

    foreach ($values as $row) {
        $productNames[] = $row[0]; 
    }
    return $productNames;
}

// Changes string to lowercase, removes special characters, and changes spaces to hyphens
function renameStr($str) {
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9 -]+/', '', $str);
    $str = str_replace(' ', '-', $str);
    return trim($str, '-');
}

// Download selected image from google drive
function downloadImage($imgName, $imgId, $service) {
    $newFileName = renameStr($imgName);
    $image = $service->files->get($imgId, array("alt" => "media"));

    $handle = fopen("./download/".$newFileName.".jpg", "w+"); 
    echo $imgName . ", ";
    while (!$image->getBody()->eof()) { 
        fwrite($handle, $image->getBody()->read(1024)); 
    }
    fclose($handle);
}

function uploadImage($imgName, $folderId, $service) {
    $imgName = renameStr($imgName);
    $image = new Google_Service_Drive_DriveFile();
    $image->setName($imgName.".jpg");
    $image->setParents([$folderId]);
    $image->setMimeType('image/jpeg');

    $data = file_get_contents("./download/".$imgName.".jpg");
    echo $imgName . ", ";
    $createdFile = $service->files->create($image, array(
      'data' => $data,
      'mimeType' => 'image/jpeg',
      'uploadType' => 'multipart'
    ));
}

// Finds products that have images and put them into a new array
function findMatch($productNames, $imgFiles) {
    $newArray = [];
    // Loops through images retrieved from google drive
    foreach ($imgFiles as $imgName => $imgId) {
        if(stripos($imgName, 'pieces') !== false) {
            $imgName = strstr($imgName, ' (', true);
        }
        // Loops through sheets product names to see if there's a match
        foreach ($productNames as $index => $prd) {
            if (preg_match('/pcs|pieces/i', $prd)) {
                $prd = strstr($imgName, ' (', true);
            }
            if($prd === $imgName) {
                $newArray[$index + 2] = ["name" => $prd, "id" => $imgId];
            }
        }
    }
    return $newArray;
}

// Inserts specified value into google spreadsheet
function insertIntoSheet($validIds, $service) {
    $sheetId = '1a1DYrNYKEh7Tsult8OvAMMJ_a2hUHIbq0SXyrRFPrPI';
    
    $data = [];
        foreach ($validIds as $key => $value) {
            $val = renameStr($value["name"]).".jpg";
            $data[] = ["range"=> "Sheet1!B".$key,
                       'values'=> array(
                            array($val)
                        )];
        }
        
    $valueInputOption = 'RAW';

    $body = new Google_Service_Sheets_BatchUpdateValuesRequest([
        'valueInputOption' => 'RAW',
        'data' => $data
    ]);
    $result = $service->spreadsheets_values->batchUpdate($sheetId, $body);
}

// Creates google drive folder in specified location and returns folder Id
// Use batch request to not exceed write request quota
function createFolder($parentId, $folderName, $service) {
    $folder = new Google_Service_Drive_DriveFile();
    $folder->setName($folderName);
    $folder->setParents([$parentId]);
    $folder->setMimeType('application/vnd.google-apps.folder');

    $createdFolder = $service->files->create($folder);

    return $createdFolder->getId();
}
?>
