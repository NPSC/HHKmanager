<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Storage;
use DB;
use Config;
use Carbon\Carbon;

class Site extends Model
{
	
	protected $appends = ['config','sysconfig', 'city', 'priceModel', 'roomCount', 'lastAccessed', 'version', 'siteName'];
	protected $casts = [
        'config' => 'collection',
    ];
	
    public function comments(){
	    return $this->hasMany('App\Comment')->orderBy('created_at', 'desc');
    }
    
    public function getConfigAttribute(){
	    try{
	        $fullconfig = parse_ini_string(Storage::disk("prod")->get($this->url . "/conf/site.cfg"), true);
	    }catch(\FileNotFoundException $e){
		    return false;
	    }catch(\Exception $e){
		    return false;
	    }
	    
	    try{
	        $config = array();
	        $config['site'] = $fullconfig['site'];
	        $config['db'] = $fullconfig['db'];
	        return $config;
	    }catch(\Exception $e){
	        return $fullconfig;
	    }
    }
    
    public function getVersionAttribute(){
	    try{
	        if(Storage::disk("prod")->exists($this->url . "/classes/SysConst/CodeVersion.php")){
	            $file = Storage::disk("prod")->get($this->url . "/classes/SysConst/CodeVersion.php");
	        }else if(Storage::disk("prod")->exists($this->url . "/classes/SysConst.php")){
	            $file = Storage::disk("prod")->get($this->url . "/classes/SysConst.php");
	        }else{
	            return "CodeVersion file not found";
	        }
			preg_match("/^class CodeVersion.*\s*const BUILD = ([0-9]*).*\s*const VERSION = ([0-9, .]*)/m", $file, $array);
			preg_match("/^class CodeVersion.*\s*const BUILD = '(.*)'.*\s*const VERSION = '(.*)'/m", $file, $array2);
	    
    	    if(count($array) >= 3 && $array[2] != '' && $array[1] != ''){
    		    return $array[2] . "." . $array[1];
    	    }else if(count($array2) >= 3 && $array2[2] != '' && $array2[1] != ''){
    			return $array2[2] . "." . $array2[1];
    		}else if(isset($this->config['code']['Version'])){
    		    return $this->config['code']['Version'] . " build " . $this->config['code']['Build'];
    		}else{
    		    return "Version not found";
    		}
	    }catch(\Exception $e){
	        return "Version not found";
	    }
    }
    
    public function getSchemaAttribute(){
	    try{
		    //decrypt
		    $key = "017d609a4b2d8910685595C8df";
		    $key = hash('sha256', $key);
		    $iv = "fYfhHeDmf j98UUy4";
		    $iv = substr(hash('sha256', $iv), 0, 16);
		    $encrypt_method = "AES-256-CBC";
		    $password = openssl_decrypt(base64_decode($this->config['db']['Password']), $encrypt_method, $key, 0, $iv);
	
		    $defaultConnection = config('database.connections.mysql');
	        $newConnection = $defaultConnection;
	        $newConnection['database'] = $this->config['db']['Schema'];
	        $newConnection['username'] = $this->config['db']['User'];
	        $newConnection['password'] = $password;
	        Config::set('database.connections.' . $this->config['db']['Schema'], $newConnection);
	        
	        return DB::connection($this->config['db']['Schema']);
	    }catch(\Exception $e){
		    return $e->getMessage();
	    }
	    
    }
    
    public function hhkdecrypt($password){
	    
	    $key = "017d609a4b2d8910685595C8df";
	    $key = hash('sha256', $key);
	    $iv = "fYfhHeDmf j98UUy4";
	    $iv = substr(hash('sha256', $iv), 0, 16);
	    $encrypt_method = "AES-256-CBC";
	    $output = false;
	    
	    $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	    return $output;
    }
    
    public function getSysconfigAttribute(){
	    try{
	    	$result = $this->schema->table('sys_config')->select('Key', 'Value')
	    	->whereIn('Key', ['siteName', 'Zip_Code', 'RoomPriceModel', 'tz','mode'])
	    	->get()->keyBy('Key');
	    }catch(\Illuminate\Database\QueryException $e){
		    $result = false;
	    }
	    return $result;
    }
    
    public function getCityAttribute(){
	    try{
	    	if($this->sysconfig && $this->sysconfig->get('Zip_Code')){
		    	$zipCode = $this->sysconfig->get('Zip_Code')->Value;
	    	}else{
		    	$zipCode = $this->config['house']['Zip_Code'];
	    	}
    		$result = $this->schema->table('postal_codes')->select('Zip_Code', 'City', 'State')->where('Zip_Code', '=', $zipCode)->first();
    		$return = $result->City . ', ' . $result->State;
    		return $return;
    	}catch(\Illuminate\Database\QueryException $e){
	    	return $e->getMessage();
    	}catch(\Exception $e){
	    	return $e->getMessage();
    	}
	    
    }
    
    public function getPriceModelAttribute(){
        try{
    	    if($this->sysconfig){
    	    	$priceModelCode = $this->sysconfig->get('RoomPriceModel')->Value;
    	    }else{
    		    $priceModelCode = false;
    	    }
        }catch(\Exception $e){
            return $e->getMessage();
        }
        
	    if($priceModelCode){
		    try{
	    		$priceModel =  $this->schema->table('gen_lookups')->select('Description')->where([['Table_name', '=', 'Price_Model'],['Code', '=', $priceModelCode]])->first();
				$result = $priceModel->Description;
	    	}catch(\Illuminate\Database\QueryException $e){
		    	$result = false;
	    	}
	    }else{
		    $result = false;
	    }
	    
	    return $result;
    }
    
    public function getModeAttribute(){
        try{
            $mode = $this->sysconfig['mode']->Value;
        }catch(\Exception $e){
            $mode = $this->config['site']['Mode'];
        }
        
        return $mode;
    }
    
    public function getRoomCountAttribute(){
	    try{
		    $result = $this->schema->table('room')->count();
	    }catch(\Illuminate\Database\QueryException $e){
		    $result = false;
	    }
	    return $result;
    }
    
    public function getLastAccessedAttribute(){
	    try{
		    $lastAccessed = $this->schema->table('w_user_log')->select('Username','Access_Date')->orderBy('Access_Date', 'desc')->first();
	    }catch(\Illuminate\Database\QueryException $e){
		    $lastAccessed = false;
	    }
	    if($lastAccessed){
		    if(isset($this->config['calendar']['TimeZone'])){
		   		$timezone = $this->config['calendar']['TimeZone'];
		   	}else if(isset($this->sysconfig['tz'])){
			   	$timezone = $this->sysconfig['tz']->Value;
		   	}
		   	
		   	if(!$timezone){
				$timezone = "UTC";
		    }
	    	$lastAccessed->Access_Date = Carbon::parse($lastAccessed->Access_Date, $timezone);
			return $lastAccessed;
		}else{
			return false;
		}

    }
    
    public function getSiteNameAttribute(){
        try{
    		if(isset($this->config['site']['Site_Name'])){
    			return $this->config['site']['Site_Name'];
    		}else if(isset($this->sysconfig["siteName"])){
    			return $this->sysconfig["siteName"]->Value;
    		}else if($this->name){
    			return $this->name;
    		}else{
    			$this->url;
    		}
        }catch(\Exception $e){
            return $e->getMessage();
        }
    }
}
