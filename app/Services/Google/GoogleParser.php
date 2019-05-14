<?php 

namespace App\Services\Google;

class GoogleParser{
	public static function parseAudience($reports, $profileId){
		$arr = [];
		for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
		    $report = $reports[ $reportIndex ];
		    $header = $report->getColumnHeader();
		    $dimensionHeaders = $header->getDimensions();
		    $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
		    $rows = $report->getData()->getRows();
		    for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
		      $audience = [];
		      $row = $rows[ $rowIndex ];
		      $dimensions = $row->getDimensions();
		      $metrics = $row->getMetrics();
		      for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
		      	$audience['date_retrieved'] = $dimensions[$i];
		        // echo "<pre>" . print_r($dimensionHeaders[$i] . ": " . $dimensions[$i]) . "</pre>";
		      }

		      for ($j = 0; $j < count($metrics); $j++) {
		        $values = $metrics[$j]->getValues();
		        // return response()->json($values);
		        for ($k = 0; $k < count($values); $k++) {
		          $entry = $metricHeaders[$k];
		          // echo "<pre>" . print_r($entry->getName() . ": " . $values[$k]) . "</pre>";
		          if($entry->getName() == 'avg_session_duration'){
		          	$time = (float)$values[$k];
                    $mins = $time/60;
                    $mins = round($mins, 2);

		          	$audience[$entry->getName()] = $mins;
		          	continue;
		          }
		          $audience[$entry->getName()] = $values[$k];
		        }
		      }
		      $audience['profile_id'] = $profileId;
		      $audience['uploader_id'] = null;
		      array_push($arr, $audience);
		    }

		}
		return $arr;
	}

	public static function parseAcquisition($reports,$profileId){
		$arr = [];
		for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
		    $report = $reports[ $reportIndex ];
		    $rows = $report->getData()->getRows();
		    $ind = 0;
		    for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
		      $acquisition = [];
		      $row = $rows[ $rowIndex ];
		      $dimensions = $row->getDimensions();
		      $metrics = $row->getMetrics();
		      
		      if($ind>0 && $arr[$ind-1]['date_retrieved'] == $dimensions[0]){
		      	if(strpos($dimensions[1], 'Organic') !== false){
			      	$arr[$ind-1]['organic_search'] = (int) $row->getMetrics()[0]->values[0];
			      	continue;
			    }
		      	else if(strpos($dimensions[1], 'Other') !== false){
		      		$arr[$ind-1]['other'] = (int) $row->getMetrics()[0]->values[0];
		      		continue;
		      	}
		      	$arr[$ind-1][strtolower($dimensions[1])] = (int) $row->getMetrics()[0]->values[0];
		      	continue;
		      }
		      $acquisition['date_retrieved'] = $dimensions[0];
		      $acquisition['organic_search'] = 0;
		      $acquisition['other'] = 0;
		      $acquisition['social'] = 0;
		      $acquisition['referral'] = 0;
		      $acquisition['direct'] = 0;
		      $acquisition['profile_id'] = $profileId;
		      if(strpos($dimensions[1], 'Organic') !== false){
		      	$acquisition['organic_search'] = (int) $row->getMetrics()[0]->values[0];
		      }
		      else if(strpos($dimensions[1], 'Other') !== false){
		      	$acquisition['other'] = (int) $row->getMetrics()[0]->values[0];
		      }
		      else{
		      	$acquisition[strtolower($dimensions[1])] = (int) $row->getMetrics()[0]->values[0];	
		      }
		      array_push($arr, $acquisition);
		      $ind++; 
		    }
		}
		return $arr;
	}

	public static function parseBehavior($reports,$profileId){
		$behaviors = [];
		for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
		    $report = $reports[ $reportIndex ];
		    $header = $report->getColumnHeader();
		    $dimensionHeaders = $header->getDimensions();
		    $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
		    $rows = $report->getData()->getRows();

		    for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
		      $behavior = [];
		      $row = $rows[ $rowIndex ];
		      $dimensions = $row->getDimensions();
		      $metrics = $row->getMetrics();
		      $behavior['date_retrieved'] = $dimensions[0];
		      $behavior['page_path'] = $dimensions[1];
		      $behavior['pageviews'] = (int) $row->getMetrics()[0]->values[0];
		      $behavior['profile_id'] = $profileId;
		      array_push($behaviors, $behavior);
		    }
	  }
	  return $behaviors;
	}
}