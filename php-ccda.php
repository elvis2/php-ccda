<?php
////////////////////////////////////////////////////////////////////////////////
// 
// php-ccda
// John Schrom
// http://john.mn
// 
// Objective: Parse a CCDA XML string into discrete health data elements.
//

////////////////////////////////////////////////////////////////////////////////


class Ccda {

	function __construct($string = '') {

		// Initialize variables
		$this->xml = false;
		$this->rx = array();
		$this->dx = array();
		$this->lab = array();
		$this->immunization = array();
		$this->proc = array();
		$this->vital = array();
		$this->allergy = array();
		$this->enc = array();
		$this->plan = array();

		// If data was passed with constructor, parse it.
		if ($string != '') {
			if(!is_object($string)) {
				$string = simplexml_load_string($string);
			}
			$this->load_xml($string);		
		}
	}
	
	function construct_json() {
		$patient = $this->demo;
		$patient->provider = $this->provider;
		$patient->rx = $this->rx;
		$patient->dx = $this->dx;
		$patient->lab = $this->lab;
		$patient->immunizaiton = $this->immunization;
		$patient->proc = $this->proc;
		$patient->vital = $this->vital;
		$patient->allergy = $this->allergy;
		$patient->enc = $this->enc;
		$patient->care_plan = $this->plan;
		return json_encode($patient, JSON_PRETTY_PRINT);
	}
	
	function load_xml($xmlObject) {
		$this->xml = $xmlObject;
		$this->parse();
		return true;
	}
	
	private function parse() {
		// Parse demographics
		$this->parse_demo($this->xml->recordTarget->patientRole);
		
		// Parse components
		$xmlRoot = $this->xml->component->structuredBody;
		$i = 0;
		while(is_object($xmlRoot->component[$i])) {
			$test = $xmlRoot->component[$i]->section->templateId->attributes()->root;
			
			// Medications
			if ($test == '2.16.840.1.113883.10.20.22.2.1.1'){
				$this->parse_meds($xmlRoot->component[$i]->section);
			}
			// Allergies
			else if ($test == '2.16.840.1.113883.10.20.22.2.6.1') {
				$this->parse_allergies($xmlRoot->component[$i]->section);
			}
			
			// Encounters
			else if ($test == '2.16.840.1.113883.10.20.22.2.22' or
					   $test == '2.16.840.1.113883.10.20.22.2.22.1') {
				$this->parse_enc($xmlRoot->component[$i]->section);
			}
			
			// Immunizations
			else if ($test == '2.16.840.1.113883.10.20.22.2.2.1' or 
					 $test == '2.16.840.1.113883.10.20.22.2.2') {
				$this->parse_immunizations($xmlRoot->component[$i]->section);
			}
			
			// Labs
			else if ($test == '2.16.840.1.113883.10.20.22.2.3.1') {
				$this->parse_labs($xmlRoot->component[$i]->section);
			}

			// Problems
			else if ($test == '2.16.840.1.113883.10.20.22.2.5.1' or 
					 $test == '2.16.840.1.113883.10.20.22.2.5') {
				$this->parse_dx($xmlRoot->component[$i]->section);
			}

			// Procedures
			else if ($test == '2.16.840.1.113883.10.20.22.2.7.1' or 
					 $test == '2.16.840.1.113883.10.20.22.2.7') {
				$this->parse_proc($xmlRoot->component[$i]->section);
			}
			
			// Vitals
			if ($test == '2.16.840.1.113883.10.20.22.2.4.1') {
				$this->parse_vitals($xmlRoot->component[$i]->section);
			}
			
			// Care Plan
			if ($test == '2.16.840.1.113883.10.20.22.2.10') {
  			$this->parse_careplan($xmlRoot->component[$i]->section);
			}
			
			$i++;
		}
		
		return true;
	}
	
