<?php 

namespace App\Services\Facebook;

class FacebookParser
{

    public static function parseGeneral($metrics, $i, $pageId){
        $m = [];
        $m['likes'] = $metrics[0][0]['values'][$i]['value'];
        $m['views'] = $metrics[1][0]['values'][$i]['value'];
        $m['impressions'] = $metrics[2][0]['values'][$i]['value'];
        $m['engagements'] = $metrics[3][0]['values'][$i]['value'];
        $m['posts_engagements'] = $metrics[4][0]['values'][$i]['value'];
        $m['negative_feedback'] = $metrics[5][0]['values'][$i]['value'];
        if(!empty($metrics[6])){
            $m['video_views'] = $metrics[6][0]['values'][$i]['value'];
        }
        else{
            $m['video_views'] = 0;
        }
        $m['new_likes'] = $metrics[7][0]['values'][$i]['value'];
        $m['content_activity'] = $metrics[8][0]['values'][$i]['value'];
        $m['date_retrieved'] = date('Y-m-d', strtotime($metrics[0][0]['values'][$i]['end_time']['date']));
        $m['facebook_pages_id'] = $pageId;
        return $m;
    }

    public static function parseContentActivityType($metrics, $i, $pageId){
        $m = [];
        // so that there will be only 1 loop
        $m['checkin'] = 0;
        $m['coupon'] = 0;
        $m['event'] = 0;
        $m['fan'] = 0;
        $m['mention'] = 0;
        $m['page_post'] = 0;
        $m['question'] = 0;
        $m['user_post'] = 0;
        $m['other'] = 0;

        $types = $metrics[9][0]['values'][$i]['value'];
        foreach ($types as $key => $value) {
            if($key == 'page post'){
                $m['page_post'] = $value;
            }
            else if($key == 'user post'){
                $m['user_post'] = $value;
            }
            else{
                $m[$key] = $value;
            }
        }
        $m['date_retrieved'] = date('Y-m-d', strtotime($metrics[0][0]['values'][$i]['end_time']['date']));
        $m['facebook_pages_id'] = $pageId;
        return $m;
    }

    public static function parseLikeSource($metrics, $i, $pageId){
        $m = [];
        // so that there will be only 1 loop
        $m['ads'] = 0;
        $m['news_feed'] = 0;
        $m['page_suggestions'] = 0;
        $m['restored_likes'] = 0;
        $m['search'] = 0;
        $m['your_page'] = 0;
        $m['other'] = 0;
        $sources = $metrics[10][0]['values'][$i]['value'];

        foreach ($sources as $key => $value) {
            $key = strtolower($key);
            if($key == 'news feed'){
                $m['news_feed'] = $value;
            }
            else if($key == 'page suggestions'){
                $m['page_suggestions'] = $value;
            }
            else if($key == 'restored likes from reactivated accounts'){
                $m['restored_likes'] = $value;
            }
            else if($key == 'your page'){
                $m['your_page'] = $value;
            }
            else{
                $m[$key] = $value;
            }
        }

        $m['date_retrieved'] = date('Y-m-d', strtotime($metrics[0][0]['values'][$i]['end_time']['date']));
        $m['facebook_pages_id'] = $pageId;
        return $m;
    }

    public static function parseFansPlace($metrics, $i, $pageId, $place){
        $m = [];
        
        $index = $place == 'country' ? 11 : 12;

        $countries = $metrics[$index][0]['values'][$i]['value'];
        arsort($countries);

        $j = 1;

        foreach ($countries as $key => $value) {       
            if($j > 5){
                break;
            }
            $m[$place . $j] = $key;
            $m['value' . $j] = $value;
            $j++;
        }

        $m['date_retrieved'] = date('Y-m-d', strtotime($metrics[$index][0]['values'][$i]['end_time']['date']));
        $m['facebook_pages_id'] = $pageId;

        return $m;
    }

