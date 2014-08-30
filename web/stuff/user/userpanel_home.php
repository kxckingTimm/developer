<?php

/**
 * File: userpanel_home.php.
 * Author: Ulrich Block
 * Contact: <ulrich.block@easy-wi.com>
 *
 * This file is part of Easy-WI.
 *
 * Easy-WI is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Easy-WI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Easy-WI.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Diese Datei ist Teil von Easy-WI.
 *
 * Easy-WI ist Freie Software: Sie koennen es unter den Bedingungen
 * der GNU General Public License, wie von der Free Software Foundation,
 * Version 3 der Lizenz oder (nach Ihrer Wahl) jeder spaeteren
 * veroeffentlichten Version, weiterverbreiten und/oder modifizieren.
 *
 * Easy-WI wird in der Hoffnung, dass es nuetzlich sein wird, aber
 * OHNE JEDE GEWAEHELEISTUNG, bereitgestellt; sogar ohne die implizite
 * Gewaehrleistung der MARKTFAEHIGKEIT oder EIGNUNG FUER EINEN BESTIMMTEN ZWECK.
 * Siehe die GNU General Public License fuer weitere Details.
 *
 * Sie sollten eine Kopie der GNU General Public License zusammen mit diesem
 * Programm erhalten haben. Wenn nicht, siehe <http://www.gnu.org/licenses/>.
 */

if ((!isset($user_id) or !$main == "1") or (isset($user_id) and !isanyuser($user_id))) {
	header('Location: login.php');
	die('No acces');
}
$sprache_bad = getlanguagefile('home', $user_language, $reseller_id);

if (isset($admin_id) and $reseller_id != 0 and $admin_id != $reseller_id) {
	$reseller_id = $admin_id;
}

$statsArray = array(
    'gameserverActive' => 0,
    'gameserverSlotsActive' => 0,
    'gameserverSlotsUsed' => 0,
    'gameserverNoPassword' => 0,
    'gameserverNoTag' => 0,
    'gameserverNotRunning' => 0,
    'mysqlDBActive' => 0,
    'mysqlDBSpaceUsed' => 0,
    'ticketsCompleted' => 0,
    'ticketsInProcess' => 0,
    'ticketsNew' => 0,
    'virtualActive' => 0,
    'voiceserverActive' => 0,
    'voiceserverSlotsActive' => 0,
    'voiceserverSlotsUsed' => 0,
    'voiceserverTrafficAllowed' => 0,
    'voiceserverTrafficUsed' => 0,
    'voiceserverCrashed' => 0,
    'webspaceActive' => 0,
    'webspaceSpaceGivenActive' => 0,
    'webspaceSpaceUsed' => 0
);

$query = $sql->prepare("SELECT `gameserverActive`,`gameserverSlotsActive`,`gameserverSlotsUsed`,`gameserverNoPassword`,`gameserverNoTag`,`gameserverNotRunning`,`mysqlDBActive`,`mysqlDBSpaceUsed`,`ticketsCompleted`,`ticketsInProcess`,`ticketsNew`,`virtualInstalled`,`virtualActive`,`voiceserverInstalled`,`voiceserverActive`,`voiceserverSlotsInstalled`,`voiceserverSlotsActive`,`voiceserverSlotsUsed`,`voiceserverTrafficAllowed`,`voiceserverTrafficUsed`,`voiceserverCrashed`,`webspaceActive`,`webspaceSpaceGivenActive`,`webspaceSpaceUsed` FROM `easywi_statistics_current` WHERE `userID`=? LIMIT 1");
$query->execute(array($user_id));
foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $statsArray = $row;
}

$statsArray['warningTotal'] = $statsArray['gameserverNoPassword'] + $statsArray['gameserverNoTag'] + $statsArray['gameserverNotRunning'] + $statsArray['voiceserverCrashed'];
$statsArray['ticketsTotal'] = $statsArray['ticketsInProcess'];

$lastdate = null;
$feedArray = array();

