<?php
/**
 * This class allows one to get and parse the entries of a specified year in the Albo 
 * Pretorio of the municipality of Catania.
 *  
 * Copyright 2016 Cristiano Longo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cristiano Longo
 */

//the following url is url-encoded
define('ALBO_CT_URL','http://www.comune.catania.gov.it/EtnaInWeb/AlboPretorio.nsf/Web%20Ricerca?OpenForm&AutoFramed');

//number of months before today from which retrieve the notices
define("NMONTHS","3");

/**
 * Convenience class to represent single entry of the municipality of Catania Albo.
 *
 * @author Cristiano Longo
 *
 */
class AlboComuneCTEntry{
	public $anno;
	public $numero;
	public $repertorio;
	public $link;
	public $tipo;
	public $mittente_descrizione;

	/**
	 * Create an entry by parsing a table row.
	 */
	public function __construct($row) {
		$cells=$row->getElementsByTagName("td");
		$repertorioAnchorNodes=$cells->item(1)->getElementsByTagName("a");
		if ($repertorioAnchorNodes->length==0)
			throw new Exception("No anchor found in repertorio");
		if ($repertorioAnchorNodes->length>1)
			throw new Exception("Multiple anchor nodes found in repertorio");
		$repertorioAnchorNode=$repertorioAnchorNodes->item(0);
		$this->repertorio=$repertorioAnchorNode->textContent;
		
		$this->link="http://www.comune.catania.gov.it".$repertorioAnchorNode->getAttribute("href");
		$this->tipo=html_entity_decode(utf8_decode($cells->item(3)->textContent));
		$this->mittente_descrizione=html_entity_decode(utf8_decode($cells->item(4)->textContent));
	}
}
/**
 * Get and parse the entries of a single year of the Albo Pretorio of the municipality
 * of Catania.
 *
 * @author Cristiano Longo
 *
 */
class AlboComuneCTParser implements Iterator{

	private $rows;
	private $items;
	private $i=1;
	
	/**
	 *  Retrieve the entries relatives to the specified period.
	 *  @param $from_date a DateTime object
	 *  @param $to_date a DataTime object
	 */
	public function __construct($from_date) {		
		$page=$this->getPage($from_date);
		$this->rows=$this->getRows($page);
	}

	/**
	 * Factory method to construct an instance which retrieves entries from
	 * a default period of time ago.
	 */
	public static function create(){
		$date=new DateTimeImmutable();
		$pd=$date->sub(new DateInterval('P'.NMONTHS.'M'));
		$parser = new AlboComuneCTParser($pd);
		return $parser;
	}
	/**
	 * Retrieve the notices whose validity period falls entirely in 
	 * the specified one.
	 * 
	 * @param $from_date a DateTime object
	 * @param $to_date a DataTime object
	 */
	private function getPage($from_date){
		$h=curl_init(ALBO_CT_URL);
		if (!$h) throw new Exception("Unable to initialize cURL session");
		
		$start_year=$from_date->format('Y');
		$start_month=$from_date->format('m');
		$start_day=$from_date->format('d');
		
		curl_setopt($h, CURLOPT_POST, TRUE);
		curl_setopt($h, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($h, CURLOPT_POSTFIELDS, 
				array("__Click" => 0, 
						"%%Surrogate_gg1"=>1, "gg1"=>$from_date->format('d'),
						"%%Surrogate_mm1"=>1, "mm1"=>$from_date->format('m'),
						"%%Surrogate_aa1"=>1, "aa1"=>$from_date->format('Y')
				));
				
		//curl_setopt($h, CURLOPT_HTTPHEADER, array("Accept-Charset: utf-8"));
		$page=curl_exec($h);
		if( $page==FALSE)
			throw new Exception("Unable to execute POST request: ".curl_error($h));
		curl_close($h);
		return $page;
	}

	/**
	 * Extract the rows of the table containing the Albo Entries from a result page.
	 *
	 * @param string $page
	 */
	private function getRows($page){
		$d=new DOMDocument();
 		$d->loadHTML($page);
 		$tables=$d->getElementsByTagName("table");
 		if ($tables->length==0)
 			throw new Exception("No table element found");
 		if ($tables->length>1)
 			throw new Exception("Multiple table elements found");
		$rows=$tables->item(0)->getElementsByTagName("tr");
		return $rows; 			
	}
	
	//helper function
	/**
	 * Get the (first) item with the specified value in the repertorio property.
	 * Return null if no such entry exists.
	 */
	public function getByRepertorio($repertorio){
		if ($this->rows->length<2) 
			return null;
		for($j=1; $j<$this->rows->length; $j++){
			$entry=new AlboComuneCTEntry($this->rows->item($j));
			if (!strcmp($repertorio, $entry->repertorio))
				return $entry;
		}
		return null;
	}
	
	//Iterator functions,  see http://php.net/manual/en/class.iterator.php
	
	public function current(){
		if ($this->rows->length<2)
			return null;
		return new AlboComuneCTEntry($this->rows->item($this->i));
	}
	
	
	public function key (){
		return $this->i;
	}
	
	public function next(){
		if ($this->i<$this->rows->length)
			++$this->i;
	}
	
	public function rewind(){
		if ($this->rows->length>1)
			$this->i=1;
	}
	
	public function valid(){
		return $this->rows->length>1 && $this->i<$this->rows->length;
	}
}
?>