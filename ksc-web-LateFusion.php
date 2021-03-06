<?php

/**
 * 		@file 	ksc-web-LateFusion.php
 * 		@brief 	Perform late fusion online
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 11 Jul 2014.
 */
 
// Update Jul 11, 2014
/**
1. Adding more normalization method - sigmoid function (default) and z-score
2. Auto adding fusion config to runID
3. Fusion method: first, compute shot score (= max score of keyframes), then normalize score, and fuse
4. OutputRunID - suffix will be added (R1, R2, weights, normalization method)
5. OutputRun config file is saved.
*/ 
 
require_once "ksc-AppConfig.php";
require_once "ksc-Tool-EvalMAP.php";

// //////////////// START //////////////////

$arNormMethodDesc = array(
0 => "Simple Sigmoid Function (1/(1+exp(-t)))",
1 => "Z-Score by Using Mean and Std");  // shown better perf compared to sigmoid

$nAction = 0;
if (isset($_REQUEST['vAction'])) {
    $nAction = $_REQUEST['vAction'];
}

if ($nAction == 0) {
    printf("<P><H1>Late Fusion</H1>\n");
    printf("<P><H1>Select TVYear</H1>\n");
    printf("<FORM TARGET='_blank'>\n");
    printf("<P>TVYear<BR>\n");
    // load xml file
    printf("<SELECT NAME='vTVYear'>\n");
    printf("<OPTION VALUE='2013'>2013</OPTION>\n");
    printf("<OPTION VALUE='2012'>2012</OPTION>\n");
    printf("<OPTION VALUE='2011'>2011</OPTION>\n");
    printf("</SELECT>\n");
    
    printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='1'>\n");
    printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
    printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
    printf("</FORM>\n");
    exit();
}

$arVideoPathLUT[2012] = "tv2012/subtest2012-new";
$arVideoPathLUT[2013] = "tv2013/test2013-new";

$nTVYear = $_REQUEST['vTVYear'];
$szTVYear = sprintf("tv%d", $nTVYear);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
$szMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szTVYear);

// ins.topics.2013.xml
$szFPInputFN = sprintf("%s/ins.topics.%d.xml", $szMetaDataDir, $nTVYear);
$arQueryList = loadQueryDesc($szFPInputFN);

$szFPInputFN = sprintf("%s/ins.search.qrels.%s.csv", $szMetaDataDir, $szTVYear);
$arQueryListCount = array();
if (file_exists($szFPInputFN)) {
    loadListFile($arList, $szFPInputFN);
    foreach ($arList as $szLine) {
        $arTmp = explode("#$#", $szLine);
        $szQueryIDx = trim($arTmp[0]);
        $nCount = intval($arTmp[1]);
        $arQueryListCount[$szQueryIDx] = $nCount;
    }
}

$szVideoPath = $arVideoPathLUT[$nTVYear];

$szResultDir = sprintf("%s/result", $gszRootBenchmarkDir);
$arDirList = collectDirsInOneDir($szResultDir);
sort($arDirList);

