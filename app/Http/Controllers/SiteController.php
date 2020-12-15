<?php

namespace App\Http\Controllers;

use App\Site;
use App\Comment;
use App\Version;
use App\User;
use Illuminate\Http\Request;
use Storage;
use Session;
use Auth;
use App\Rules\fileNotExist;
use App\Rules\fileExists;
use Config_Lite;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use DB;

class SiteController extends Controller
{
	/**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('two_factor');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    
    public function demo()
    {
	    $sites = Site::all();
	    $sites = $sites->where('config.site.Mode', 'demo');
	    $versions = Version::orderBy('release_date', 'desc')->get();
	    $mode = "Demo";
	    
        return view('sites.index')->with(['sites'=>$sites, 'versions'=>$versions, 'mode'=>$mode]);
    }
    
    public function live()
    {
        $sites = Site::all();
        if(count($sites->whereNotIn('config.site.Mode', ['demo', 'live'])) > 0){
		    Session::flash("info", "There is one or more sites with issues, <a href='" . route("sites.other") . "'>Click here to fix</a>");
	    }
        $sites = $sites->where('config.site.Mode', 'live');
	    $versions = Version::orderBy('release_date', 'desc')->get();
	    $mode = "Live";
	    
        return view('sites.index')->with(['sites'=>$sites, 'versions'=>$versions, 'mode'=>$mode]);
    }
    
    public function other()
    {
	    $sites = Site::all();
	    $sites = $sites->whereNotIn('config.site.Mode', ['demo', 'live']);
	    $versions = Version::orderBy('release_date', 'desc')->get();
	    $mode = "Other";
	    
        return view('sites.index')->with(['sites'=>$sites, 'versions'=>$versions, 'mode'=>$mode]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(array(
		        'name' => 'required|max:255|unique:sites',
		        'path' => ['required', new fileNotExist("prod")],
		        'version' =>['required', "exists:versions,id"],
	        ));
	        $version = Version::find($request->version);
	        $patharray = explode('/', $request->path);
	        $dbname = end($patharray);
	        $dbpassword = str_random(40);
	        $sitePepper = str_random(40);
	        
			$output = "";
			
			if(!UserController::checkVMRExpiry() > 0){
			    Session::flash("error", "VM Racks password Expired");
			    return redirect()->back();
			}
			
			try{
			    $hhkapp = User::withTrashed()->where("email", "hhkapp")->first();
			    $vmrpwd = $hhkapp->password;
			    
				passthru("echo " . decrypt($vmrpwd) . " | su -c \"bash " . base_path() . "/scripts/newSite.sh " . $version->filepath . " " . $request->path . "\" -m \"hhkapp\"");
				
			}catch(\Exception $e){
				Session::flash('error', "Failed to copy files: " . $e->getMessage());
				return redirect()->back();
			}
	
			if(Storage::disk('prod')->exists($request->path . "/conf/site.cfg")){
				$config = new Config_Lite(Storage::disk('prod')->path($request->path . "/conf/site.cfg"));
				$config->set('db', 'URL', '10.138.0.21')
						->set('db', 'User', $dbname)
						->set('db', 'Password', self::hhkencrypt($dbpassword))
						->set('db', 'Schema', $dbname)
				        ->set('site', 'sitePepper', $sitePepper);
						
				if($request->demo){
					$config->set('site', 'Mode', 'demo');
				}else{
					$config->set('site', 'Mode', 'live');
				}
						
				$config->save();
			}else{
				Session::flash('error', "Unable to find site.cfg - more info below<br><pre>" . $output . "</pre>");
				return redirect()->back();
			}
			
			$schemaoutput = DB::select('CALL create_user_schema(?, ?)', [$dbname,$dbpassword]);
			
			if(isset($schemaoutput[0]->error)){
				Session::flash('error', "Error creating database, " . $schemaoutput[0]->error);
				return redirect()->back();
			}
			
			if(isset($schemaoutput[0]->success)){
				$output .= "Schema created successfully<br>";
			}
			
	        $site = new Site;
	        
	        $site->name = $request->name;
	        $site->url = $request->path;
	        $site->save();
	        
	        $comment = new Comment;
	        $comment->body = "Site created";
	        $comment->author()->associate(Auth::user());
	        $comment->save();
	        
	        $site->comments()->save($comment);
	        
	        $output .= '<br>New Schema Username: ' . $dbname . '<br>New Schema password: ' . $dbpassword . '<br>New Site Pepper: ' . $sitePepper .  '<br>Site created successfully, <a href="https://hospitalityhousekeeper.net/' . $request->path . '/install/step2.php" target="_blank">click here</a> to continue installation.<br>';
	        
	        Session::flash('success', "<pre>" . $output . "</pre>");
	        
	        return redirect()->back();

    }
    
    public function hhkencrypt($password){
	    
	    $key = "017d609a4b2d8910685595C8df";
	    $key = hash('sha256', $key);
	    $iv = "fYfhHeDmf j98UUy4";
	    $iv = substr(hash('sha256', $iv), 0, 16);
	    $encrypt_method = "AES-256-CBC";
	    $output = false;
	    
	    $output = base64_encode(openssl_encrypt($password, $encrypt_method, $key, 0, $iv));
	    //openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	    return $output;
    }
    
    public function import(Request $request)
    {
        $request->validate(array(
		        'path' => ['required', 'unique:sites,url', new fileExists("prod")],
	        ));
	        
	        $conf = "";
	        $site_name = "";
	        $version = "";
	        try{
		        		        
		        $site = new Site;
		        
		        $site->url = $request->path;
		        $site->save();
		        
		        $comment = new Comment;
		        $comment->body = "Site added to dashboard";
		        $comment->author()->associate(Auth::user());
		        $comment->save();
		        
		        $site->comments()->save($comment);		        
		        
		        Session::flash('success', 'New Site added');
	        }catch(\Exception $e){
		        Session::flash('error', $e->getMessage());
	        }
	        
	        return redirect()->route('sites.live');
    }
    
    public function upgrade(Request $request)
    {
	    
	    $request->validate(array(
	    	'version_id' => ['required','exists:versions,id'],
		    'site_id' => ['required','exists:sites,id'],
		    'user' => ['required'],
		    'pw' => ['required'],
	    ));
	    try{
		    
		    $version = Version::find($request->version_id);
		    $site = Site::find($request->site_id);
		    
		    $hhkapp = User::withTrashed()->where("email", "hhkapp")->first();
		    $vmrpwd = $hhkapp->password;
		    
		    $script = "echo " . decrypt($vmrpwd) . " | su -c \"bash " . base_path() . "/scripts/upgrade.sh " . $version->filepath . " " . $site->url . "\" -m \"hhkapp\"";
		    $upgradeURL = "https://hospitalityhousekeeper.net/" . $site->url . "/admin/ws_update.php";
		    $client = new Client(); //GuzzleHttp\Client
		    
		    //Check password
		    //old password check
		    $passwordResult = $client->request('GET', $upgradeURL, [
				'query' => [
					'cd' => $site->config['db']['Schema'],
					'un' => $request->user,
					'so' => md5($request->pw),
					'ck' => 'y'
				]
			]);
			
			$passwordJson = json_decode($passwordResult->getBody());
			
			if(!isset($passwordJson->init)){
			    $passwordResult = $client->request('POST', $upgradeURL, [
			        'form_params' => [
			            'cd' => $site->config['db']['Schema'],
			            'un' => $request->user,
			            'so' => $request->pw,
			            'ck' => 'y'
			        ]
			    ]);
			    
			    $passwordJson = json_decode($passwordResult->getBody());
			}
			
			if(isset($passwordJson->resultMsg) && $passwordJson->resultMsg == "bubbly"){
		    
			    if(!UserController::checkVMRExpiry() > 0){
			         Session::flash("error", "VM Racks password Expired");
			         return redirect()->back();
			    }
			    
			    //copy files
			    ob_start();
			    passthru($script);
				$scriptoutput = ob_get_contents();
				ob_end_clean();
				
			    //run update script
				$result = $client->request('GET', $upgradeURL, [
					'query' => [
						'cd' => $site->config['db']['Schema'],
						'un' => $request->user,
						'so' => md5($request->pw),
					]
				]);
				
				$json = json_decode($result->getBody());
				
				if(!isset($json->init)){
				    
				    $result = $client->request('POST', $upgradeURL, [
				        'form_params' => [
				            'cd' => $site->config['db']['Schema'],
				            'un' => $request->user,
				            'so' => $request->pw,
				        ]
				    ]);
				    
				    $json = json_decode($result->getBody());
				    
				}
				
				if(isset($json->error)){
					Session::flash('error', "<pre>" . $scriptoutput . "\nHHK response: \n" . $json->error .  "</pre>");	
				}elseif(isset($json->errorMsg) && $json->errorMsg != ""){
					Session::flash('error', "<pre>" . $scriptoutput . "\nHHK response: \n" . $json->errorMsg .  "</pre>");
				}elseif(isset($json->resultMsg)){
				    
				    $comment = new Comment;
				    $comment->body = "Site updated to " . $version->name;
				    $comment->author()->associate(Auth::user());
				    $comment->save();
				    
				    $site->comments()->save($comment);
				    
					Session::flash('success', "<pre>" . $scriptoutput . "\nHHK response: \n" . $json->resultMsg .  "</pre>");
				}else{
					Session::flash('error', "An unknown error occurred.<br>Possible solution: Does ws_update.php exist in the page table?<br>script results (if any) <br><pre>" . $scriptoutput . "</pre>"  . $site->config['db']['Schema']);
				}
				
			}else{
				Session::flash('error', "Unable to update - Invalid Password" . $passwordResult->getBody());
			}
	    }catch(\Exception $e){
		   	Session::flash("error", "The following error has occurred: \n" . $e->getMessage() . " Line: " . $e->getLine());
	    }
	    return redirect()->back();
    }
    
    
    public function setLive(Site $site)
    {
	    
	    $slug = explode("/", $site->url);
	    
	    $slugEnd = end($slug);
	    
	    if($slug[0] == "demo"){
	        
	        if(!UserController::checkVMRExpiry() > 0){
	            Session::flash("error", "VM Racks password Expired");
	            return redirect()->back();
	        }
	        
	        try{
	            $hhkapp = User::withTrashed()->where("email", "hhkapp")->first();
	            $vmrpwd = $hhkapp->password;
	        
	    	    $script = "echo " . decrypt($vmrpwd) . " | su -c \"bash " . base_path() . "/scripts/move.sh " . $slugEnd . "\" -m \"hhkapp\"";
			
			    passthru($script);
			
	    	    if(Storage::disk('prod')->exists($slugEnd . "/conf/site.cfg")){
				
				    $site->url = $slugEnd;
				    $site->save();
				    session::flash('success', "Site " . $site->siteName . " moved successfully.<br><br><strong>Further tasks</strong><ul><li>Add 'Use ClientVHost $slugEnd $slugEnd' to /etc/httpd/conf.d/z_clients.conf</li></ul>");
			    }else{
				    session::flash('error', "The site could not be moved.");
			    }
	        }catch(\Exception $e){
	            Session::flash("error", $e->getMessage());
	        }
	    }else{
		    session::flash('error', "Site is already in the live directory");
	    }
	    
	    return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
	    $site = Site::find($id);
        return response()->json($site);
    }
    
    public function getConfig($id){
	    $site = Site::find($id);
	    return response()->json($site->config);
    }
    
    public function getComments($id){
	    $site = Site::with('comments.author')->find($id);
	    return response()->json($site->comments);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function edit(Site $site)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Site $site)
    {
        $request->validate(array(
		        'name' => 'required|max:255',
		        'path' => 'required',
	        ));

	        
	        $site->name = $request->name;
	        $site->url = $request->path;
	        $site->save();
	        
	        $comment = new Comment;
	        $comment->body = "Site updated";
	        $comment->author()->associate(Auth::user());
	        $comment->save();
	        
	        $site->comments()->save($comment);
	        	        
	        Session::flash('success', $site->name . ' updated successfully');
	        
	        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function destroy(Site $site)
    {
/*
        $script = "bash " . base_path() . "/scripts/delete.sh " . $site->url;
	    $scriptoutput = passthru($script);
	    if(!$scriptoutput == null){
		    Session::flash("error", "Could not delete site: " . $scriptoutput);
	    }
*/
	    $site->delete();
	    Session::flash('success', "Site removed from dashboard successfully - No files were deleted.<br>If you plan on deleting the site completely, don't forget to delete the files, database and database user");
        return redirect()->back();
    }
}
