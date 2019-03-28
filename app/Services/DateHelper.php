<?php 

namespace App\Services;

class DateHelper
{
    public static function addDays($date, int $days){
    	$newDate = \DateTime::createFromFormat('Y-m-d', $date);
    	$newDate->modify("+{$days}days");
    	$newDate = $newDate->format('Y-m-d');
    	return $newDate; 
    }

    public static function subtractDays($date, int $days){
    	$newDate = \DateTime::createFromFormat('Y-m-d', $date);
    	$newDate->modify("-{$days}days");
    	$newDate = $newDate->format('Y-m-d');
    	return $newDate;
    }
}