    public static function parseFansGender($metrics, $i, $pageId, $gender){
        $m = [];

        $gender = strtolower($gender);

        $ageGrps = $metrics[13][0]['values'][$i]['value'];
        
        $m[$gender . '_13_17'] = 0;
        $m[$gender . '_18_24'] = 0;
        $m[$gender . '_25_34'] = 0;
        $m[$gender . '_35_44'] = 0;
        $m[$gender . '_45_54'] = 0;
        $m[$gender . '_55_64'] = 0;
        $m[$gender . '_65_'] = 0;

        foreach ($ageGrps as $key => $ageGrp) {
            $key = strtolower($key);
            $key = preg_replace("/[^a-zA-Z0-9\s]/", "_", $key);
            if($key[0] != $gender)
                continue;       
            $m[$key] = $ageGrp;
        }

        $m['date_retrieved'] = date('Y-m-d', strtotime($metrics[13][0]['values'][$i]['end_time']['date']));
        $m['facebook_pages_id'] = $pageId;

        return $m;
    }

    public static function parseUpdate($metricArr, $metricName, $pageId, $appendMetric = null){
        $parsedMetric = [];
        $i = 0;
        foreach ($metricArr as $key => $metric) {
            // if(empty($like)){
            //     continue;
            // }
            $metricValues = $metric[0]["values"];
            foreach ($metricValues as $key => $metricValue) {
                if(!is_null($appendMetric)){
                    $appendMetric[$i][$metricName] = $metricValue["value"];
                    $i++;                    
                }
                else{
                    $likeObj = [];
                    $likeObj[$metricName] = $metricValue["value"];
                    $likeObj['date_retrieved'] = date('Y-m-d', strtotime($metricValue["end_time"]["date"]));
                    $likeObj['facebook_pages_id'] = $pageId;
                    array_push($parsedMetric, $likeObj);                    
                }
            }
        }
        if(is_null($appendMetric)){
            return $parsedMetric;
        }
        return $appendMetric;
    }

    public static function parseGenMetric($metricObj, $metricName, $pageId, $arrMetric = null, $index = null){
        if(!is_null($arrMetric) && !is_null($index)){
            dd($index, $metricObj);
            $arrMetric[$index][$metricName] = $metricObj['values'][$index]['value'];
        }
        else{
            $parsedLikes = [];
            foreach ($likes as $key => $like) {
                // if(empty($like)){
                //  continue;
                // }
                $likeValues = $like[0]["values"];
                foreach ($likeValues as $key => $likeValue) {
                    $likeObj = [];
                    $likeObj[$metricName] = $likeValue["value"];
                    $likeObj['date_retrieved'] = date('Y-m-d', strtotime($likeValue["end_time"]["date"]));
                    $likeObj['facebook_pages_id'] = $pageId;
                    array_push($parsedLikes, $likeObj);
                }
            }
            return $parsedLikes;
        }
    }

	public static function parsePostsDetails($postsDetails, $pageId){
		$parsedPostsDetails = [];
		foreach ($postsDetails as $key => $postDetail) {
            $post = [];
            $post['id'] = $postDetail["id"];
			$post['created_time'] = $postDetail["created_time"]->format('Y-m-d');
            $post['comments'] = count($postDetail["comments"]);
            $post['link'] = $postDetail["link"];
            $post['impressions'] = $postDetail["insights"][0]["values"][0]["value"];
            $post['engaged_users'] = $postDetail["insights"][1]["values"][0]["value"];
            $post['likes'] = $postDetail["like"];
            $post['love'] = $postDetail["love"];
            $post['wow'] = $postDetail["wow"];
            $post['haha'] = $postDetail["haha"];
            $post['sad'] = $postDetail["sad"];
            $post['angry'] = $postDetail["angry"];
            $post['facebook_pages_id'] = $pageId;
            array_push($parsedPostsDetails, $post);
		}
        return $parsedPostsDetails;
    }

    public static function parseReactions($arr, $graphEdge){
        $arr["like"] = $graphEdge->getField("like")->getMetaData()["summary"]["total_count"];
        $arr["love"] = $graphEdge->getField("love")->getMetaData()["summary"]["total_count"];
        $arr["wow"] = $graphEdge->getField("wow")->getMetaData()["summary"]["total_count"];
        $arr["haha"] = $graphEdge->getField("haha")->getMetaData()["summary"]["total_count"];
        $arr["sad"] = $graphEdge->getField("sad")->getMetaData()["summary"]["total_count"];
        $arr["angry"] = $graphEdge->getField("angry")->getMetaData()["summary"]["total_count"];

        return $arr;
    }
}