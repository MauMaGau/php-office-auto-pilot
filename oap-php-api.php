<?php

/**
 * Office Auto Pilot PHP API Wrapper Class
 *
 * Allows access to the Office Auto Pilot API via PHP
 *
 * @package: 	OAP-PHP-API
 * @author: 	Neal Lambert
 * @last_mod: 	by Neal Lambert 01/30/2013
 * @url: 		http://officeautopilot.com/
 * @api_url: 	http://officeautopilot.com/wp-content/uploads/2010/10/API_reference.pdf
 * @see_also: 	http://support.officeautopilot.com/entries/22308086-contacts-api
 */
 
class OAPAPI {
  
	//API CREDENTIALS
	var $Appid		= '';
	var $Key		= '';

	//API URL
	var $host		= 'http://api.moon-ray.com/';

	//SERVICES
	var $contact 	= 'cdata.php';
	var $product	= 'pdata.php';
	var $form		= 'fdata.php';
	
	
	/**
	* Init
	* @desc: Search OAP for data
	* @params:  $app_id, $key 
	*/
	
	function __construct($app_id=FALSE,$key=FALSE)
	{
		if(empty($app_id) OR empty($key))
			throw new Exception("Missing OAP API Appid or Key");
		
		$this->Appid	= $app_id;
		$this->Key		= $key;
	}

	/**
	* Search (contact,product,form)
	* @desc: Search OAP for data
	* @params:  $type (string) - contact,product,form; $data (array)
	* @access:  public
	* @return:  array of contacts, or products
	*/

	public function search($type=FALSE,$data=FALSE)
	{
		$data = array('equation' => $data);
	
		if($service = $this->_service($type))
		{
			return $this->_request($service,'fetch',$data);
		}
		
		return FALSE;
	}
  
  
	/**
	* Fetch (contact,product,form)
	* @desc: This is return a list of tags that you can use in your system
	* @params:  $type (string) - contact,product,form; $data (array)(contact,product) or (string) for (form)
	* @access:  public
	* @return:  array of contacts, or products
	*/

	public function fetch($type=FALSE,$data=FALSE)
	{
		if($service = $this->_service($type))
		{
			$xml = '';
		
			switch($service)
			{
				//CONTACTS
				case 'cdata.php':
					foreach ($data as $contact_id)
					$xml .= '<contact_id>'.$contact_id.'</contact_id>';
					break;
				//PRODUCTS
				case 'pdata.php':
					foreach ($data as $product_id)
					$xml .= '<product_id>'.$product_id.'</product_id>';
					break;
				//FORMS
				case 'fdata.php':
					$xml .= 'id='.$data;
					break;
			}
			
			return $this->_request($service,'fetch', $xml);
		}
		
		return FALSE;
	}
  
	/**
	* Fetch Tags Type (contact)
	* @desc: List of tag names in the account. Recommended to use Pull Tag instead of this function.
	* @access:  public
	* @return:  array of tags
	*/

	public function fetch_tags_type()
	{
		$return = $this->_request($this->contact,'fetch_tag',FALSE);
		
		if(!empty($return->tags))
		{
			$tags = explode('*/*',$return->tags);
	
		
			return (is_array($tags) ? array_filter($tags) : $tags);
		}
		
		return FALSE;
	}
	
	/**
	* Fetch Sequences Type (contact)
	* @desc: This is return a list of tags that you can use in your system
	* @access:  public
	* @return:  array of sequences e.g. [24] =>  'sequence name which has id 24'
	*/

	public function fetch_sequences_type()
	{
		$sequences = FALSE;
		
		//MAKE API REQUEST
		$return = $this->_request($this->contact,'fetch_sequences',FALSE);
		
		//CONVERT TO ARRAY
		if(!empty($return->sequence))
		{
			foreach($return->sequence as $sequence)
				$sequences[(string)$sequence->attributes()->id] = (string)$sequence;
		}
		
		return $sequences;
	}
	
	/**
	* Key Type (contact, product)
	* @desc: The Key Type is used to visually map out all the fields that are used for a contact on your system. The
			 fields are organized in groups.
	* @access:  public
	* @return:  array of tags
	*/

	public function key_type($type=FALSE)
	{
		if($service = $this->_service($type))
		{
			return $this->_request($service,'key','');
		}
		
		return FALSE;
	}
	
	/**
	* Pull Tag (contact)
	* @desc: List of tag names in the account with corresponding ids
	* @access:  public
	* @return:  array of tags
	*/

	public function pull_tag()
	{
		$return = $this->_request($this->contact,'pull_tag',FALSE);
		
		$tags = array();
		
		if(!empty($return->tag))
			foreach($return->tag as $tag)
				$tags[(int)$tag->attributes()->id] = (string)$tag;
		
		return $tags;
	}
	
	/**
	* (Private) Service
	* @desc: Checks to see if the service name is valid and returns the service URL
	* @params: $key (string)
	* @access:  private
	* @return:  object
	*/
	
	private function _service($key)
	{
		switch ($key) 
		{
			case 'contact':
				return $this->contact;
				break;
			case 'contacts':
				return $this->contact;
				break;	
			case 'product':
				return $this->product;
				break;
			case 'products':
				return $this->product;
				break;
			case 'form':
				return $this->form;
				break;
			case 'forms':
				return $this->form;
				break;
			default:
				return FALSE;
				break;
		}
	}
   
	/**
	* (Private) Request
	* @desc: Make a request to the Office Auto Pilot XML Rest API
	* @params: $service (string),$reqType (string), $data_xml (string),$return_id (boolean), $f_add(boolean)
	* @access:  private
	* @return:  object
	*/

	private function _request($service,$reqType,$data=FALSE,$return_id=FALSE,$f_add=FALSE)
	{	
		$postargs = "Appid=".$this->Appid."&Key=".$this->Key."&reqType=".$reqType.($return_id ? '&return_id=1' : '&return_id=1').($data ? '&data='.urlencode($data) : '').($f_add ? '&f_add=1' : '');
		
		$ch = curl_init($this->host.'/'.$service);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postargs);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$output = curl_exec($ch);
		curl_close($ch);
		
		//DEBUG
		//print_r($output);
		
		return new SimpleXMLElement($output);
	}
	
}

/* End of file oap-php-api.php */
/* Location: ./oap-php-api */