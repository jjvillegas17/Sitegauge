<?php


namespace App\Services\Facebook;

use Facebook\Facebook;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookResponseException;

class FacebookInsight {
	private $fb;
	private $since;
	private $until;
	private $metric = 'page_fans';
	private $pageId;
	private $batch = [];

	public function __construct(Facebook $fb, $pageId){
		$this->fb = $fb;
		$this->pageId = $pageId;
	}

	public function addRequest($metric){
		$request = $this->fb->get("/{$this->pageId}/insights?metric={$this->metric}&since={$this->since}&until={$this->until}")->getGraphEdge();
	}

	public function getBatchRequest($batch){
		try {
          $responses = $this->fb->sendBatchRequest($batch);
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          // When Graph returns an error
        	dd('error');
        	return 'error';
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
           	dd('error');
           	return 'error';
        }
        return $responses;
	}

	public function since($since){
		if(is_null($since)){
			$this->since = $since;
			return $this;
		}
        $since = date('Y-m-d', strtotime($since . '-1 days'));
		$this->since = $since;
		return $this;
	}

	public function until($until){
		if(is_null($until)){
			$this->until = $until;
			return $this;
		}
		$until = date('Y-m-d', strtotime($until . '+1 days'));
		$this->until = $until;
		return $this;
	}

	public function metric($metric){
		$this->metric = $metric;
		return $this;
	}

	public function checkDate(){
		if(is_null($this->since) && is_null($this->until)) {
		    $this->until = date('Y-m-d', strtotime('+1 day'));
            $this->since = date('Y-m-d', strtotime('-7 days'));
        }
        else if(is_null($this->since) && !is_null($this->until)) {
            $date = \DateTime::createFromFormat('Y-m-d', $this->until);
            $date->modify('-8 day');
            $this->since = $date->format('Y-m-d');
        }

	}

	public function get(){
		$this->checkDate();

		try {
			if(!is_null($this->since) && is_null($this->until)){
				$graphEdge = $this->fb->get("/{$this->pageId}/insights?metric={$this->metric}&since={$this->since}")->getGraphEdge();
			}
			else{
				$graphEdge = $this->fb->get("/{$this->pageId}/insights?metric={$this->metric}&since={$this->since}&until={$this->until}")->getGraphEdge();
			}
        } catch (FacebookSDKException $e) {
            return 'error';
        }

        return $graphEdge->asArray();
	}
}