<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DateHelper;
use Auth;
use App\User;
use Exception;
use Google_Client;
use Google_Service_Analytics;

use App\Http\Controllers\API\BaseController as BaseController;

class GoogleController extends BaseController{
	private $client;

    public function __construct(Google_Client $client, Request $request) {
        // check if the accessToken is expired, if expired refresh here
        $this->client = $client;
        $this->client->setAccessToken($request->token);
    }

    public function getAccounts(Request $request){
    	$analytics = new Google_Service_Analytics($this->client);

        // get all accounts
        $accounts = $analytics->management_accounts->listManagementAccounts()->getItems();

        $all = [];
        foreach ($accounts as $key => $account) {
            $myAccount = [];
            $myAccount['accountId'] = $account->getId();
            $myAccount['accountName'] = $account->getName();
            $myAccount['properties'] = [];
            // get all properties of each account
            $properties = $analytics->management_webproperties
            ->listManagementWebproperties($myAccount['accountId'])->getItems();

            foreach ($properties as $key => $property) {
                $myProperty = [];
                $myProperty['propertyId'] = $property->getId();
                $myProperty['propertyName'] = $property->getName();
                $myProperty['views'] = [];

                // get all views
                $profiles = $analytics->management_profiles
                ->listManagementProfiles($myAccount['accountId'], $myProperty['propertyId'])->getItems();

                foreach ($profiles as $key => $profile) {
                    $myProfile = [];
                    $myProfile['profileId'] = $profile->getId();
                    $myProfile['profileName'] = $profile->getName();
                    array_push($myProperty['views'], $myProfile);
                }
                array_push($myAccount['properties'], $myProperty);
            }
            array_push($all, $myAccount);
        }

        return response()->json($all);
    }

    public function save(Request $request){

    }

}