// print_r($arQueryListCount);
// show form
if ($nAction == 1) {
    printf("<P><H1>Late Fusion</H1>\n");
    printf("<FORM TARGET='_blank'>\n");
    printf("<P>Query<BR>\n");
    // load xml file
    printf("<SELECT NAME='vQueryID'>\n");
    foreach ($arQueryList as $szQueryID => $szText) {
        
        if (isset($arQueryListCount[$szQueryID])) {
            printf("<OPTION VALUE='%s#%s'>%s - %d</OPTION>\n", $szQueryID, $szText, $szText, $arQueryListCount[$szQueryID]);
        } else {
            printf("<OPTION VALUE='%s#%s'>%s</OPTION>\n", $szQueryID, $szText, $szText);
        }
    }
    printf("</SELECT>\n");
    
    printf("<P>View GroundTruth<BR>\n");
    printf("<SELECT NAME='vShowGT'>\n");
    printf("<OPTION VALUE='0'>No</OPTION>\n");
    printf("<OPTION VALUE='1'>Yes</OPTION>\n");
    printf("</SELECT>\n");
    
    printf("<P>RunID1<BR>\n");
    printf("<SELECT NAME='vRunID1'>\n");
    foreach ($arDirList as $szRunID) {
        if (! strstr($szRunID, $nTVYear)) {
            continue;
        }
        printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szRunID, $szRunID);
    }
    printf("</SELECT>\n");
    
    printf("<P>RunID1-Weight<BR>\n");
    printf("<INPUT TYPE='TEXT' NAME='vWeightR1' VALUE='1'>\n");
    
    printf("<P>RunID2<BR>\n");
    printf("<SELECT NAME='vRunID2'>\n");
    foreach ($arDirList as $szRunID) {
        if (! strstr($szRunID, $nTVYear)) {
            continue;
        }
        printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szRunID, $szRunID);
    }
    printf("</SELECT>\n");
    
    printf("<P>RunID2-Weight<BR>\n");
    printf("<INPUT TYPE='TEXT' NAME='vWeightR2' VALUE='1'>\n");

    printf("<P>From<BR>\n");
    printf("<INPUT TYPE='TEXT' NAME='vFrom' VALUE='9068'>\n");

    printf("<P>To<BR>\n");
    printf("<INPUT TYPE='TEXT' NAME='vTo' VALUE='9098'>\n");

    printf("<P>Normalization Method for Scores<BR>\n");
    printf("<SELECT NAME='vNormMethod'>\n");
	printf("<OPTION VALUE='0'>%s</OPTION>\n", $arNormMethodDesc[0]);
    printf("<OPTION VALUE='1'>%s</OPTION>\n", $arNormMethodDesc[1]);
    printf("</SELECT>\n");

    
    printf("<P>Output Run - Suffix will be added, e.g XXX[R1=R2.1xw1=1.0-R2=R2.2xw2=1.0-Norm=0]<BR>\n");
    printf("<INPUT TYPE='TEXT' NAME='vOutRunID' VALUE='run_fusion%s'>\n", $nTVYear);
    
    printf("<P>PageID<BR>\n");
    printf("<INPUT TYPE='TEXT' NAME='vPageID' VALUE='1' SIZE=10>\n");
    
    printf("<P>Max Videos Per Page<BR>\n");
    printf("<INPUT TYPE='TEXT' NAME='vMaxVideosPerPage' VALUE='100' SIZE=10>\n");
    
    printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='2'>\n");
    printf("<P><INPUT TYPE='HIDDEN' NAME='vTVYear' VALUE='%s'>\n", $nTVYear);
    printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
    printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
    printf("</FORM>\n");
    exit();
}

// view query images
$szQueryIDz = $_REQUEST['vQueryID'];
$arTmp = explode("#", $szQueryIDz);
$szQueryID = trim($arTmp[0]);
$szText = trim($arTmp[1]);

$szRunID1 = $_REQUEST['vRunID1'];
$szRunID2 = $_REQUEST['vRunID2'];
$fWeight1 = floatval($_REQUEST['vWeightR1']);
$fWeight2 = floatval($_REQUEST['vWeightR2']);
$nQueryIDStart = intval($_REQUEST['vFrom']);
$nQueryIDEnd = intval($_REQUEST['vTo']);

$szCoreOutRunID = $_REQUEST['vOutRunID'];

// adding Jul 11, 2014
$nNormMethod = $_REQUEST['vNormMethod'];

// pick first 3 char of RunID as Code - usually 1.1, 1.2 are used to rank by priority
$szR1 = substr($szRunID1, 0, 3) ;
$szR2 = substr($szRunID2, 0, 3);
$szOutputRunSuffix = sprintf("R1=R%sxw1=%0.1f-R2=R%sxw2=%0.1f-Norm=%d", $szR1, $fWeight1, $szR2, $fWeight2, $nNormMethod);

$szOutRunID = sprintf("%s[%s]", $szCoreOutRunID, $szOutputRunSuffix);

$arLog = array();
$arLog [] = sprintf("Fusion run config");
$arLog [] = sprintf("Output Name: %s", $szOutRunID);
$arLog [] = sprintf("R1: %s - Weight: %0.1f", $szRunID1, $fWeight1);
$arLog [] = sprintf("R2: %s - Weight: %0.1f", $szRunID2, $fWeight2);
$arLog [] = sprintf("NormScoreMethod: %s", $arNormMethodDesc[$nNormMethod]);
$arLog [] = sprintf("TVYear: %s", $szTVYear);