	private function parse_meds($xmlMed) {
		foreach($xmlMed->entry as $entry) {
			$n = count($this->rx);
			$this	->rx[$n]
					  ->date_range
            ->start 	    	= (string) $entry	->substanceAdministration
													    	              ->effectiveTime
                                              ->low
                                              ->attributes()
                                              ->value;
			$this ->rx[$n]
			      ->date_range
			      ->end          = (string) $entry	->substanceAdministration
																    		      ->effectiveTime
                                              ->high
                                              ->attributes()
                                              ->value;
			$this ->rx[$n]
			      ->product_name = (string) $entry	->substanceAdministration
													          					->consumable
                                              ->manufacturedProduct
                                              ->manufacturedMaterial
                                              ->code
                                              ->attributes()
                                              ->displayName;
			$this ->rx[$n]
			      ->product_code = (string) $entry	->substanceAdministration
																	          	->consumable
                                              ->manufacturedProduct
                                              ->manufacturedMaterial
                                              ->code
                                              ->attributes()
                                              ->code;										
			$this ->rx[$n]
			      ->product_code_system = (string) $entry ->substanceAdministration
													                					->consumable
                                                    ->manufacturedProduct
                                                    ->manufacturedMaterial
                                                    ->code
                                                    ->attributes()
                                                    ->codeSystem;
			$this ->rx[$n]
            ->translation
            ->name          = (string) $entry	->substanceAdministration
														          				->consumable
                                              ->manufacturedProduct
                                              ->manufacturedMaterial
                                              ->code
                                              ->translation
                                              ->attributes()
                                              ->displayName;

			$this ->rx[$n]
			      ->translation
			      ->code_system   = (string) $entry	->substanceAdministration
																	          	->consumable
                                              ->manufacturedProduct
                                              ->manufacturedMaterial
                                              ->code
                                              ->translation
                                              ->attributes()
                                              ->codeSystemName;

			$this ->rx[$n]
			      ->translation
			      ->code          = (string) $entry	->substanceAdministration
										          								->consumable
                                              ->manufacturedProduct
                                              ->manufacturedMaterial
                                              ->code
                                              ->translation
                                              ->attributes()
                                              ->code;			
			$this ->rx[$n]
			      ->dose_quantity
			      ->value         = (string) $entry	->substanceAdministration
												          						->doseQuantity
                                              ->attributes()
                                              ->value;
			$this ->rx[$n]
			      ->dose_quantity
			      ->unit          = (string) $entry	->substanceAdministration
													          					->doseQuantity
                                              ->attributes()
                                              ->unit;
		}
		return true;
	}
	
