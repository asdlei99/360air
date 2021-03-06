<?php

class Flight_model extends CI_Model {
	function __construct() {
		parent::__construct();
	}
	
	/**
	 * Get Flight
	 * Given a flight ID query the database and return a new Flight object
	 * 
	 * @param $flight_id ID of the flight
	 * @return Flight object (if found), NULL if flight not found
	 */
	function getFlight($flight_id) {
		$q = $this->db->get_where('flights', array('flight_pk' => $flight_id), 1, 0);
		if($q->num_rows() == 1) {
			// Instantiate $flight with a Flight object and fill in the matching class properties
			$flight = $q->row(0, 'Flight');
			
			// Lookup the depart airport code
			$dq = $this->db->get_where('airports', array('airport_pk' => $flight->depart_airport_id), 1, 0);
			if($dq->num_rows() == 1) {
				$flight->depart_airport_code = $dq->row()->code;
				$flight->depart_airport_name = $dq->row()->name;	
			}
			$dq->free_result();
			
			// Lookup the arrival airport code
			$aq = $this->db->get_where('airports', array('airport_pk' => $flight->arrival_airport_id), 1, 0);
			if($aq->num_rows() == 1) {
				$flight->arrival_airport_code = $aq->row()->code;
				$flight->arrival_airport_name = $aq->row()->name;
			}
			$aq->free_result();
			
			return $flight;
		} else {
			return NULL;
		}
	}
	
	/**
	 * Add a flight to the database
	 *
	 * @param $flt Flight object
	 * @return the flight_pk for the new row if successful, -1 otherwise
	 */
	function addFlight(Flight $flt) {
		// Null the pk before trying to insert
		$flt->flight_pk = NULL;
		
		$this->db->insert('flights', $flt->get_db_vars());
		
		if($this->db->affected_rows() == 1) {
			
			// Return the flight_pk of the newly added row
			$flight = $this->db->order_by('flight_pk', 'desc')->limit(1)->get('flights')->row();
			return $flight->flight_pk;
		}
			
			
		return -1;
	}
	
	/**
	 * Updates a flight with the pk in the Flight object with the rest of the information
	 * 
	 * @param $flt Flight object, $reason reason for the update, $accountId id of the account doing the update
	 * @return the flight_pk for the affected row if successful, -1 otherwise
	 */	
	function updateFlight(Flight $flt, $reason, $accountId) {
		$this->db->where('flight_pk', $flt->flight_pk);
		$this->db->update('flights', $flt->get_db_vars());
		
		if($this->db->affected_rows() == 1) {
			// Insert a flight modification
			$mod = array(
				'comment'              	=> 'UPDATE: ' . $reason,
				'account_id'            => $accountId,
				'flight_id'             => $flt->flight_pk,
				'time'            		=> now(),
			);
			$this->db->insert('flight_modifications', $mod);
			return $flt->flight_pk;
		}
		return -1;
	}
	