// include both jpg and png file
$szQueryPatName = sprintf("query%s-new", $nTVYear);
$szFPQueryImgListFN = sprintf("%s/%s/%s.prg", $szMetaDataDir, $szQueryPatName, $szQueryID);
loadListFile($arQueryImgList, $szFPQueryImgListFN);
// print_r($arQueryImgList);
// ins.search.qrels.tv2011
$szFPNISTResultFN = sprintf("%s/ins.search.qrels.%s", $szMetaDataDir, $szTVYear);

if (file_exists($szFPNISTResultFN)) {
    $arNISTList = parseNISTResult($szFPNISTResultFN);
}

// for computing MAP online
$nTotalHits = sizeof($arNISTList[$szQueryID]);
$arAnnList = array();
foreach ($arNISTList[$szQueryID] as $szShotID) {
    $arAnnList[$szShotID] = 1;
}
/*
 * $szFPOutputFN = sprintf("%s/ins.search.qrels.%s.csv", $szMetaDataDir, $szTVYear); if(!file_exists($szFPOutputFN)) { $arTmpOutput = array(); foreach($arNISTList as $szQueryIDx => $arTmp) { printf("<P>Query [%s] - Count [%d]\n", $szQueryIDx, sizeof($arTmp)); $arTmpOutput[] = sprintf("%s#$#%s", $szQueryIDx, sizeof($arTmp)); } saveDataFromMem2File($arTmpOutput, $szFPOutputFN); }
 */