	private function parse_demo($xmlDemo) {
		// Extract Demographics
		$this ->demo
		      ->addr
		      ->street 		     = array(	
		                        (string)	$xmlDemo	->addr
																			          ->streetAddressLine[0],
                            (string)	$xmlDemo	->addr
																		            ->streetAddressLine[1]);
		$this ->demo
		      ->addr
		      ->city			    = (string) $xmlDemo	->addr
															                ->city;
		$this ->demo
		      ->addr
		      ->state 		    = (string) $xmlDemo	->addr
														                	->state;
		$this ->demo
		      ->addr
		      ->postalCode 	  = (string) $xmlDemo	->addr
															                ->postalCode;
		$this ->demo
		      ->addr
		      ->country 		  = (string) $xmlDemo	->addr
															                ->country;
		$this ->demo
		      ->phone
		      ->number 		    = (string) $xmlDemo	->telecom
														                	->attributes()
                                              ->value;
		$this ->demo
		      ->phone
		      ->use 		      = (string) $xmlDemo	->telecom
														                	->attributes()
                                              ->use;
		$this ->demo
		      ->name
		      ->first 		    = (string) $xmlDemo	->patient
														                	->name
                                              ->given;
		$this ->demo
		      ->name
		      ->last 		      = (string) $xmlDemo	->patient
													                		->name
                                              ->family;
		$this ->demo
		      ->gender 			  = (string) $xmlDemo	->patient
													                  	->administrativeGenderCode
                                              ->attributes()
                                              ->code;
		$this ->demo
		      ->birthdate 		= (string) $xmlDemo	->patient
														                	->birthTime
                                              ->attributes()
                                              ->value[0];
		$this ->demo
		      ->maritalStatus = (string) $xmlDemo	->patient
											                 				->maritalStatusCode
                                              ->attributes()
                                              ->displayName;
		$this ->demo
		      ->race 			   	= (string) $xmlDemo	->patient
													                		->raceCode
                                              ->attributes()
                                              ->displayName;
		$this ->demo
		      ->ethnicity 		= (string) $xmlDemo	->patient
													                		->ethnicGroupCode
                                              ->attributes()
                                              ->displayName;
		$this ->demo
		      ->language 			= (string) $xmlDemo	->patient
													                		->languageCommunication
                                              ->languageCode
                                              ->attributes()
                                              ->code;
		
		// Extract provider info
		$this ->provider
		      ->organization
		      ->name 			     = (string) $xmlDemo  ->providerOrganization
																		            ->name;
		$this ->provider
		      ->organization
		      ->phone 			   = (string) $xmlDemo  ->providerOrganization
																		          	->telecom
                                                ->attributes()
                                                ->value;
		$this ->provider
		      ->organization
		      ->addr
		      ->street 	       = 	array(
		                          (string) $xmlDemo ->providerOrganization
																		            ->addr
                                                ->streetAddressLine[0],
                              (string) $xmlDemo ->providerOrganization
																	          		->addr
                                                ->streetAddressLine[1]);
		$this ->provider
		      ->organization
		      ->addr
		      ->city 		       = (string) $xmlDemo  ->providerOrganization
																	          	  ->addr
                                                ->city;
		$this ->provider
		      ->organization
		      ->addr
		      ->state 		     = (string) $xmlDemo  ->providerOrganization
																			          ->addr
                                                ->state;
		$this ->provider
		      ->organization
		      ->addr
		      ->postalCode     = (string) $xmlDemo  ->providerOrganization
																		          	->addr
                                                ->postalCode;
		$this ->provider
		      ->organization
		      ->addr
		      ->country 	     = (string) $xmlDemo  ->providerOrganization
																	          		->addr
                                                ->country;
		return true;
	}
	
