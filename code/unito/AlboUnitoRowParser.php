<?php 
/**
 * Parse single rows in the notice board of the university of Torino (http://unito.it).
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

//require('../phpparsing/AlboTableParser.php');
require('AlboUnitoEntry.php');

class AlboUnitoRowParser implements AlboRowParser{

	/**
	 * Convert a table row into an Albo-specific entry object.
	 *
	 * @param DOMElement $row
	 */
	function parseRow($row){
		$e=new AlboUnitoEntry();
		$tds=$row->getElementsByTagName('td');
		$e->numero_repertorio=$tds->item(0)->textContent;
		$e->data_inserimento=DateTime::createFromFormat('d/m/Y', $tds->item(1)->textContent);
		$e->anno_repertorio=$e->data_inserimento->format('Y');
		$e->struttura=$tds->item(2)->textContent;
		$e->oggetto=$tds->item(3)->textContent;
		$e->inizio_pubblicazione=DateTime::createFromFormat('d/m/Y', $tds->item(4)->textContent);
		$e->fine_pubblicazione=DateTime::createFromFormat('d/m/Y', $tds->item(5)->textContent);
		return $e;
	}
}
?>