if ($ui->smallletters('w', 2, 'get') == 'da' or (!$ui->smallletters('w', 2, 'get') and !$ui->smallletters('d', 2, 'get'))) {

    $htmlExtraInformation['js'][] = '<script src="//cdn.jsdelivr.net/jquery.knob/1.2.2/jquery.knob.min.js"></script>';
    $htmlExtraInformation['js'][] = '<script type="text/javascript">$(function() { $(".knob").knob({ draw: function() {} }); });</script>';

    // start collecting news feed data. When combined, timestamps will be used as array index
    $query = $sql->prepare("SELECT * FROM `feeds_settings` WHERE `resellerID`=? AND `active`='Y' LIMIT 1");
    $query->execute(array($reseller_id));
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {

        if ($row['orderBy'] == 'I' and $row['merge'] == 'N'){
            $orderFeedsBy = '`feedID` ASC';
        } else if ($row['orderBy'] == 'U' and $row['merge'] == 'N'){
            $orderFeedsBy = '`feedUrl` ASC';
        } else {
            $orderFeedsBy = 'n.`pubDate` DESC';
        }

        $newsAmount = $row['newsAmount'];
        
        if ($row['merge'] == 'N') {

            #https://github.com/easy-wi/developer/issues/80 Include CMS news in dashboards
            $query2 = $sql->prepare("SELECT p.`id`,p.`date`,t.`id` AS `textID`,t.`title`,t.`text` FROM `page_pages` p LEFT JOIN `page_pages_text` t ON p.`id`=t.`pageid` WHERE p.`released`='1' AND p.`type`='news' AND t.`language`=? AND p.`resellerid`=0 ORDER BY `date` DESC LIMIT 0,$newsAmount");
            $query2->execute(array($user_language));
            foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $row2) {
                $feedArray[$page_url][] = array('title' => $row2['title'], 'link' => (isset($seo) and $seo == 'Y') ? $page_url . '/' . $user_language . '/' . szrp($gsprache->news) . '/' . szrp($row2['title']) . '/' : $page_url . '/index.php?site=news&amp;id=' . $row2['id'], 'text' => nl2br($row2['text']), 'url' => $page_url, 'date' => date('Y-m-d', $strtotime), 'time' => date('H:i', $strtotime));
            }

            $query2 = $sql->prepare("SELECT `feedID`,`feedUrl`,`feedID`,`twitter`,`loginName` FROM `feeds_url` WHERE `resellerID`=? AND `active`='Y' ORDER BY $orderFeedsBy");
            $query2->execute(array($row['resellerID']));
            $object = $query2->fetchAll(PDO::FETCH_ASSOC);

            if ($row['steamFeeds'] == 'Y') {
                $object[] = array('feedID' => 0, 'feedUrl' => 'http://store.steampowered.com/news/', 'twitter' => 'N', 'loginName' => '');
            }

            foreach ($object as $row2) {

                $query3 = $sql->prepare("SELECT `title`,`link`,`description`,`content`,`pubDate` FROM `feeds_news` WHERE `feedID`=? AND `resellerID`=? AND `active`='Y' ORDER BY `pubDate` DESC LIMIT $newsAmount");
                $query3->execute(array($row2['feedID'], $row['resellerID']));
                foreach ($query3->fetchAll(PDO::FETCH_ASSOC) as $row3) {

                    if ($row['displayContent'] == 'Y' and $row['limitDisplay'] == 'Y' and $row2['twitter'] == 'N'){
                        $text = substr($row3['content'], 0, $row['maxChars']);
                    } else if ($row['displayContent'] == 'Y' and $row['limitDisplay'] == 'N' and $row2['twitter'] == 'N'){
                        $text = $row3['content'];
                    } else if ($row['displayContent'] == 'N' and $row['limitDisplay'] == 'Y' and $row2['twitter'] == 'N'){
                        $text = substr($row3['description'], 0, $row['maxChars']);
                    } else {
                        $text = $row3['description'];
                    }

                    $url = ($row2['twitter'] == 'N') ? $row2['feedUrl'] : 'https://twitter.com/' . $row2['loginName'];

                    $strtotime = strtotime($row2['pubDate']);

                    $feedArray['News'][$strtotime] =  array('title' => $row3['title'], 'link' => $row3['link'], 'text' => $text, 'url' => $url, 'date' => date('Y-m-d', $strtotime), 'time' => date('H:i', $strtotime));
                }
            }

            unset($object);

        } else {

            $steamAppIDs = ($row['steamFeeds'] == 'Y' and count($steamAppIDsArray) > 0) ? ' OR (n.`feedID`=0  AND n.`content` IN (' . implode(',', $steamAppIDsArray) . '))' : '';

            $query2 = $sql->prepare("(SELECT 'cms' AS `newsType`,p.`id` AS `newsID`,p.`date` AS `newsDate`,t.`id` AS `textID`,t.`title` AS `newsTitle`,t.`text` AS `newsText`,'' AS `twitterText`,'' AS `feedUrl`,'N' AS `twitter`,'' AS `loginName`,'' AS `link` FROM `page_pages` p LEFT JOIN `page_pages_text` t ON p.`id`=t.`pageid` WHERE p.`released`='1' AND p.`type`='news' AND t.`language`=:lang AND p.`resellerid`=0) UNION (SELECT 'feed' AS `newsType`,u.`feedID` AS `newsID`,n.`pubDate` AS `newsDate`,0 AS `textID`,n.`title` AS `newsTitle`,n.`content` AS `newsText`,n.`description` AS `twitterText`,u.`feedUrl`,u.`twitter`,u.`loginName`,n.`link` FROM `feeds_news` n LEFT JOIN `feeds_url` u ON n.`feedID`=u.`feedID` WHERE n.`resellerID`=:resellerID AND n.`active`='Y' AND (u.`active`='Y'  $steamAppIDs)) ORDER BY `newsDate` DESC LIMIT 0,$newsAmount");
            $query2->execute(array(':lang' => $user_language,':resellerID' => $row['resellerID']));
            foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $row2) {


                if ($row2['newsType'] == 'cms') {

                    $url = $page_url;
                    $link = (isset($seo) and $seo == 'Y') ? $page_url . '/' . $user_language . '/' . szrp($gsprache->news) . '/' . szrp($row2['newsTitle']) . '/' : $page_url . '/index.php?site=news&amp;id=' . $row2['newsID'];
                    $text = nl2br($row2['newsText']);

                } else {

                    $url = ($row2['twitter'] == 'N') ? $row2['feedUrl'] : 'https://twitter.com/' . $row2['loginName'];
                    $link = $row2['link'];

                    if ($row['displayContent'] == 'Y' and $row['limitDisplay'] == 'Y' and $row2['twitter'] == 'N'){
                        $text = substr(preg_replace('/<(.*?)>/', '', preg_replace('/<*?[^<>]*?>(.*?)<\/*?>/', '$1', $row2['newsText'], -1), -1), 0, $row['maxChars']);
                    } else if ($row['displayContent'] == 'Y' and $row['limitDisplay'] == 'N' and $row2['twitter'] == 'N'){
                        $text = $row2['newsText'];
                    } else if ($row['displayContent'] == 'N' and $row['limitDisplay'] == 'Y' and $row2['twitter'] == 'N'){
                        $text = substr(preg_replace('/<(.*?)>/', '', preg_replace('/<*?[^<>]*?>(.*?)<\/*?>/', '$1', $row2['twitterText'], -1), -1), 0, $row['maxChars']);
                    } else {
                        $text = $row2['twitterText'];
                    }
                }

                $title = $row2['newsTitle'];

                if (strlen($row2['newsTitle']) <= 1) {
                    $title = $link;
                }

                $strtotime = strtotime($row2['newsDate']);

                $feedArray['News'][] = array('title' => $title, 'link' => $link, 'text' => $text,'url' => $url, 'date' => date('Y-m-d', $strtotime), 'time' => date('H:i', $strtotime));
            }
        }
    }
}