	private function parse_allergies($xmlAllergy) {
		foreach($xmlAllergy->entry as $entry) {
			$n = count($this->allergy);
			$this ->allergy[$n]
			      ->date_range
			      ->start	       =	(string) $entry	->act
																	          	->effectiveTime
                                              ->low
                                              ->attributes()
                                              ->value;
			$this ->allergy[$n]
			      ->date_range
			      ->end		       =	(string) $entry	->act
												          						->effectiveTime
                                              ->high
                                              ->attributes()
                                              ->value;
			$this ->allergy[$n]
			      ->name				=	(string) $entry	->act
															        			->entryRelationship
                                            ->observation
                                            ->code
                                            ->attributes()
                                            ->displayName;
			$this ->allergy[$n]
			      ->code				=	(string) $entry	->act
																        		->entryRelationship
                                            ->observation
                                            ->code
                                            ->attributes()
                                            ->code;
			$this ->allergy[$n]
			      ->code_system	=	(string) $entry	->act
										        								->entryRelationship
                                            ->observation
                                            ->code
                                            ->attributes()
                                            ->codeSystem;
			$this ->allergy[$n]
			      ->code_system_name	=	(string) $entry	->act
																              		->entryRelationship
                                                  ->observation
                                                  ->code
                                                  ->attributes()
                                                  ->codeSystemName;
			$this ->allergy[$n]
			      ->allergen
            ->name		    =	(string) $entry	->act
														        				->entryRelationship
                                            ->observation
                                            ->participant
                                            ->participantRole
                                            ->playingEntity
                                            ->code
                                            ->attributes()
                                            ->displayName;
			$this ->allergy[$n]
			      ->allergen
            ->code		    =	(string) $entry	->act
											        							->entryRelationship
                                            ->observation
                                            ->participant
                                            ->participantRole
                                            ->playingEntity
                                            ->code
                                            ->attributes()
                                            ->code;
			$this ->allergy[$n]
			      ->allergen
			      ->code_system		=	(string) $entry	->act
														          				->entryRelationship
                                              ->observation
                                              ->participant
                                              ->participantRole
                                              ->playingEntity
                                              ->code
                                              ->attributes()
                                              ->codeSystem;
			$this ->allergy[$n]
			      ->allergen
			      ->code_system_name		=	(string) $entry	->act
																                		->entryRelationship
                                                    ->observation
                                                    ->participant
                                                    ->participantRole
                                                    ->playingEntity
                                                    ->code
                                                    ->attributes()
                                                    ->codeSystemName;
			$this ->allergy[$n]
			      ->reaction_type
			      ->name 		         = (string) $entry 	->act
												          								->entryRelationship
                                                  ->observation
                                                  ->value
                                                  ->attributes()
                                                  ->displayName;
			$this ->allergy[$n]
			      ->reaction_type
			      ->code 		         = (string) $entry 	->act
														          						->entryRelationship
                                                  ->observation
                                                  ->value
                                                  ->attributes()
                                                  ->code;
			$this ->allergy[$n]
			      ->reaction_type
			      ->code_system 		= (string) $entry 	->act
															          					->entryRelationship
                                                  ->observation
                                                  ->value
                                                  ->attributes()
                                                  ->codeSystem;
			$this ->allergy[$n]
			      ->reaction_type
			      ->code_system_name 	= (string) $entry 	->act
																	            			->entryRelationship
                                                    ->observation
                                                    ->value
                                                    ->attributes()
                                                    ->codeSystemName;

      $entryRoot = $entry->act->entryRelationship->observation;
			foreach($entryRoot->entryRelationship as $detail) {
			  if (!is_object($detail->observation->templateId)) continue;
				$test = $detail->observation->templateId->attributes()->root;
        $varname = '';

				if ($test == '2.16.840.1.113883.10.20.22.4.9') {
				  $varname = 'reaction';
				  }
				if ($test == '2.16.840.1.113883.10.20.22.4.8') {
				  $varname = 'severity';
				  }
				
				if ($varname != '') {
				  $this ->allergy[$n]
					      ->{$varname}
					      ->name		    = (string) $detail	->observation
																		              ->value
                                                  ->attributes()
                                                  ->displayName;
				  $this ->allergy[$n]
					      ->{$varname}
					      ->code		    = (string) $detail	->observation
																		              ->value
                                                  ->attributes()
                                                  ->code;
				  $this ->allergy[$n]
					      ->{$varname}
					      ->code_system	= (string) $detail	->observation
																		              ->value
                                                  ->attributes()
                                                  ->codeSystem;
				  $this ->allergy[$n]
					      ->{$varname}
					      ->code_system_name	= (string) $detail	->observation
																		                    ->value
                                                        ->attributes()
                                                        ->codeSystemName;
          }
        }
      }
		return true;
	}

