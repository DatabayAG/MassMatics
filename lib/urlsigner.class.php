<?php
	/**
	 * Signiert URLs mit einer MAC (Message Authentication Code)
	 * @author Wolfgang Kimmig / Hochschule Offenburg
	 * @date 23.5.2013
	 */
	class URLSigner {
		
		/**
		 * Lebenszeit der URL in Sekunden.
		 * Wenn 0, dann ist die URL unendlich gueltig.
		 */
		private $lifetime;
		
		public function __construct(){
			$this->lifetime = 3*60;
		}
		
		/**
		 * Setzt die Lebenszeit einer URL.
		 * @param Integer $t Die Lebenszeit in Sekunden.
		 */
		public function setLifetime($t){
			$this->lifetime = $t+0;
		}
		
		/**
		 * Liefert die Lebenszeit einer URL.
		 * @return Integer $t Die Lebenszeit in Sekunden.
		 */
		public function getLifetime(){
			return $this->lifetime+0;
		}
		
		/**
		 * Haengt an eine URL einen MAC an.
		 * @param String $url Zu signierende Nachricht.
		 * @return String $url/<UNIX-Timestamp>/<MAC>
		 */
		public function appendMAC($url, $password){
			$ret = "";
			if($url && $password){
				$ts = time();
				$parsedURL = $this->parseURL($url);
								
				// Entfernen der Parameter t und m um Namenskollisionen zu vermeiden.
				if(isset($parsedURL['query_array']['t'])) {unset($parsedURL['query_array']['t']);}
				if(isset($parsedURL['query_array']['m'])) {unset($parsedURL['query_array']['m']);}
				
				$mac = $this->getMAC($parsedURL['query_array'], $password, $ts);
				
				$parsedURL['query_array']['t'] = $ts;
				$parsedURL['query_array']['m'] = $mac;
				
				$ret = $this->unparseURL($parsedURL);
			}
			return $ret;
		}
		
		/**
		 * Liefert alle Parameter, die an einer URL haengen.
		 * @param String Die URL.
		 * @return Array Die URL-Parameter.
		 */
		private function getURLParams($url){
			$u = $this->parseURL($url);
			return $u['query_array'];
		}
		
		/**
		 * Parst eine URL in seine Bestandteile.
		 * @param String Die URL.
		 * @return Array Die Bestandteile der URL.
		 */
		private function parseURL($url){
			$parts = parse_url($url);
			$query = array();
			parse_str($parts['query'], $query);
			$parts['query_array'] = $query;
			return $parts;
		}
		
		/**
		 * Inverse Funktion zu parseURL() Erzeugt aus einer geparsten URL 
		 * wieder einen URL-String.
		 * @link http://php.net/manual/de/function.parse-url.php
		 * @param Array Die geparste URL.
		 * @return String die URL.
		 */
		private function unparseURL($parsedURL) { 
			$scheme   = isset($parsedURL['scheme']) ? $parsedURL['scheme'] . '://' : ''; 
			$host     = isset($parsedURL['host']) ? $parsedURL['host'] : ''; 
			$port     = isset($parsedURL['port']) ? ':' . $parsedURL['port'] : ''; 
			$user     = isset($parsedURL['user']) ? $parsedURL['user'] : '';
			$ct_id     = isset($parsedURL['ct_id']) ? $parsedURL['ct_id'] : '';  
			$pass     = isset($parsedURL['pass']) ? ':' . $parsedURL['pass']  : ''; 
			$pass     = ($user || $pass) ? "$pass@" : ''; 
			$path     = isset($parsedURL['path']) ? $parsedURL['path'] : ''; 
			if(is_array($parsedURL['query_array'])){
				$parsedURL['query'] = $this->buildQuery($parsedURL['query_array']);
			}
			$query    = isset($parsedURL['query']) ? '?' . $parsedURL['query'] : ''; 
			$fragment = isset($parsedURL['fragment']) ? '#' . $parsedURL['fragment'] : ''; 

			return "$scheme$user$ct_id$pass$host$port$path$query$fragment"; 
		}
		
		/**
		 * Erzeugt aus einem assoziativem Array den Query-String.
		 * @param Array $queryArray Das Anfrage-Array.
		 * @return String Der Query-String.
		 */
		private function buildQuery($queryArray){
			$ret = '';
			if(is_array($queryArray)){
				$splits = array();
				foreach($queryArray as $key => $val){
					$splits[] = urlencode($key)."=".urlencode($val);
				}
				$ret = join('&', $splits);
			}
			return $ret;
		}
		
		/**
		 * Liefert den MAC.
		 * @param String $url Die Basis-URL ohne Query-String.
		 * @param Array $params Die Query-Parameter.
		 * @param String $password Das Passwort fuer den MAC.
		 * @param Integer $timestamp Der Timestamp, bis zu dem die URL gueltig ist.
		 * @return String Message Authentication Code.
		 */
		private function getMAC($params, $password, $timestamp){
			$data = '';
			if(count($params) > 0){
				// Sortieren der Parameter nach Namen
				ksort($params);
				// Alle Leerzeichen entfernen.
				foreach($params as $key => $p){
					$data .= $key.preg_replace("/\s*/", '', $p);
				}
			}
			if(strlen($data) < 8){
				$data = str_pad($data, 8, chr(0), STR_PAD_LEFT);
			}
			$hash = hash_hmac('sha256', $data, $timestamp.$password);
			
			// Verschleiern der Hash-Funktion.
			
			// String umdrehen.
			$hash = strrev($hash);
			
			if(strlen($hash) % 2 != 0){
				$hash .= '0'; // Auffuellen, wenn String ungeradzahlige Laenge hat.
			}
			// Byte n mit n+1 tauschen.
			for($i = 0; $i < strlen($hash); $i+=2){
				$tmp = $hash[$i];
				$hash[$i] = $hash[$i+1];
				$hash[$i+1] = $tmp;
			}
			
			// Ausschneiden eines bestimmten Bereiches aus dem Hash
			$hash = substr($hash, 2, 57);
			
			return $hash;
		}
		
		/**
		 * Prueft eine HTTP-Anfrage, ob der uebergebene
		 * MAC stimmig und noch gueltig ist.
		 * @param Array $r Die GET-Parameter.
		 * @param String $password Das Passwort.
		 * @return Boolean true, wenn der MAC in den Parametern Ok ist.
		 */
		public function checkRequest($r, $password){
			$ret = false;
			if(strlen(trim($password)) > 0 && is_array($r)){
				$lifeTimeOk = true;
				if($this->getLifetime() > 0){
					$ts = $r['t']+0;
					$lifeTimeOk = (time() - $ts+0) <= $this->getLifetime();
				}
				
				if($lifeTimeOk){
					$signedParams = $r;
					unset($signedParams['m']);
					unset($signedParams['t']);
					
					$myMac = $this->getMAC($signedParams, $password, $r['t']);
					$ret = ($myMac == $r['m']);
				}
				
			}
			return $ret;
		}
	}
?>