	/**
	 * Removes a flight with the pk in the Flight object.  Cannot be undone
	 * 
	 * @param $flt Flight object, $reason reason for the update, $accountId id of the account doing the update
	 * @return TRUE if successful, FALSE otherwise
	 */
	function removeFlight(Flight $flt, $reason, $accountId) {
		$this->db->where('flight_pk', $flt->flight_pk);
		$this->db->delete('flights');
		
		if($this->db->affected_rows() == 1) {
			// Insert a flight modification
			$mod = array(
				'comment'              	=> 'REMOVED: ' . $reason,
				'account_id'            => $accountId,
				'flight_id'             => $flt->flight_pk,
				'time'            		=> now(),
			);
			$this->db->insert('flight_modifications', $mod);
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Returns a list of at most 5 recent flights taken for a given user
	 * 
	 * @param $accountId to match the Account id of the user
	 * @return List of flights of max size 5
	 */
	function getRecentFlights($accountId) {
		// Get the flights with the corresponding account id
		$orders = $this->db->get_where('orders', array('account_id' => $accountId))->order_by('time', 'desc')->limit(5);
		
		$recentFlights = array();
		foreach($orders as $o) {
			$recentFlights[] = getFlight($o->flight_id);
		}		
		
		return $recentFlights;
	}
	
	/**
	 * Returns a list of the 5 most recently added flights to the flights table
	 * 
	 * @return List of flights of max size 5
	 */
	function getNewlyAddedFlights() {
		// Populate with the airport code and name for depart and arrival
		$this->db->join('airports as depart_airport', 'flights.depart_airport_id = depart_airport.airport_pk');
		$this->db->select('depart_airport.code as depart_airport_code, depart_airport.name as depart_airport_name, depart_airport.city as depart_airport_city, depart_airport.country as depart_airport_country');
		
		$this->db->join('airports as arrival_airport', 'flights.arrival_airport_id = arrival_airport.airport_pk');
		$this->db->select('arrival_airport.code as arrival_airport_code, arrival_airport.name as arrival_airport_name, arrival_airport.city as arrival_airport_city, arrival_airport.country as arrival_airport_country');
		
		$this->db->select('flights.*');
		
		$flights = $this->db->order_by('flight_pk', 'desc')->where('depart_time >=', now())->limit(10)->get('flights')->result();
		return $flights;
	}
	
	/**
	 * Returns a list of flights filtered by the parameters
	 * 
	 * @param 
	 * @return a list of flight data matching the search criteria
	 */
	function search($departAirport, $arrivalAirport, $departTimeStart, $departTimeEnd, $arriveTimeStart, $arriveTimeEnd, $classType, $isDomestic) {
		$this->db->from('flights');
		
		// Add filter parameters depending on what was passed
		if($classType != NULL) {
			$this->db->where('flights.class_type',$classType);
		}
		if($departTimeStart != NULL) {
			$this->db->where('depart_time >', $departTimeStart);
		}
		if($departTimeEnd != NULL) {
			$this->db->where('flights.depart_time <=', $departTimeEnd);
		}
		if($arriveTimeStart != NULL) {
			$this->db->where('flights.arrival_time >=', $arriveTimeStart);
		}
		if($arriveTimeEnd != NULL) {
			$this->db->where('flights.arrival_time <=', $arriveTimeEnd);
		}
		
		// Filter by depart airport
		if($departAirport != NULL) {
			// Look up the airport id using the code provided			
			$this->db->join('airports as d_airport', 'flights.depart_airport_id = d_airport.airport_pk');
			$this->db->like('d_airport.code',$departAirport, 'none');
		}
		// Filter by arrival airport
		if($arrivalAirport != NULL) {
			// Look up the airport id using the code provided			
			$this->db->join('airports as a_airport', 'flights.arrival_airport_id = a_airport.airport_pk');
			$this->db->like('a_airport.code',$arrivalAirport, 'none');
		}
		
		// Populate with the airport code and name for depart and arrival
		$this->db->join('airports as depart_airport', 'flights.depart_airport_id = depart_airport.airport_pk');
		$this->db->select('depart_airport.code as depart_airport_code, depart_airport.name as depart_airport_name, depart_airport.city as depart_airport_city, depart_airport.country as depart_airport_country');
		
		$this->db->join('airports as arrival_airport', 'flights.arrival_airport_id = arrival_airport.airport_pk');
		$this->db->select('arrival_airport.code as arrival_airport_code, arrival_airport.name as arrival_airport_name, arrival_airport.city as arrival_airport_city, arrival_airport.country as arrival_airport_country');
		

		if($isDomestic) {
			$this->db->where('depart_airport.is_domestic', '1');
			$this->db->where('arrival_airport.is_domestic', '1');
		}
		
		$this->db->select('flights.*');
		
		$flights = $this->db->get();
		
		return $flights->result();
	}
	
	/**
	 * Gets a list of airports including their airport code and name
	 * 
	 * @param $isDomestic indicates whether only domestic airports should be included
	 * @return a list of airport information
	 */
	function getAirports($isDomestic) {
		$this->db->from('airports');
		$this->db->select('airport_pk, code, name, city, country, is_domestic');
		if($isDomestic) {
			$this->db->where('is_domestic',1);
		}
		$airports = $this->db->get();
		
		return $airports->result();
	}
	
	/**
	 * 
	 * Returns the airport_pk corresponding to the given code
	 * 
	 * @param $airportCode
	 */
	function airportCodeToId($airportCode) {
		if($airportCode != NULL) {
			$this->db->from('airports');
			$this->db->select('airport_pk');
			$this->db->where('code', $airportCode);
			
			$a = $this->db->get()->row();
			if($a != NULL)
				return $a->airport_pk;
			return NULL;
		} else {
			return NULL;
		}
	}
}

?>