	private function parse_immunizations($xmlImm) {
		foreach($xmlImm->entry as $entry) {
			$n = count($this->immunization);
			$entryRoot = $entry->substanceAdministration;
			$this ->immunization[$n]
			      ->date			      	= (string)	$entryRoot	->effectiveTime
																                    		->attributes()
                                                        ->value;
			$this ->immunization[$n]
			      ->product
            ->name		          = (string)	$entryRoot	->consumable
												                    						->manufacturedProduct
                                                        ->manufacturedMaterial
                                                        ->code
                                                        ->attributes()
                                                        ->displayName;
			$this ->immunization[$n]
			      ->product
			      ->code		          = (string)	$entryRoot	->consumable
															                    			->manufacturedProduct
                                                        ->manufacturedMaterial
                                                        ->code
                                                        ->attributes()
                                                        ->code;
			$this ->immunization[$n]
			      ->product
			     ->code_system	      = (string)	$entryRoot	->consumable
													                    					->manufacturedProduct
                                                        ->manufacturedMaterial
                                                        ->code
                                                        ->attributes()
                                                        ->codeSystem;
			$this ->immunization[$n]
			      ->product
			      ->code_system_name	= (string)	$entryRoot	->consumable
															                    			->manufacturedProduct
                                                        ->manufacturedMaterial
                                                        ->code
                                                        ->attributes()
                                                        ->codeSystemName;
			$this ->immunization[$n]
			      ->product
			      ->translation
			      ->name		          = (string)	$entryRoot	->consumable
														                    				->manufacturedProduct
                                                        ->manufacturedMaterial
                                                        ->code
                                                        ->translation
                                                        ->attributes()
                                                        ->displayName;
			$this ->immunization[$n]
			      ->product
			      ->translation
			      ->code		          = (string)	$entryRoot	->consumable
											                    							->manufacturedProduct
                                                        ->manufacturedMaterial
                                                        ->code
                                                        ->translation
                                                        ->attributes()
                                                        ->code;
			$this ->immunization[$n]
			     ->product
			     ->translation
			     ->code_system		    = (string)	$entryRoot	->consumable
														                    				->manufacturedProduct
                                                        ->manufacturedMaterial
                                                        ->code
                                                        ->translation
                                                        ->attributes()
                                                        ->codeSystem;
			$this ->immunization[$n]
			      ->product
			      ->translation
			      ->code_system_name	= (string)	$entryRoot	->consumable
														                    				->manufacturedProduct
                                                        ->manufacturedMaterial
                                                        ->code
                                                        ->translation
                                                        ->attributes()
                                                        ->codeSystemName;
			$this ->immunization[$n]
			     ->route
			     ->name		             = (string)	$entryRoot	->routeCode
										                    								->attributes()
                                                        ->displayName;
			$this ->immunization[$n]
			      ->route
			      ->code		          = (string)	$entryRoot	->routeCode
																	                    	->attributes()
                                                        ->code;
			$this ->immunization[$n]
			      ->route
			      ->code_system	      = (string)	$entryRoot	->routeCode
															                    			->attributes()
                                                        ->codeSystem;
			$this ->immunization[$n]
			      ->route
			     ->code_system_name 	= (string)	$entryRoot	->routeCode
															                    			->attributes()
                                                        ->codeSystemName;	
		}
		return true;
	}

	private function parse_labs($xmlLab) {
		foreach($xmlLab->entry as $entry) {
			$n = count($this->lab);
			
			$this ->lab[$n]
			      ->panel_name  = (string) $entry		->organizer
															              	->code
                                              ->attributes()
                                              ->displayName;
			$this ->lab[$n]
			      ->panel_code  = (string) $entry		->organizer
												              				->code
                                              ->attributes()
                                              ->code;
			$this ->lab[$n]
			      ->panel_code_system = (string) $entry	->organizer
																                  ->code
                                                  ->attributes()
                                                  ->codeSystem;
			$this ->lab[$n]
			      ->panel_code_system_name = (string) $entry	->organizer
													                    					->code
                                                        ->attributes()
                                                        ->codeSystemName;
			$this ->lab[$n]
			      ->results
			      ->date      = (string) $entry				->organizer
											            							->component
                                                ->observation
                                                ->effectiveTime
                                                ->attributes()
                                                ->value;
			$this ->lab[$n]
			      ->results
			      ->name      = (string) $entry				->organizer
										            								->component
                                                ->observation
                                                ->code
                                                ->attributes()
                                                ->displayName;
			$this ->lab[$n]
			      ->results
			      ->code      = (string) $entry				->organizer
										            								->component
                                                ->observation
                                                ->code
                                                ->attributes()
                                                ->code;
			$this ->lab[$n]
			      ->results
			      ->code_system = (string) $entry		->organizer
													          					->component
                                              ->observation
                                              ->code
                                              ->attributes()
                                              ->codeSystem;
			$this ->lab[$n]
			      ->results
			      ->code_system_name = (string) $entry	->organizer
															              			->component
                                                  ->observation
                                                  ->code
                                                  ->attributes()
                                                  ->codeSystemName;
			$this ->lab[$n]
			      ->results
			      ->value     = (string) $entry			->organizer
										          								->component
                                              ->observation
                                              ->value
                                              ->attributes()
                                              ->value;
			$this ->lab[$n]
			      ->results
			      ->unit      = (string) $entry				->organizer
												            						->component
                                                ->observation
                                                ->value
                                                ->attributes()
                                                ->unit;
			$this ->lab[$n]
			      ->results
			      ->code      = (string) $entry				->organizer
										            								->component
                                                ->observation
                                                ->code
                                                ->attributes()
                                                ->code;
			}
		return true;
	}

