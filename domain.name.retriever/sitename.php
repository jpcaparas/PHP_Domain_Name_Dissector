<?php

Class SiteName {
	
	public $name; //the name of the site --> our target
	public $ext; //the domain extension
	public $extlist; //the file containing all valid extensions
	public $validexts; //the extlist file converted into an array
	public $subdomain; //the subdomain
	public $url; //the url / hostname from which we will get the sitename
	
	public function __construct($params=array()) {
		$this->check_defined_params($params); //some basic househeekping
		$this->sanitize(); //check if the URL is using HTTP or HTTPS parameters
		$this->process(); //the main function to get the domain name, extension (based on the TLD's on the .dat file), and subdomain
	}
	
	public function check_defined_params($params=array()) {
		$object_vars = get_object_vars($this);
		foreach ($object_vars as $var => $value) {
			if (isset($params[$var])) {
				$this->$var = $params[$var];
			} else {
				$this->$var = false;
			}
		}
	}
	
	public function sanitize() {
		if (!$this->url) $this->url = $_SERVER['HTTP_HOST'];
		$rgx = '|http(s)?://|';
		$this->url = preg_replace($rgx, '', $this->url);
	}
	
	public function process() {
		$local_ips = array('127.0.0.1','::1');
		$local = in_array($_SERVER['REMOTE_ADDR'], $local_ips) ? true : false;

		if ($local) {
			$file  = __DIR__ . '/extlist.dat';
			if (!$extlist = file($file)) {
				exit("Error loading Domain EXT list from local server");
			}
		} else {
			if (!$extlist = file('http://mxr.mozilla.org/mozilla-central/source/netwerk/dns/effective_tld_names.dat?raw=1%22')) {
				exit("Error loading Domain EXT list from mozilla.org");
			}
		}

		$exts = array();
		foreach ($extlist as $ext) {
			$rgx = '|^//(.*)|';
			if (!preg_match($rgx, $ext)) {
				$rgx = '|^[a-zA-Z0-9*]|';
				if (preg_match($rgx, $ext)) {
					if (trim(htmlspecialchars(($ext))) != "") {
						$rgxext = preg_replace('|\.|','\.',$ext);
						$exts[trim($ext)] = '\.' . trim(preg_replace('|\*|','[^.]+',trim($rgxext))) . '$';
					}
				}
				$this->validexts = $exts;
			}
		}
		
		foreach ($this->validexts as $ext => $rgx) {
			preg_match("|$rgx|", $this->url, $m);
			if (!empty($m)) {
				$this->ext = substr($m[count($m)-1], 1);
			}
		}
		
		if ($this->ext) {
			$extlength = strlen($this->ext);
			$sitename = substr($this->url, 0, -($extlength+1));
			while (strstr($sitename, '.')) {
				$pos = strpos($sitename, '.');
				$this->subdomain .= substr($sitename, 0, $pos+1);
				$sitename = substr($sitename, $pos+1);
			}
			$pos = strripos($this->subdomain,'.');
			$this->subdomain = substr($this->subdomain, 0, $pos );
			$this->name = $sitename;
		} else {
			die("You are not using valid domain extension");
		}
	}
	
}


?>