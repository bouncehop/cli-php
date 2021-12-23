<?php
/**

 * Returns index of closest matched Image's index.

 * 

 * Using the keywords array of images and product name's keywords, 

 * calculate the highest score(=closest Match) image and return the index of image list 

 *

 * @param [[string]] $imageNameKeywords Array The keywords array of images.

 * @param [string] $productNameKeywords The keywords of a product.

 * @return {number} $indexOfHighestScore Index of closest matched Image, if there is no matched Image, it returns -1.

 */

function findClosestMatchedImageIndex($imageNameKeywordsArray, $productNameKeywords)

{

    $highestScore = 0;

    $indexOfHighestScore;



    foreach($imageNameKeywordsArray as $indexOfImage => $imageNameKeywords)

    {

        $score = 0;

        foreach($productNameKeywords as $keyword)

        {

            // if there is a keyword of product in the Keywords of Image, increase score.

            if(in_array(strtolower($keyword), array_map('strtolower', $imageNameKeywords)))

            {

                $score += 1;

            }

        }



        // Comparing with previous the highest score

        if($highestScore < $score )

        {

            $highestScore = $score;

            $indexOfHighestScore = $indexOfImage;

        }

    }



    // return the index of image which got the highest score

    // if the highest score is zero, consider it as no matched result.

    if($highestScore== 0)

    {

        // not found

        return -1;

    }

    else 

    {

        return $indexOfHighestScore;

    }

}



/**

 * Create and Return the Matched Image List using $imageList, $productList.

 * 

 * @return [string] $closestMatchedImageList The list of matched image. if there is no matched image, it it empty string "".

 */

function createMatchedImageList()

{

    global $imageList, $productList;



    $closestMatchedImageList = [];

    // create the keywords of images array

    $imageNameKeywordsArray = createImageNameKeywordsArray();



    foreach($productList as $productName)

    {

        // replace non char to space

        $trimedProductName = preg_replace('/[^a-zA-Z0-9\']/', ' ', $productName);

        // separate the product name by space

        $productNameKeywords = explode(" ", $trimedProductName);

        

        // find the closest matched image index 

        $imageIndex = findClosestMatchedImageIndex($imageNameKeywordsArray, $productNameKeywords);



        if($imageIndex != -1)

        {

            print($productName + " is Matched with " + $imageList[$imageIndex] + "\n");

            array_push($closestMatchedImageList, $imageList[$imageIndex]);

        }

        else 

        {

            print($productName + " is Matched with nothing \n");

            array_push($closestMatchedImageList, "");

        }

    }



    return $closestMatchedImageList;


}



/**

 * Returns trimed Image File Name.

 *  *

 * @param {string} $oldFileName The original string of file name.

 * @return {string} $newFileName Index of closest matched Image.

 */

function trimImageFileName($oldFileName) {

    // ToLowerCase

    $newFileName = strtolower($oldFileName);

    // replace all spaces to -

    $newFileName = str_replace(' ', '-', $newFileName);

    // remove non char except space and .

    $newFileName = preg_replace('/[^a-zA-Z0-9\.\-\']/', '', $newFileName);

    return $newFileName;


}

?>