// //////////////// SHOW QUERY ///////////////////
$szRootKeyFrameDir = sprintf("%s/keyframe-5", $gszRootBenchmarkDir);
$szKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szTVYear);
$arOutput = array();
$arOutput[] = sprintf("<P><H1>RunID: %s<BR>\n", $szRunID);
$arOutput[] = sprintf("<P><H1>Query - %s</H1><BR>\n", $szText);
foreach ($arQueryImgList as $szQueryImg) {
    $szURLImg = sprintf("%s/%s/%s/%s.jpg", $szKeyFrameDir, $szQueryPatName, $szQueryID, $szQueryImg);
    // exit($szURLImg);
    $szRetURL = $szURLImg;
    $imgzz = imagecreatefromjpeg($szRetURL);
    $widthzz = imagesx($imgzz);
    $heightzz = imagesy($imgzz);
    
    // calculate thumbnail size
    $new_width = $thumbWidth = 100; // to reduce loading time
    $new_height = floor($heightzz * ($thumbWidth / $widthzz));
    
    // create a new temporary image
    $tmp_img = imagecreatetruecolor($new_width, $new_height);
    
    // copy and resize old image into new image
    // imagecopyresized($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
    
    // better quality compared with imagecopyresized
    imagecopyresampled($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
    // output to buffer
    ob_start();
    imagejpeg($tmp_img);
    $szImgContent = base64_encode(ob_get_clean());
    $arOutput[] = sprintf("<IMG  TITLE='%s - %s' SRC='data:image/jpeg;base64," . $szImgContent . "' />", $szQueryImg, $fScore);
    
    imagedestroy($imgzz);
    imagedestroy($tmp_img);
    // $arOutput[] = sprintf("<IMG SRC='%s' WIDTH='100' TITLE='%s'/> \n", $szURLImg, $szQueryImg);
}
$arOutput[] = sprintf("<P><BR>\n");

// // VERY SPECIAL ****

/*
 * $arWeightList = array( "9048" => 10, // small ROI "9049" => 2, // large ROI "9050" => 2, // large ROI "9051" => 2, // large ROI --> ??? not sure "9052" => 10, // small ROI "9053" => 10, // small ROI "9054" => 2, // large ROI "9055" => 10, // too small ROI "9056" => 2, // large ROI "9057" => 2, // large ROI "9058" => 2, // large ROI "9059" => 2, // large ROI "9060" => 2, // large ROI "9061" => 10, // small ROI "9062" => 2, // large ROI "9063" => 2, // large ROI "9064" => 10, // small ROI "9065" => 2, // large ROI "9066" => 2, // large ROI, Mask/Rect > 0.5 "9067" => 2, // large ROI, Mask/Rect > 0.5 "9068" => 10, // small ROI, Mask/Rect > 0.5 );
 */
/*
 $arWeightList = array(
     "9048" => 10, // small ROI
     "9049" => .1, // large ROI
     "9050" => .1, // large ROI
     "9051" => .1, // large ROI --> ??? not sure
     "9052" => 10, // small ROI
     "9053" => 10, // small ROI
     "9054" => .1, // large ROI
     "9055" => 10, // too small ROI
     "9056" => .1, // large ROI
     "9057" => .1, // large ROI
     "9058" => .1, // large ROI
     "9059" => .1, // large ROI
     "9060" => .1, // large ROI
     "9061" => 10, // small ROI
     "9062" => .1, // large ROI
     "9063" => .1, // large ROI
     "9064" => 10, // small ROI
     "9065" => .1, // large ROI
     "9066" => .1, // large ROI, Mask/Rect > 0.5
     "9067" => .1, // large ROI, Mask/Rect > 0.5
     "9068" => 10, // small ROI, Mask/Rect > 0.5

 );
*/
/*
 $arWeightList = array(
     "9048" => 10, // small ROI
     "9049" => .5, // large ROI
     "9050" => .5, // large ROI
     "9051" => .5, // large ROI --> ??? not sure
     "9052" => 10, // small ROI
     "9053" => 10, // small ROI
     "9054" => .5, // large ROI
     "9055" => 10, // too small ROI
     "9056" => .5, // large ROI
     "9057" => .5, // large ROI
     "9058" => .5, // large ROI
     "9059" => .5, // large ROI
     "9060" => .5, // large ROI
     "9061" => 10, // small ROI
     "9062" => .5, // large ROI
     "9063" => .5, // large ROI
     "9064" => 10, // small ROI
     "9065" => .5, // large ROI
     "9066" => .5, // large ROI, Mask/Rect > 0.5
     "9067" => .5, // large ROI, Mask/Rect > 0.5
     "9068" => 10, // small ROI, Mask/Rect > 0.5

 );
*/
/*
 $arWeightList = array(
     "9048" => 10, // small ROI
     "9049" => 1, // large ROI
     "9050" => 1, // large ROI
     "9051" => 1, // large ROI --> ??? not sure
     "9052" => 10, // small ROI
     "9053" => 10, // small ROI
     "9054" => 1, // large ROI
     "9055" => 10, // too small ROI
     "9056" => 1, // large ROI
     "9057" => 1, // large ROI
     "9058" => 1, // large ROI
     "9059" => 1, // large ROI
     "9060" => 1, // large ROI
     "9061" => 10, // small ROI
     "9062" => 1, // large ROI
     "9063" => 1, // large ROI
     "9064" => 10, // small ROI
     "9065" => 1, // large ROI
     "9066" => 1, // large ROI, Mask/Rect > 0.5
     "9067" => 1, // large ROI, Mask/Rect > 0.5
     "9068" => 10, // small ROI, Mask/Rect > 0.5

 );
*/
/*
 $arWeightList = array(
     "9048" => 10, // small ROI
     "9049" => 5, // large ROI
     "9050" => 5, // large ROI
     "9051" => 5, // large ROI --> ??? not sure
     "9052" => 10, // small ROI
     "9053" => 10, // small ROI
     "9054" => 5, // large ROI
     "9055" => 10, // too small ROI
     "9056" => 5, // large ROI
     "9057" => 5, // large ROI
     "9058" => 5, // large ROI
     "9059" => 5, // large ROI
     "9060" => 5, // large ROI
     "9061" => 10, // small ROI
     "9062" => 5, // large ROI
     "9063" => 5, // large ROI
     "9064" => 10, // small ROI
     "9065" => 5, // large ROI
     "9066" => 5, // large ROI, Mask/Rect > 0.5
     "9067" => 5, // large ROI, Mask/Rect > 0.5
     "9068" => 10, // small ROI, Mask/Rect > 0.5

 );


$arWeightList = array(
    "9048" => 10, // small ROI
    "9049" => 10, // large ROI
    "9050" => 10, // large ROI
    "9051" => 10, // large ROI --> ??? not sure
    "9052" => 10, // small ROI
    "9053" => 10, // small ROI
    "9054" => 10, // large ROI
    "9055" => 10, // too small ROI
    "9056" => 10, // large ROI
    "9057" => 10, // large ROI
    "9058" => 10, // large ROI
    "9059" => 10, // large ROI
    "9060" => 10, // large ROI
    "9061" => 10, // small ROI
    "9062" => 10, // large ROI
    "9063" => 10, // large ROI
    "9064" => 10, // small ROI
    "9065" => 10, // large ROI
    "9066" => 10, // large ROI, Mask/Rect > 0.5
    "9067" => 10, // large ROI, Mask/Rect > 0.5
    "9068" => 10 // small ROI, Mask/Rect > 0.5
)

;
*/

$nShowGT = $_REQUEST['vShowGT'];
if ($nShowGT) {
    $arRawList = $arNISTList[$szQueryID];
} else {
    // printf("Path:$szVideoPath <BR>\n");
    $arQueryIDStart = array(
        2012 => 9048,
        2013 => 9069
    );
    $arQueryIDEnd = array(
        2012 => 9068,
        2013 => 9098
    );
    
    if($nQueryIDStart<$arQueryIDStart[$nTVYear])
    {
        $nQueryIDStart = $arQueryIDStart[$nTVYear]; 
    }
    if($nQueryIDEnd > $arQueryIDEnd[$nTVYear])
    {
        $nQueryIDEnd = $arQueryIDEnd[$nTVYear];
    }
    for ($nQueryID = $nQueryIDStart; $nQueryID <= $nQueryIDEnd; $nQueryID ++) {
        $szQueryIDz = sprintf("%s", $nQueryID);
        $szQueryResultDir1 = sprintf("%s/%s/%s", $szResultDir, $szOutRunID, $szVideoPath);
        $szQueryResultDir = sprintf("%s/%s/%s/%s", $szResultDir, $szOutRunID, $szVideoPath, $szQueryIDz);
        
        makeDir($szQueryResultDir1);
        $szCmdLine = sprintf("chmod 777 %s", $szQueryResultDir1);
        execSysCmd($szCmdLine);
        makeDir($szQueryResultDir);
        $szCmdLine = sprintf("chmod 777 %s", $szQueryResultDir);
        execSysCmd($szCmdLine);
        
        $szFPOutputFN = sprintf("%s/%s.rank", $szQueryResultDir1, $szQueryIDz);
        if (!file_exists($szFPOutputFN)) {
            $szResultDir1 = sprintf("%s/%s/%s/%s", $szResultDir, $szRunID1, $szVideoPath, $szQueryIDz);
            $szResultDir2 = sprintf("%s/%s/%s/%s", $szResultDir, $szRunID2, $szVideoPath, $szQueryIDz);
            
            $arRawListz = fuseRankedList($nNormMethod, $szResultDir1, $fWeight1, $szResultDir2, $fWeight2, $nTVYear);
            $arRawList = array();
            $nCount = 0;
            
            $arScoreOutput = array();
            foreach ($arRawListz as $szShotID => $fScore) {
                $arRawList[] = sprintf("%s #$# %f", $szShotID, $fScore);
                $arScoreOutput[] = sprintf("%s #$# %s #$# %f", $szShotID, $szQueryIDz, $fScore);
                $nCount ++;

                //if ($nCount > 10000)
                //    break;
            }
            saveDataFromMem2File($arRawList, $szFPOutputFN);
            $szCmdLine = sprintf("chmod 777 %s", $szFPOutputFN);
            execSysCmd($szCmdLine);
            
            $szFPScoreOutputFN = sprintf("%s/%s.res", $szQueryResultDir, $szQueryIDz);
            saveDataFromMem2File($arScoreOutput, $szFPScoreOutputFN);
            $szCmdLine = sprintf("chmod 777 %s", $szFPScoreOutputFN);
            execSysCmd($szCmdLine);
        }
    } 

    $szQueryResultDir1 = sprintf("%s/%s/%s", $szResultDir, $szOutRunID, $szVideoPath);
    $szQueryResultDir = sprintf("%s/%s/%s/%s", $szResultDir, $szOutRunID, $szVideoPath, $szQueryID);
    $szFPOutputFN = sprintf("%s/%s.rank", $szQueryResultDir1, $szQueryID);
    loadListFile($arRawList, $szFPOutputFN);
}

$nNumVideos = sizeof($arRawList);
$arScoreList = array();
foreach ($arRawList as $szLine) {
    $arTmp = explode("#$#", $szLine);
    $szShotID = trim($arTmp[0]);
    $fScore = floatval($arTmp[1]);
    if (sizeof($arScoreList) < 10000) {
        $arScoreList[$szShotID] = $fScore;
    }
}

//$arTmpzzz = computeTVAveragePrecision($arAnnList, $arScoreList, $nMaxDocs = 10000);
// print_r($arTmpzzz);

$arTmpzzz = computeTVAveragePrecision($arAnnList, $arScoreList, $nMaxDocs = 1000);
$fMAP = $arTmpzzz['ap'];
$nTotalHitsz = $arTmpzzz['total_hits'];
$szOut = sprintf("<P><H3>MAP: %0.2f. Num hits (@1000): %d<BR>\n", $fMAP, $nTotalHitsz);
$arLog [] = $szOut;
$arOutput[] = $szOut;

// update Jul 11, 2014
$szFPOutputFN = sprintf("%s/%s.log", $szQueryResultDir1, $szOutRunID);
saveDataFromMem2File($arLog, $szFPOutputFN);
exit();
// //

$nCount = 0;
$nNumShownKFPerShot = 5;
// foreach($arRawList as $szLine)

$nMaxVideosPerPage = intval($_REQUEST['vMaxVideosPerPage']);
$nPageID = max(0, intval($_REQUEST['vPageID']) - 1);
$nStartID = $nPageID * $nMaxVideosPerPage;
$nEndID = min($nStartID + $nMaxVideosPerPage, $nNumVideos, 1000);

$nNumPages = min(20, intval(($nNumVideos + $nMaxVideosPerPage - 1) / $nMaxVideosPerPage));
$queryURL = sprintf("vQueryID=%s&vRunID=%s&vMaxVideosPerPage=%s&vTVYear=%d&vAction=%d&", urlencode($szQueryIDz), urlencode($szRunID), urlencode($nMaxVideosPerPage), $nTVYear, $nAction);
// printf($queryURL);

$szURLz = sprintf("ksc-web-ViewResult.php?%s&vShowGT=1", $queryURL);

$nViewImg = 0;
if ($nShowGT) {
    $arOutput[] = sprintf("<P><H1>Ranked List - [Ground Truth] - [%d] Video Clips</H1>\n", $nNumVideos);
} else {
    $arOutput[] = sprintf("<P><H1>Total Relevant Videos <A HREF='%s'>[%s]</A>. Click the link to view all relevant ones!</H1>\n", $szURLz, sizeof($arNISTList[$szQueryID]));
}
$arOutput[] = sprintf("<P><H1>Page: ");
for ($i = 0; $i < $nNumPages; $i ++) {
    if ($i != $nPageID) {
        $szURL = sprintf("ksc-web-ViewResult.php?%s&vPageID=%d&vShowGT=%d", $queryURL, $i + 1, $nShowGT);
        $arOutput[] = sprintf("<A HREF='%s'>%02d</A> ", $szURL, $i + 1);
    } else {
        $arOutput[] = sprintf("%02d ", $i + 1);
    }
}

$arOutput[] = sprintf("<BR>\n");
// print_r($arScoreList);exit();
for ($i = $nStartID; $i < $nEndID; $i ++) {
    $szLine = $arRawList[$i];
    $arTmp = explode("#$#", $szLine);
    $szShotID = trim($arTmp[0]);
    $fScore = floatval($arTmp[1]);
    
    $szShotKFDir = sprintf("%s/test/%s", $szKeyFrameDir, $szShotID);
    $arImgList = collectFilesInOneDir($szShotKFDir, "", ".jpg");
    
    $arOutput[] = sprintf("%d. ", $nCount + 1);
    $nCountz = 0;
    $nSampling = 0;
    $nNumKFzz = sizeof($arImgList);
    $nSamplingRate = intval($nNumKFzz / $nNumShownKFPerShot);
    
    foreach ($arImgList as $szImg) {
        $nSampling ++;
        if (($nSampling % $nSamplingRate) != 0) {
            continue;
        }
        
        $szURLImg = sprintf("%s/test/%s/%s.jpg", $szKeyFrameDir, $szShotID, $szImg);
        
        // /
        // generate thumbnail image
        $szRetURL = $szURLImg;
        $imgzz = imagecreatefromjpeg($szRetURL);
        $widthzz = imagesx($imgzz);
        $heightzz = imagesy($imgzz);
        
        // calculate thumbnail size
        $new_width = $thumbWidth = 100; // to reduce loading time
        $new_height = floor($heightzz * ($thumbWidth / $widthzz));
        
        // create a new temporary image
        $tmp_img = imagecreatetruecolor($new_width, $new_height);
        
        // copy and resize old image into new image
        // imagecopyresized($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
        
        // better quality compared with imagecopyresized
        imagecopyresampled($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
        // output to buffer
        ob_start();
        imagejpeg($tmp_img);
        $szImgContent = base64_encode(ob_get_clean());
        $arOutput[] = sprintf("<IMG  TITLE='%s - %s' SRC='data:image/jpeg;base64," . $szImgContent . "' />", $szShotID, $fScore);
        
        imagedestroy($imgzz);
        imagedestroy($tmp_img);
        // /
        // $arOutput[] = sprintf("<IMG SRC='%s' WIDTH='100' TITLE='%s - %s'/> \n", $szURLImg, $szImg, $fScore);
        $nCountz ++;
        if ($nCountz >= $nNumShownKFPerShot) {
            break;
        }
    }
    
    if (in_array($szShotID, $arNISTList[$szQueryID])) {
        $arOutput[] = sprintf("<IMG SRC='winky-icon.png'><BR>\n");
        $nHits ++;
    } else {
        $arOutput[] = sprintf("<IMG SRC='sad-icon2.png'><BR>\n");
    }
    
    $arOutput[] = sprintf("<BR>\n");
    
    $nCount ++;
    if ($nCount > 100) {
        break;
    }
}

$arOutput[] = sprintf("<P><H1>Num hits (top %s): %d/%d.</H1>\n", $nMaxVideosPerPage, $nHits, $nTotalHits);

$arOutput[] = sprintf("<P><H1>Page: ");
for ($i = 0; $i < $nNumPages; $i ++) {
    if ($i != $nPageID) {
        $szURL = sprintf("ksc-web-ViewResult.php?%s&vPageID=%d&vShowGT=%d", $queryURL, $i + 1, $nShowGT);
        $arOutput[] = sprintf("<A HREF='%s'>%02d</A> ", $szURL, $i + 1);
    } else {
        $arOutput[] = sprintf("%02d ", $i + 1);
    }
}
$arOutput[] = sprintf("<P><BR>\n");

foreach ($arOutput as $szLine) {
    printf("%s\n", $szLine);
}

// ob_flush_end();
exit();

// ////////////////////////////// FUNCTIONS ///////////////////////////////////

/**
 * <videoInstanceTopic
 * text="George W.
 * Bush"
 * num="9001"
 * type="PERSON">
 */
function loadQueryDesc($szFPInputFN = "ins.topics.2011.xml")
{
    $nNumRows = loadListFile($arRawList, $szFPInputFN);
    
    $arOutput = array();
    for ($i = 0; $i < $nNumRows; $i ++) {
        $szLine = trim($arRawList[$i]);
        if ($szLine == "<videoInstanceTopic") {
            $szQueryText = trim($arRawList[$i + 1]);
            $szQueryText = str_replace("text=", "", $szQueryText);
            $szQueryText = trim($szQueryText, "\"");
            
            $szQueryID = trim($arRawList[$i + 2]);
            $szQueryID = str_replace("num=", "", $szQueryID);
            $szQueryID = trim($szQueryID, "\"");
            
            $szQueryType = trim($arRawList[$i + 3]);
            $szQueryType = str_replace(">", "", $szQueryType);
            $szQueryType = str_replace("type=", "", $szQueryType);
            $szQueryType = trim($szQueryType, "\"");
            
            $szOutput = sprintf("%s - %s - %s", $szQueryID, $szQueryType, $szQueryText);
            $arOutput[$szQueryID] = $szOutput;
        }
    }
    
    return $arOutput;
}

function parseNISTResult($szFPInputFN)
{
    loadListFile($arRawList, $szFPInputFN);
    
    $arOutput = array();
    foreach ($arRawList as $szLine) {
        // 9001 0 shot300_101 0
        $arTmp = explode(" ", $szLine);
        $szQueryID = trim($arTmp[0]);
        $szShotID = trim($arTmp[2]);
        $nLabel = intval($arTmp[3]);
        
        if ($nLabel) {
            $arOutput[$szQueryID][] = $szShotID;
        }
    }
    
    return $arOutput;
}

// update Jul 11, 2014
function fuseRankedList($nNormMethod, $szResultDir1, $fWeight1, $szResultDir2, $fWeight2, $nTVYear)
{
	global $nQueryID;
	global $arLog;

    $arResultDirList = array();
    
    $arResultDirList[] = $szResultDir1;
    $arResultDirList[] = $szResultDir2;
    
    $arResultRankList = array();
	$nRound = 1;
	
	
	// first - compute shot score = max scores of keyframes
	$arTmpList = array();
    foreach ($arResultDirList as $szResultDir) {
        $arFileList = collectFilesInOneDir($szResultDir, "", ".res");
        // print_r($arFileList);
        $arRankList = array();
        $nCount = 0;
        foreach ($arFileList as $szInputName) {
            $szFPScoreListFN = sprintf("%s/%s.res", $szResultDir, $szInputName);
            loadListFile($arScoreList, $szFPScoreListFN);
            foreach ($arScoreList as $szLine) {
                $arTmp = explode("#$#", $szLine);
                $szTestKeyFrameID = trim($arTmp[0]);
                $szQueryKeyFrameID = trim($arTmp[1]);
                $fScore = floatval($arTmp[2]);
                
                $arTmp1 = explode("_", $szTestKeyFrameID);
                if ($nTVYear != 2013) {
                    $szShotID = trim($arTmp1[0]);
                } else {
                    $szShotID = sprintf("%s_%s", trim($arTmp1[0]), trim($arTmp1[1]));
                }
                if (isset($arRankList[$szShotID])) {
                    if ($arRankList[$szShotID] < $fScore) {
                        $arRankList[$szShotID] = $fScore;
                    }
                } else {
                    $arRankList[$szShotID] = $fScore;
                }
            }
        }
        
		// compute statistics such as mean and std
        $nCount = 0;
		$fSum = 0;
		$fSumSq = 0;
		foreach ($arRankList as $szShotID => $fScore) {
			$fSum += $fScore;
			$fSumSq += $fScore*$fScore;
			$nCount++;
		}		
		$fMean = $fSum/$nCount;
		$fStd = $fSumSq/$nCount - $fMean*$fMean;
		$fStd = sqrt($fStd);
		$arLog[] = sprintf("<P>QueryID = %s - Mean = %0.4f - Std = %0.4f - Path: %s<BR>\n", $nQueryID, $fMean, $fStd, $szResultDir);
		// then fuse scores - before fusion, normalize scores
        foreach ($arRankList as $szShotID => $fScore) {
            
			if($nRound == 1)
			{
				if (isset($arResultRankList[$szShotID]["score"])) {
					exit("Serious ERROR - BUGGY!\n");
				} else {
					if($nNormMethod == 0)
					{
						$arResultRankList[$szShotID]["score"] = $fWeight1 * normScoreSigmoid($fScore);
					}
					
					if($nNormMethod == 1)
					{
						$arResultRankList[$szShotID]["score"] = $fWeight1 * normScoreZMethod($fScore, $fMean, $fStd);
					}
					
					$arResultRankList[$szShotID]["weight"] = $fWeight1;
				}
			}
			else
			{
				if (isset($arResultRankList[$szShotID]["score"])) {
					if($nNormMethod == 0)
					{
						$arResultRankList[$szShotID]["score"] += $fWeight2 * normScoreSigmoid($fScore);
					}
					if($nNormMethod == 1)
					{
						$arResultRankList[$szShotID]["score"] += $fWeight2 * normScoreZMethod($fScore, $fMean, $fStd);
					}
					$arResultRankList[$szShotID]["weight"] += $fWeight2;
				}
				else
				{
					// just ignore with assumption that set2 is a subset of set1
					//$arResultRankList[$szShotID]["score"] = $fWeight2 * normScore($fScore);
					//$arResultRankList[$szShotID]["weight"] = $fWeight2;
					
					$arTmpList[$szShotID] = 1;
				}
			}
        }
		$nRound++;
    }
	
	//printf((sizeof($arTmpList)));exit();

    $arResultRankList2 = array();
	foreach($arResultRankList as $szShotID => $arTmp)
	{
		$arResultRankList2[$szShotID] = $arTmp["score"]/$arTmp["weight"];
	}
    arsort($arResultRankList2);
    
    return ($arResultRankList2);
}

function normScoreSigmoid($fScore)
{
    $fReturn = 1 / (1 + exp(- $fScore));
    
    return $fReturn;
}

function normScoreZMethod($fScore, $fMean, $fStd)
{
    $fReturn = ($fScore - $fMean)/($fStd+1); // $fStd+1 to avoid error of dividing to zero
    
	//printf("<P>Score = %0.4f - Norm Score = %0.4f <BR>\n", $fScore, $fReturn);exit();
	
    return $fReturn;
}

?>