	private function parse_dx($xmlDx) {
		foreach($xmlDx->entry as $entry) {
			$n = count($this->dx);
			$this ->dx[$n]
			      ->date_range
			      ->start       = (string) $entry	->act
												            				->effectiveTime
                                            ->low
                                            ->attributes()
                                            ->value;
			$this ->dx[$n]
			      ->date_range
			      ->end         = (string) $entry	->act
										            						->effectiveTime
                                            ->high
                                            ->attributes()
                                            ->value;
			$this ->dx[$n]
			      ->name        = (string) $entry	->act
									            							->entryRelationship
                                            ->observation
                                            ->value
                                            ->attributes()
                                            ->displayName;
			$this ->dx[$n]
			      ->code        = (string) $entry	->act
											          	  				->entryRelationship
                                            ->observation
                                            ->value
                                            ->attributes()
                                            ->code;
			$this ->dx[$n]
			      ->code_system = (string) $entry	->act
													          		  	->entryRelationship
                                            ->observation
                                            ->value
                                            ->attributes()
                                            ->codeSystem;
			$this ->dx[$n]
			      ->translation
            ->name        = (string) $entry	->act
										            						->entryRelationship
                                            ->observation
                                            ->value
                                            ->translation
                                            ->attributes()
                                            ->displayName;
			$this ->dx[$n]
			      ->translation
            ->code        = (string) $entry	->act
									            							->entryRelationship
                                            ->observation
                                            ->value
                                            ->translation
                                            ->attributes()
                                            ->code;
			$this ->dx[$n]
			      ->translation
			      ->code_system = (string) $entry	->act
									            							->entryRelationship
                                            ->observation
                                            ->value
                                            ->translation
                                            ->attributes()
                                            ->codeSystem;
			$this ->dx[$n]
			      ->translation
			      ->code_system_name = (string) $entry	->act
									                  							->entryRelationship
                                                  ->observation
                                                  ->value
                                                  ->translation
                                                  ->attributes()
                                                  ->codeSystemName;
			$this ->dx[$n]
			      ->status = (string) $entry	->act
										    				    		->entryRelationship
                                        ->observation
                                        ->entryRelationship
                                        ->observation
                                        ->value
                                        ->attributes()
                                        ->displayName;		
		}
		return true;
	}

	private function parse_proc($xmlProc) {
		foreach($xmlProc->entry as $entry) {
			$n = count($this->proc);
			if(!is_object($entry->procedure->code)) continue;
			
			$this ->proc[$n]
			      ->date		= (string) $entry	->procedure
										              			->effectiveTime
                                        ->attributes()
                                        ->value;
			$this ->proc[$n]
			      ->name		= (string) $entry	->procedure
											              		->code
                                        ->attributes()
                                        ->displayName;
			$this ->proc[$n]
			      ->code		= (string) $entry	->procedure
										              			->code
                                        ->attributes()
                                        ->code;
			$this ->proc[$n]
			      ->code_system	= (string) $entry	->procedure
										                  			->code
                                            ->attributes()
                                            ->codeSystem;
			$this ->proc[$n]
			      ->performer
			      ->organization		= (string) $entry ->procedure
										                        		->performer
                                                ->assignedEntity
                                                ->addr
                                                ->name;
			$this ->proc[$n]
			      ->performer
			      ->street		= array(
			                    (string) $entry ->procedure
						                							->performer
                                          ->assignedEntity
                                          ->addr
                                          ->streetAddressLine[0],
													(string) $entry ->procedure
								                					->performer
                                          ->assignedEntity
                                          ->addr
                                          ->streetAddressLine[1]);
			$this ->proc[$n]
			      ->performer
			      ->city	    = (string) $entry ->procedure
						                							->performer
                                          ->assignedEntity
                                          ->addr
                                          ->city;
			$this ->proc[$n]
			      ->performer
			      ->state	    = (string) $entry ->procedure
												                	->performer
                                          ->assignedEntity
                                          ->addr
                                          ->state;
			$this ->proc[$n]
			      ->performer
			      ->zip	      = (string) $entry ->procedure
								                					->performer
                                          ->assignedEntity
                                          ->addr
                                          ->postalCode;
			$this ->proc[$n]
			      ->performer
			      ->country	  = (string) $entry ->procedure
										                			->performer
                                          ->assignedEntity
                                          ->addr
                                          ->country;
      if (is_object($entry  ->procedure
                            ->performer
                            ->assignedEntity
                            ->telecom)) {
        $this ->proc[$n]
  			      ->performer
  			      ->phone	    = (string) $entry ->procedure
  							                						->performer
                                            ->assignedEntity
                                            ->telecom
                                            ->attributes()
                                            ->value;	
        }
		}
		return true;
	}

