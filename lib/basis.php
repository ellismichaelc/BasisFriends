<?
class Basis {
	// Curl Handler
	private $curl        = null;
	private $curl_config = null;

	// These will hold the users login information
	private $login_done  = false;
	private $login_email = null;
	private $login_pass  = null;
	
	// Info about our user
	private $user_info   = array();
	private $user_data   = array();
	private $user_feed   = array();
	private $user_habits = array();
	
	// These will hold error information
	public $error_found  = false;
	public $error_string = "";
	
	// Paths to the data we're scraping!
	private $url_base    = "https://app.mybasis.com/";
	private $url_login   = "login";
	private $url_cookie  = '';
	private $url_agent   = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
	
	// Assumes POST, login then password respectively with %s
	private $url_params  = "next=&username=%s&password=%s&submit=Login";

	// URLs for various stuff
	private $url_info    = array("api/v1/user/me",
								 "api/v1/points",
								 "api/v1/feed/me",
								 "api/v1/user/me/habit_slots");

	public function __construct($email, $pass) {
		$this->url_cookie  = tempnam(sys_get_temp_dir(), '');

		if(!$this->url_cookie) die("Couldn't create temporary file");

		$this->curl_config = array(
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_FOLLOWLOCATION => true,
		    CURLOPT_COOKIESESSION  => true,
		    CURLOPT_COOKIEJAR	   => $this->url_cookie,
		    CURLOPT_COOKIEFILE     => $this->url_cookie,
		    CURLOPT_USERAGENT      => $this->url_agent,
		    CURLOPT_SSL_VERIFYPEER => false,
		);
		
		$this->login_email = $email;
		$this->login_pass  = $pass;
	}
	
	public function userInfo() {
	
		return $this->user_info;
		
	}

	public function userFeed() {
	
		return $this->user_feed;
		
	}

	public function userHabits() {
	
		return $this->user_habits;
		
	}
	
	public function collectData() {
	
		// This will hit each URL and merge the new info to the user info variable
		foreach($this->url_info as $url) {
			if(!$new_info = $this->getURL($this->url_base . $url)) return false;
			
			$new_info = json_decode($new_info, true);

			if(isset($new_info['error'])) {
			
				$this->error_found  = true;
				$this->error_string = $new_info['error'] . " - " . $new_info['error_description'];
				
				return false;
			}
			
			if(!is_array($new_info)) {
				$this->error_found  = true;
				$this->error_string = "Data was empty.";
			
				return false;
			}
			
			// Don't do this if it's the feed, we collect this data differently
			if(strstr($url, "/feed/")) 			 $this->user_feed   = $new_info;
			elseif(strstr($url, "/habit_slots")) $this->user_habits = $new_info;
			else                       			 $this->user_info   = array_merge($this->user_info, $new_info);
		}
		
		return true;
		
	}

	public function login() {	
	
		$url    = $this->url_base . $this->url_login;
		$config = array(
		    CURLOPT_POST           => true,
		    CURLOPT_POSTFIELDS     => sprintf($this->url_params, $this->login_email, $this->login_pass)
		);
		
		$result = $this->getURL($url, $config);
		
		preg_match("/error_string = \"(.*?)\";/", $result, $matches);
		
		if(isset($matches[1]) && !empty($matches[1])) {
			$this->error_found  = true;
			$this->error_string = trim($matches[1]);
			
			return false;
		}
		
		$this->login_done = true;
		
		return true;
		
	}
	
	private function getURL($url = "", $config = array()) {
		$this->curl = curl_init();

		$config[ CURLOPT_URL ] = $url;

		curl_setopt_array($this->curl, $config);
		curl_setopt_array($this->curl, $this->curl_config);
		
		if(!$result = curl_exec($this->curl)) {
			$this->error_found  = true;
			$this->error_string = curl_error($this->curl);
			
			return false;
		}

		curl_close($this->curl);
		
		return $result;
		
	}
}
?>