	private function parse_vitals($xmlVitals) {
		foreach($xmlVitals->entry as $entry) {
			$n = count($this->vital);
			$this ->vital[$n]
			      ->date      = (string) $entry	->organizer
													          			->effectiveTime
                                          ->attributes()
                                          ->value;
      
      // Pull each vital sign for a given date
			$this ->vital[$n]
			      ->results   = array();
			$m = 0;
			foreach($entry->organizer->component as $component) {
				$this ->vital[$n]
				      ->results[$m]
				      ->name        = (string) $component	->observation
																			          	->code
                                                  ->attributes()
                                                  ->displayName;
				$this ->vital[$n]
				      ->results[$m]
				      ->code        = (string) $component	->observation
																			          	->code
                                                  ->attributes()
                                                  ->code;
				$this ->vital[$n]
				      ->results[$m]
				      ->code_system = (string) $component ->observation
														        				  		->code
                                                  ->attributes()
                                                  ->codeSystem;
				$this ->vital[$n]
				      ->results[$m]
				      ->code_system_name = (string) $component  ->observation
															                					->code
                                                        ->attributes()
                                                        ->codeSystemName;
				$this ->vital[$n]
				      ->results[$m]
				      ->value       = (string) $component	->observation
															          					->value
                                                  ->attributes()
                                                  ->value;
				$this ->vital[$n]
				      ->results[$m]
				      ->unit        = (string) $component	->observation
																          				->value
                                                  ->attributes()
                                                  ->unit;
				$m++;
			}	
		}
		return true;
	}
	private function parse_enc($xmlEnc) {
		foreach($xmlEnc->entry as $entry) {
			$n = count($this->enc);
			
			$this ->enc[$n]
			      ->date		= (string) $entry	->encounter
													          		->effectiveTime
                                        ->attributes()
                                        ->value;
			$this ->enc[$n]
			      ->name		= (string) $entry	->encounter
										          					->code
                                        ->attributes()
                                        ->displayName;
			$this ->enc[$n]
			      ->code		= (string) $entry	->encounter
											          				->code
                                        ->attributes()
                                        ->code;
			$this ->enc[$n]
			      ->code_system		= (string) $entry	->encounter
												                			->code
                                              ->attributes()
                                              ->codeSystem;
			$this ->enc[$n]
			      ->code_system_name		= (string) $entry	->encounter
													                      		->code
                                                    ->attributes()
                                                    ->codeSystemName;
			$this ->enc[$n]
			      ->code_system_version		= (string) $entry	->encounter
												                        			->code
                                                      ->attributes()
                                                      ->codeSystemVersion;
      if(is_object($entry ->encounter
                          ->entryRelationship
                          ->observation
                          ->value)) {
    			$this ->enc[$n]
    			      ->finding
    			      ->name		  = (string) $entry	->encounter
    											            				->entryRelationship
                                              ->observation
                                              ->value
                                              ->attributes()
                                              ->displayName;
    			$this ->enc[$n]
    			      ->finding
    			      ->code		  = (string) $entry	->encounter
    												            			->entryRelationship
                                              ->observation
                                              ->value
                                              ->attributes()
                                              ->code;
    			$this ->enc[$n]
    			      ->finding
    			      ->code_system		= (string) $entry	->encounter
    												                			->entryRelationship
                                                  ->observation
                                                  ->value
                                                  ->attributes()
                                                  ->codeSystem;
          }
      if(is_object($entry ->encounter
                          ->performer
                          ->assignedEntity
                          ->code)) {
  			$this ->enc[$n]
  			      ->performer
              ->name		    = (string) $entry	->encounter
  											               				->performer
                                              ->assignedEntity
                                              ->code
                                              ->attributes()
                                              ->displayName;
  			$this ->enc[$n]
  			      ->performer
              ->code_system		= (string) $entry	->encounter
  													                		->performer
                                                ->assignedEntity
                                                ->code
                                                ->attributes()
                                                ->codeSystem;
  			$this ->enc[$n]
  			      ->performer
  			      ->code		  = (string) $entry	->encounter
  											            				->performer
                                            ->assignedEntity
                                            ->code
                                            ->attributes()
                                            ->code;
  			$this ->enc[$n]
  			      ->performer
  			      ->code_system_name		= (string) $entry	->encounter
  												                      			->performer
                                                      ->assignedEntity
                                                      ->code
                                                      ->attributes()
                                                      ->codeSystemName;
        }
      if(is_object($entry ->encounter
                         ->participant
                         ->participantRole
                         ->code)) {
  			$this ->enc[$n]
  			      ->location
  			      ->organization		= (string) $entry	->encounter
  												                  			->participant
                                                  ->participantRole
                                                  ->code
                                                  ->attributes()
                                                  ->displayName;
        }
			$this ->enc[$n]
			      ->location
			      ->street		    = array(
			                        (string) $entry	->encounter
														                	->participant
                                              ->participantRole
                                              ->addr
                                              ->streetAddressLine[0],
															(string) $entry	->encounter
									                						->participant
                                              ->participantRole
                                              ->addr
                                              ->streetAddressLine[1]);
			$this ->enc[$n]
			      ->location
            ->city		      = (string) $entry	->encounter
										                					->participant
                                              ->participantRole
                                              ->addr
                                              ->city;
			$this ->enc[$n]
			      ->location
			      ->state		     = (string) $entry	->encounter
											                				->participant
                                              ->participantRole
                                              ->addr
                                              ->state;
			$this ->enc[$n]
			      ->location
			      ->zip		       = (string) $entry	->encounter
										                					->participant
                                              ->participantRole
                                              ->addr
                                              ->postalCode;
			$this ->enc[$n]
			      ->location
			      ->country		   = (string) $entry	->encounter
												                			->participant
                                              ->participantRole
                                              ->addr
                                              ->country;
		}
		return true;
	}
	
	private function parse_careplan($xmlCare) {
  	foreach($xmlCare->entry as $entry) {
    	$n = count($this->plan);
    	
    	if (is_object($entry->act->code)) $entryRoot = $entry->act;
    	elseif (is_object($entry->observation->code)) $entryRoot = $entry->observation;
    	else continue;

    	$this ->plan[$n]
    	      ->name        = (string)  $entryRoot  ->code
    	                                            ->attributes()
                                                  ->displayName;
    	$this ->plan[$n]
    	      ->code        = (string)  $entryRoot  ->code
    	                                            ->attributes()
                                                  ->code;
    	$this ->plan[$n]
    	      ->code_system = (string)  $entryRoot  ->code
      	                                          ->attributes()
                                                  ->codeSystem;
    	$this ->plan[$n]
    	      ->text        = (string)  $entryRoot  ->text;
    	      
    	$this ->plan[$n]
    	      ->status      = (string)  $entryRoot  ->statusCode
    	                                            ->attributes()
                                                  ->code;
  	}
  	return true;
	}
	
	
}

?>
