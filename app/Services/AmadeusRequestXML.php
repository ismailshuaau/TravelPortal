<?php
/**
 * Created by PhpStorm.
 * User: UniQue
 * Date: 4/6/2018
 * Time: 1:09 PM
 */

namespace App\Services;


class AmadeusRequestXML
{
    private $AmadeusConfig;

    private $PortalConfig;

    public function __construct(){

        $this->AmadeusConfig = new AmadeusConfig();
        $this->PortalConfig  = new PortalConfig();
    }

    public function posXML(){
        return '<POS>
        <Source PseudoCityCode="'.$this->AmadeusConfig->pcc.'" ISOCurrency="'.$this->AmadeusConfig->isoCurrency.'">
         <RequestorID Type="'.$this->AmadeusConfig->requestorIdType.'" ID="'.$this->AmadeusConfig->requestorId.'"/>
        </Source>
         <TPA_Extensions>
            <Provider>
               <Name>'.$this->AmadeusConfig->name.'</Name> 
               <System>'.$this->AmadeusConfig->system.'</System> 
               <Userid>'.$this->AmadeusConfig->userId.'</Userid> 
               <Password>'.$this->AmadeusConfig->password.'</Password> 
            </Provider>
         </TPA_Extensions>
      </POS>';
    }

    public function requestXML($body){
        return '
        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
                '.$body.'
            </soap:Body>
        </soap:Envelope>';
    }

    public function lowFarePlusRequestBodyXML($data){
        $passengers = '';
        if($data['no_of_adult'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="ADT" Quantity="'.$data['no_of_adult'].'"/>';
        }if($data['no_of_child'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="CHD" Quantity="'.$data['no_of_child'].'"/>';
        }if($data['no_of_infant'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="INF" Quantity="'.$data['no_of_infant'].'"/>';
        }

        if($data['return_date'] == "" || $data['return_date'] == null ||  $data['return_date'] ==  "Not Available"){
            $originDestinations = '
                <OriginDestinationInformation>
                  <DepartureDateTime>'.date('Y-m-d',strtotime($data['departure_date'])).'T00:00:00</DepartureDateTime>   
                  <OriginLocation LocationCode="'.$this->AmadeusConfig::iataCode($data['departure_city']).'"/>   
                  <DestinationLocation LocationCode="'.$this->AmadeusConfig::iataCode($data['destination_city']).'"/>  
                </OriginDestinationInformation> 
            ';
        }else{
            $originDestinations = '
                <OriginDestinationInformation>
                  <DepartureDateTime>'.date('Y-m-d',strtotime($data['departure_date'])).'T00:00:00</DepartureDateTime>   
                  <OriginLocation LocationCode="'.$this->AmadeusConfig::iataCode($data['departure_city']).'"/>   
                  <DestinationLocation LocationCode="'.$this->AmadeusConfig::iataCode($data['destination_city']).'"/>  
                </OriginDestinationInformation> 
                <OriginDestinationInformation>
                  <DepartureDateTime>'.date('Y-m-d',strtotime($data['return_date'])).'T00:00:00</DepartureDateTime>
                  <OriginLocation LocationCode="'.$this->AmadeusConfig::iataCode($data['destination_city']).'"/>   
                  <DestinationLocation LocationCode="'.$this->AmadeusConfig::iataCode($data['departure_city']).'"/>   
                </OriginDestinationInformation>
            ';
        }

       $body = '
            <wmLowFarePlus xmlns="http://traveltalk.com/wsLowFarePlus">
              <OTA_AirLowFareSearchPlusRQ>   
                '.$this->posXML().'
                '.$originDestinations.'
                <TravelPreferences>
                  <FareRestrictPref>
                      <AdvResTicketing>
                           
                           <AdvReservation/>   
                            </AdvResTicketing>    
                            <StayRestrictions>     
                            <MinimumStay/>     
                            <MaximumStay/>    
                            </StayRestrictions>    
                            <VoluntaryChanges>     
                            <Penalty/>    
                            </VoluntaryChanges>   
                            </FareRestrictPref>  
                </TravelPreferences> 
                <TravelerInfoSummary>   
                  <SeatsRequested>'.($data['no_of_adult'] + $data['no_of_child']).'</SeatsRequested>
                  <AirTravelerAvail>
                    '.$passengers.'
                  </AirTravelerAvail>  
                  <PriceRequestInformation PricingSource="Both"/>
                </TravelerInfoSummary>
              </OTA_AirLowFareSearchPlusRQ>
            </wmLowFarePlus>';

        return $this->requestXML($body);
    }

    public function lowFarePlusMultiDestinationRequestBodyXML($data){
        $passengers = '';
        if($data['searchParam']['no_of_adult'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="ADT" Quantity="'.$data['searchParam']['no_of_adult'].'"/>';
        }if($data['searchParam']['no_of_child'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="CHD" Quantity="'.$data['searchParam']['no_of_child'].'"/>';
        }if($data['searchParam']['no_of_infant'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="INF" Quantity="'.$data['searchParam']['no_of_infant'].'"/>';
        }
        $originDestinations = '';
        foreach($data['originDestinations']as $serial => $originDestination){
            $originDestinations = $originDestinations.'
            <OriginDestinationInformation>
                  <DepartureDateTime>'.date('Y-m-d',strtotime($originDestination['departure_date'])).'T00:00:00</DepartureDateTime>   
                  <OriginLocation LocationCode="'.$this->AmadeusConfig::iataCode($originDestination['departure_city']).'"/>   
                  <DestinationLocation LocationCode="'.$this->AmadeusConfig::iataCode($originDestination['destination_city']).'"/>  
                </OriginDestinationInformation> 
            ';
        }
        $body = '
            <wmLowFarePlus xmlns="http://traveltalk.com/wsLowFarePlus">
              <OTA_AirLowFareSearchPlusRQ>   
                '.$this->posXML().'
                '.$originDestinations.'
                <TravelPreferences>
                 <FareRestrictPref>
                      <AdvResTicketing>
                           <AdvReservation/>   
                            </AdvResTicketing>    
                            <StayRestrictions>     
                            <MinimumStay/>     
                            <MaximumStay/>    
                            </StayRestrictions>    
                            <VoluntaryChanges>     
                            <Penalty/>    
                            </VoluntaryChanges>   
                            </FareRestrictPref> 
                </TravelPreferences> 
                <TravelerInfoSummary>   
                  <SeatsRequested>'.($data->searchParam['no_of_adult'] + $data->searchParam['no_of_child']).'</SeatsRequested>
                  <AirTravelerAvail>
                    '.$passengers.'
                  </AirTravelerAvail>  
                  <PriceRequestInformation PricingSource="Both"/>
                </TravelerInfoSummary>
              </OTA_AirLowFareSearchPlusRQ>
            </wmLowFarePlus>';

        return $this->requestXML($body);
    }

    public function lowFareMatrixRequestBodyXML($data){
        $passengers = '';
        if($data['no_of_adult'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="ADT" Quantity="'.$data['no_of_adult'].'"/>';
        }if($data['no_of_child'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="CHD" Quantity="'.$data['no_of_child'].'"/>';
        }if($data['no_of_infant'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="INF" Quantity="'.$data['no_of_infant'].'"/>';
        }
        $originDestinations = '';
        foreach($data['originDestinations'] as $serial => $originDestination){
            $originDestinations = $originDestinations.'
            <OriginDestinationInformation>
                  <DepartureDateTime>'.date('Y-m-d',strtotime($originDestination['departure_date'])).'T00:00:00</DepartureDateTime>   
                  <OriginLocation LocationCode="'.$this->AmadeusConfig::iataCode($originDestination['departure_city']).'"/>   
                  <DestinationLocation LocationCode="'.$this->AmadeusConfig::iataCode($originDestination['destination_city']).'"/>  
                </OriginDestinationInformation> 
            ';
        }
        $body = '
              <OTA_AirLowFareSearchMatrixRQ>   
                '.$this->posXML().'
                '.$originDestinations.'
                <TravelPreferences>
                  <FareRestrictPref>
                      <AdvResTicketing>
                           <AdvReservation/>   
                            </AdvResTicketing>    
                            <StayRestrictions>     
                            <MinimumStay/>     
                            <MaximumStay/>    
                            </StayRestrictions>    
                            <VoluntaryChanges>     
                            <Penalty/>    
                            </VoluntaryChanges>   
                            </FareRestrictPref> 
                </TravelPreferences> 
                <TravelerInfoSummary>   
                  <SeatsRequested>'.($data['no_of_adult'] + $data['no_of_child']).'</SeatsRequested>
                  <AirTravelerAvail>
                    '.$passengers.'
                  </AirTravelerAvail>  
                  <PriceRequestInformation PricingSource="Both"/>
                </TravelerInfoSummary>
              </OTA_AirLowFareSearchMatrixRQ>';

        return $this->requestXML($body);
    }

    public function lowFareScheduleRequestBodyXML($data){
        $passengers = '';
        if($data['no_of_adult'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="ADT" Quantity="'.$data['no_of_adult'].'"/>';
        }if($data['no_of_child'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="CHD" Quantity="'.$data['no_of_child'].'"/>';
        }if($data['no_of_infant'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="INF" Quantity="'.$data['no_of_infant'].'"/>';
        }
        $originDestinations = '';
        foreach($data['originDestinations'] as $serial => $originDestination){
            $originDestinations = $originDestinations.'
            <OriginDestinationInformation>
                  <DepartureDateTime>'.date('Y-m-d',strtotime($originDestination['departure_date'])).'T00:00:00</DepartureDateTime>   
                  <OriginLocation LocationCode="'.$this->AmadeusConfig::iataCode($originDestination['departure_city']).'"/>   
                  <DestinationLocation LocationCode="'.$this->AmadeusConfig::iataCode($originDestination['destination_city']).'"/>  
                </OriginDestinationInformation> 
            ';
        }
        $body = '
              <OTA_AirLowFareSearchScheduleRQ>   
                '.$this->posXML().'
                '.$originDestinations.'
                <TravelPreferences>
                  <CabinPref Cabin="'.$data['cabin'].'"/>
                </TravelPreferences> 
                <TravelerInfoSummary>   
                  <SeatsRequested>'.($data['num_of_adult'] + $data['num_of_child']).'</SeatsRequested>
                  <AirTravelerAvail>
                    '.$passengers.'
                  </AirTravelerAvail>  
                  <PriceRequestInformation PricingSource="Both"/>
                </TravelerInfoSummary>
              </OTA_AirLowFareSearchScheduleRQ>';

        return $this->requestXML($body);
    }

    public function flightInfoRequestXML($data){
        $body = '
        <wmAirFlifoXml xmlns="http://traveltalk.com/wsAirFlifo">
            <OTA_AirFlifoRQ Version="1.000">
              '.$this->posXML().'
                <Airline Code="'.$data['filingAirlineCode'].'" />  
                <FlightNumber>'.$data['flightNumber'].'</FlightNumber>  
                <DepartureDate>'.$data['departureDateTime'].'</DepartureDate>  
                <DepartureAirport LocationCode="'.$data['departureAirportCode'].'" />  
                <ArrivalAirport LocationCode="'.$data['arrivalAirportCode'].'" /> 
            </OTA_AirFlifoRQ>
         </wmAirFlifoXml>';

        return $this->requestXML($body);
    }

    public function airSeatMapRequestXML($data){
        $body = '
              <OTA_AirSeatMapRQ>  
              '.$this->posXML().'
                <SeatMapRequests>   
                  <SeatMapRequest>    
                    <FlightSegmentInfo DepartureDateTime="'.$data['departure_date_time'].'2006-10-11" FlightNumber="'.$data['flight_number'].'">     
                      <DepartureAirport LocationCode="'.$data['departure_airport_code'].'"/>     
                      <ArrivalAirport LocationCode="'.$data['arrival_airport_code'].'"/>     
                      <MarketingAirline Code="'.$data['marketing_airline'].'"/>    
                    </FlightSegmentInfo>    
                    <SeatDetails>     
                      <CabinClass CabinType="'.$data['cabin_type'].'"/>     
                      <ResBookDesignations>      
                        <ResBookDesignation ResBookDesigCode="'.$data['res_book_desig_code'].'"/>     
                      </ResBookDesignations>    
                    </SeatDetails>   
                  </SeatMapRequest>  
                </SeatMapRequests> 
              </OTA_AirSeatMapRQ>';
        return $this->requestXML($body);
    }

    public function airPriceRequestXML($selectedItinerary, $searchParam){

//        dd($selectedItinerary);

        $passengers = '';

        if($searchParam['no_of_adult'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="ADT" Quantity="'.$searchParam['no_of_adult'].'"/>';
        }

        if($searchParam['no_of_child'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="CHD" Quantity="'.$searchParam['no_of_child'].'"/>';
        }

        if($searchParam['no_of_infant'] > 0){
            $passengers = $passengers.'<PassengerTypeQuantity Code="INF" Quantity="'.$searchParam['no_of_infant'].'"/>';
        }

        $seats = $searchParam['no_of_adult'] + $searchParam['no_of_child'];

        $originDestinationsCount = $selectedItinerary['originDestinationsCount'];

        if($originDestinationsCount > 1){
            $originDestinationOptions = '';
           for($i = 0; $i < $originDestinationsCount; $i++){
               $segmentInfo = '';
               $check = $i + 1;
               foreach($selectedItinerary['originDestinations'] as $serial => $originDestination){
                   $originDestination = (array)$originDestination;
                   if($check == $originDestination['originDestinationPlacement']){
                       $segmentInfoData = '<FlightSegment DepartureDateTime="'.$originDestination['departureDateTime'].'" ArrivalDateTime="'.$originDestination['arrivalDateTime'].'" FlightNumber="'.$originDestination['flightNumber'].'" ResBookDesigCode="'.$originDestination['resBookDesigCode'].'">    
		  <DepartureAirport LocationCode="'.$originDestination['departureAirportCode'].'"/>
		  <ArrivalAirport LocationCode="'.$originDestination['arrivalAirportCode'].'"/>      
		  <MarketingAirline Code="'.$originDestination['marketingAirlineCode'].'"/>     
		  </FlightSegment>';
                       $segmentInfo = $segmentInfo.$segmentInfoData;
                   }
               }
               $originDestinationOption = '';

               if($segmentInfo != ""){
                   $originDestinationOption = '<OriginDestinationOption>'.$segmentInfo.'</OriginDestinationOption>';
               }
               $originDestinationOptions = $originDestinationOptions.$originDestinationOption;
           }
        }

        else{
            $originDestinationOptions = '';
            $segmentInfo = '';
            foreach($selectedItinerary['originDestinations'] as $serial => $originDestination){

                $originDestination = (array)$originDestination;
                $segmentInfoData = '<FlightSegment DepartureDateTime="'.$originDestination['departureDateTime'].'" ArrivalDateTime="'.$originDestination['arrivalDateTime'].'" FlightNumber="'.$originDestination['flightNumber'].'" ResBookDesigCode="'.$originDestination['resBookDesigCode'].'">    
		  <DepartureAirport LocationCode="'.$originDestination['departureAirportCode'].'"/>
		  <ArrivalAirport LocationCode="'.$originDestination['arrivalAirportCode'].'"/>      
		  <MarketingAirline Code="'.$originDestination['marketingAirlineCode'].'"/>     
		  </FlightSegment>';
               $segmentInfo = $segmentInfo.$segmentInfoData;
            }
            $originDestinationOptions = '<OriginDestinationOption>'.$segmentInfo.'</OriginDestinationOption>';
        }

		$body = '
       <wmAirPrice xmlns="http://traveltalk.com/wsAirPrice">
		 <OTA_AirPriceRQ>
		  '.$this->posXML().' 
		  <AirItinerary>   
		  <OriginDestinationOptions>    
		  '.$originDestinationOptions.'  
		  </OriginDestinationOptions>  
		  </AirItinerary>  
		  <TravelerInfoSummary>   
		  <SeatsRequested>'.$seats.'</SeatsRequested>   
		  <AirTravelerAvail>    
		  '.$passengers.' 
		  </AirTravelerAvail>   
		  <PriceRequestInformation PricingSource="'.$selectedItinerary['pricingSource'].'"/>  
		  </TravelerInfoSummary> 
		 </OTA_AirPriceRQ>
		</wmAirPrice> ';

        return $this->requestXML($body);
	}

    public function buildTypeSort($buildType,$buildData){
		if($buildType == 'Hotel'){
			return $this->hotelBookXML($buildData);
		}elseif($buildType == 'Air'){
			return $this->airBookXML($buildData);
		}elseif($buildType == 'Vehicle'){
			return $this->vehicleBookXML($buildData);
		}
		return '';
	}

    public function airBookXML($selectedItinerary){
        $passengerCount = 0;
        foreach($selectedItinerary['itineraryPassengerInfo'] as $i => $count){
            if(!is_array($count)){
                $count = (array) $count;
            }
            $passengerCount = $passengerCount + $count['quantity'];
        }

        $originDestinationsCount = $selectedItinerary['originDestinationsCount'];

        if($originDestinationsCount > 1){
            $originDestinationOptions = '';
            $segmentCount = 1;
            for($i = 0; $i < $originDestinationsCount; $i++){
                $segmentInfo = '';
                $check = $i + 1;
                foreach($selectedItinerary['originDestinations'] as $serial => $originDestination){
                    $originDestination = (array)$originDestination;
                    if($check == $originDestination['originDestinationPlacement']){
                        $segmentInfoData = '<FlightSegment DepartureDateTime="'.$originDestination['departureDateTime'].'" ArrivalDateTime="'.$originDestination['arrivalDateTime'].'" FlightNumber="'.$originDestination['flightNumber'].'" ResBookDesigCode="'.$originDestination['resBookDesigCode'].'" RPH="'.$segmentCount.'" NumberInParty="'.$passengerCount.'">    
		  <DepartureAirport LocationCode="'.$originDestination['departureAirportCode'].'"/>
		  <ArrivalAirport LocationCode="'.$originDestination['arrivalAirportCode'].'"/>      
		  <MarketingAirline Code="'.$originDestination['marketingAirlineCode'].'"/>     
		  </FlightSegment>';
                        $segmentInfo = $segmentInfo.$segmentInfoData;
                        $segmentCount = $segmentCount + 1;
                    }
                }

                $originDestinationOption = '';
                if($segmentInfo != ""){
                    $originDestinationOption = '<OriginDestinationOption>'.$segmentInfo.'</OriginDestinationOption>';
                }
                $originDestinationOptions = $originDestinationOptions.$originDestinationOption;
            }

        }

        else{
            $originDestinationOptions = '';
            $segmentInfo = '';
            $segmentCount = 1;
            foreach($selectedItinerary['originDestinations'] as $serial => $originDestination){
                $originDestination = (array)$originDestination;
                $segmentInfoData = '<FlightSegment DepartureDateTime="'.$originDestination['departureDateTime'].'" ArrivalDateTime="'.$originDestination['arrivalDateTime'].'" FlightNumber="'.$originDestination['flightNumber'].'" ResBookDesigCode="'.$originDestination['resBookDesigCode'].'" RPH="'.$segmentCount.'" NumberInParty="'.$passengerCount.'">    
		  <DepartureAirport LocationCode="'.$originDestination['departureAirportCode'].'"/>
		  <ArrivalAirport LocationCode="'.$originDestination['arrivalAirportCode'].'"/>      
		  <MarketingAirline Code="'.$originDestination['marketingAirlineCode'].'"/>     
		  </FlightSegment>';
                $segmentInfo = $segmentInfo.$segmentInfoData;
                $segmentCount = $segmentCount + 1;
            }
            $originDestinationOptions = '<OriginDestinationOption>'.$segmentInfo.'</OriginDestinationOption>';
        }

	return '
             <OTA_AirBookRQ>
   <AirItinerary DirectionInd="'.$selectedItinerary['directionInd'].'">
      <OriginDestinationOptions>
         '.$originDestinationOptions.'
      </OriginDestinationOptions>
   </AirItinerary>
</OTA_AirBookRQ>
           ';

	}

    public function hotelBookXML($hotelRoomInformation){
		return '<OTA_HotelResRQ>
   <HotelReservations>
      <HotelReservation RoomStayReservation="1">
         <RoomStays>
            <RoomStay SourceOfBusiness="I">
               <RoomRates>
                  <RoomRate BookingCode="ROHPRO" NumberOfUnits="1" RatePlanCode="PRO" />
               </RoomRates>
               <GuestCounts>
                  <GuestCount AgeQualifyingCode="ADT" Age="0" Count="1" />
               </GuestCounts>
               <TimeSpan Start="2006-09-07" Duration="7" End="2006-09-14" />
               <Guarantee GuaranteeType="CC">
                  <GuaranteesAccepted>
                     <GuaranteeAccepted>
                        <PaymentCard CardType="Credit" CardCode="VI" CardNumber="4444333322221111" ExpireDate="0506">
                           <CardHolderName>JOHN SMITH</CardHolderName>
                           <Address FormattedInd="0" Type="Home">
                              <StreetNmbr>7300 NORTH KENDALL DRIVE</StreetNmbr>
                              <CityName>MIAMI</CityName>
                              <PostalCode>33156</PostalCode>
                              <StateProv>FL</StateProv>
                              <CountryName>USA</CountryName>
                           </Address>
                        </PaymentCard>
                     </GuaranteeAccepted>
                  </GuaranteesAccepted>
               </Guarantee>
               <DepositPayments>
                  <RequiredPayment>
                     <AcceptedPayments>
                        <AcceptedPayment>
                           <PaymentCard CardType="Credit" CardCode="VI" CardNumber="4444333322221111" ExpireDate="0506">
                              <CardHolderName>JOHN SMITH</CardHolderName>
                              <Address FormattedInd="0" Type="Home">
                                 <StreetNmbr>7300 NORTH KENDALL DRIVE</StreetNmbr>
                                 <CityName>MIAMI</CityName>
                                 <PostalCode>33156</PostalCode>
                                 <StateProv>FL</StateProv>
                                 <CountryName>USA</CountryName>
                              </Address>
                           </PaymentCard>
                        </AcceptedPayment>
                     </AcceptedPayments>
                  </RequiredPayment>
               </DepositPayments>
               <BasicPropertyInfo ChainCode="BR" HotelCode="SCB" HotelCityCode="SFO" HotelCodeContext="DY" />
               <ResGuestRPHs>
                  <ResGuestRPH RPH="1" />
               </ResGuestRPHs>
               <SpecialRequests>
                  <SpecialRequest RequestCode="SI">
                     <Text>Supplental Information</Text>
                  </SpecialRequest>
                  <SpecialRequest RequestCode="TN">
                     <Text>TN12345</Text>
                  </SpecialRequest>
               </SpecialRequests>
            </RoomStay>
         </RoomStays>
      </HotelReservation>
   </HotelReservations>
</OTA_HotelResRQ>';
	}

    public function vehicleBookXML($vehicleInformation){
		return '<?xml version="1.0" encoding="UTF-8"?>
<OTA_VehResRQ>
   <VehResRQCore Status="Available">
      <VehRentalCore PickUpDateTime="2006-07-05T06:00:00" ReturnDateTime="2006-08-04T06:00:00">
         <PickUpLocation LocationCode="NCE" />
         <ReturnLocation LocationCode="NCE" />
      </VehRentalCore>
      <VendorPref Code="EP" CodeContext="CP" />
      <VehPref>
         <VehType VehicleCategory="EBMN" />
      </VehPref>
      <RateQualifier RateQualifier="EUPR" />
      <TPA_Extensions>
         <CarData NumCars="1">
            <CarRate Rate="63301" Currency="USD" />
         </CarData>
      </TPA_Extensions>
   </VehResRQCore>
</OTA_VehResRQ>';
	}

    public function travelBuildMainRequestElementXML($passengerInformation,$buildData,$buildType,$user){
    	
    	$body = '
  <wmTravelBuild xmlns="http://traveltalk.com/wsTravelBuild">
  <OTA_TravelItineraryRQ>
   '.$this->posXML().'
   '.$this->buildTypeSort($buildType,$buildData).'
   <TPA_Extensions>
      <PNRData>
         <Traveler PassengerTypeCode="ADT" BirthDate="1952-07-24">
            <PersonName>
               <NamePrefix>MR</NamePrefix>
               <GivenName>JOHN</GivenName>
               <Surname>TEST</Surname>
               <NameTitle>MD</NameTitle>
            </PersonName>
            <TravelerRefNumber RPH="1" />
         </Traveler>
         <Telephone PhoneLocationType="Home" CountryAccessCode="234" AreaCityCode="LOS" PhoneNumber="'.$user['profile']['phone'].'" FormattedInd="0" />
         <Email>'.$user['email'].'info@Amadeus.com</Email>
         <Ticketing TicketTimeLimit="'.$buildData['ticketTimeLimit'].'" TicketType="eTicket" />
      </PNRData>
      <PriceData PriceType="'.$buildData['priceType'].'" AutoTicketing="false" ValidatingAirlineCode="'.$buildData['validatingAirlineCode'].'">
       <PublishedFares>
      <FareRestrictPref>
      <AdvResTicketing><AdvReservation/>
      </AdvResTicketing>
      <StayRestrictions>
      <MinimumStay/>
      <MaximumStay/>
      </StayRestrictions>
      <VoluntaryChanges>
      <Penalty/>
      </VoluntaryChanges>
      </FareRestrictPref>
      </PublishedFares>
      </PriceData>
   </TPA_Extensions>
  </OTA_TravelItineraryRQ>
  </wmTravelBuild>';

        return $this->requestXML($body);
    }

    public function flightTravelBuildRequestElementXML($passengerInformation,$buildData,$user){

//         dd($passengerInformation);
        $body = '
  <wmTravelBuild xmlns="http://traveltalk.com/wsTravelBuild">
  <OTA_TravelItineraryRQ>
   '.$this->posXML().'
   '.$this->airBookXML($buildData).'
   <TPA_Extensions>
      <PNRData>
      '.$this->airBookPassengersXML($passengerInformation).'
         <Telephone PhoneLocationType="Home" CountryAccessCode="234" AreaCityCode="LOS" PhoneNumber="'.$user['profile']['phone'].'" FormattedInd="0"/>
         <Email>'.$user['email'].'</Email>
         <Ticketing TicketTimeLimit="'.$buildData['ticketTimeLimit'].'" TicketType="eTicket" />
      </PNRData>
      <PriceData PriceType="'.$buildData['pricingSource'].'" AutoTicketing="false" ValidatingAirlineCode="'.$buildData['validatingAirlineCode'].'" >
       <PublishedFares>
      <FareRestrictPref>
      <AdvResTicketing><AdvReservation/>
      </AdvResTicketing>
      <StayRestrictions>
      <MinimumStay/>
      <MaximumStay/>
      </StayRestrictions>
      <VoluntaryChanges>
      <Penalty/>
      </VoluntaryChanges>
      </FareRestrictPref>
      </PublishedFares>
      </PriceData>
   </TPA_Extensions>
  </OTA_TravelItineraryRQ>
  </wmTravelBuild>';

        return $this->requestXML($body);
    }

    public function airBookPassengersXML($passengerInformation){
        $available = [];
        foreach($passengerInformation as $key => $information){
            $prefix = explode('_',$key)[0];
            if($prefix != ""){
            array_push($available,$prefix);
            }
        }
        $passengerArray = array_values(array_unique($available));
        $passengerRPH = 1;
        $travelers = '';
        foreach($passengerArray as $serial => $passengerType){
            $passengerTypeCount = count($passengerInformation[$passengerType."_title"]);
            for($p = 0; $p < $passengerTypeCount; $p++){
                $birthDate = '';
                if($passengerType != 'adult'){
                    $dob_new = $passengerInformation[$passengerType."_year_of_birth"][$p].'-'.$passengerInformation[$passengerType."_month_of_birth"][$p].'-'.$passengerInformation[$passengerType."_day_of_birth"][$p];
                    $date = $dob = date('Y-m-d', strtotime($dob_new));
                    $birthDate = 'BirthDate="'.$date.'"';
                }
                $traveler = '
                <Traveler PassengerTypeCode="ADT" '.$birthDate.'>
                  <PersonName>
                    <NamePrefix>'.$passengerInformation[$passengerType."_title"][$p].'</NamePrefix>
                    <GivenName>'.$passengerInformation[$passengerType."_first_name"][$p].' '.$passengerInformation[$passengerType."_other_name"][$p].'</GivenName>
                    <Surname>'.$passengerInformation[$passengerType."_sur_name"][$p].'</Surname>
                  </PersonName>
                  <TravelerRefNumber RPH="'.$passengerRPH.'" />
                </Traveler>
                ';
                $passengerRPH =  $passengerRPH + 1;
                $travelers = $travelers.$traveler;
            }
        }

       return $travelers;
    }

    public function hotelAvailRequestXml($data){
		$body = '<OTA_HotelAvailRQ>
     '.$this->posXML().'
   <AvailRequestSegments>
      <AvailRequestSegment>
         <StayDateRange Start="2012-09-11" End="2012-09-18" />
         <RoomStayCandidates>
            <RoomStayCandidate>
               <GuestCounts IsPerRoom="true">
                  <GuestCount Count="1" />
               </GuestCounts>
            </RoomStayCandidate>
         </RoomStayCandidates>
         <HotelSearchCriteria>
            <Criterion ExactMatch="true">
               <HotelRef HotelCityCode="DUS" />
            </Criterion>
         </HotelSearchCriteria>
      </AvailRequestSegment>
   </AvailRequestSegments>
</OTA_HotelAvailRQ>';
        return $this->requestXML($body);
	}
	
	public function hotelAvailRoomRequestXML($data){
		$body = '<OTA_HotelAvailRQ>
     '.$this->posXML().'
   <AvailRequestSegments>
      <AvailRequestSegment>
         <StayDateRange Start="2012-09-11" End="2012-09-18" />
         <RoomStayCandidates>
            <RoomStayCandidate>
               <GuestCounts IsPerRoom="true">
                  <GuestCount Count="1" />
               </GuestCounts>
            </RoomStayCandidate>
         </RoomStayCandidates>
         <HotelSearchCriteria>
            <Criterion ExactMatch="true">
               <HotelRef ChainCode="NS" HotelCode="CEN" HotelCityCode="DUS" />
            </Criterion>
         </HotelSearchCriteria>
      </AvailRequestSegment>
   </AvailRequestSegments>
</OTA_HotelAvailRQ>';

        return $this->requestXML($body);
	}

    public function hotelAvailRoomDetailsRequestXML($data) {

		$body = '<OTA_HotelAvailRQ>
     '.$this->posXML().'
   <AvailRequestSegments>
      <AvailRequestSegment>
         <StayDateRange Start="2012-09-11" End="2012-09-18" />
         <RoomStayCandidates>
            <RoomStayCandidate>
               <GuestCounts IsPerRoom="true">
                  <GuestCount Count="1" />
               </GuestCounts>
            </RoomStayCandidate>
         </RoomStayCandidates>
         <HotelSearchCriteria>
            <Criterion ExactMatch="true">
               <HotelRef ChainCode="NS" HotelCode="CEN" HotelCityCode="DUS" />
            </Criterion>
         </HotelSearchCriteria>
         <RatePlanCandidates>
            <RatePlanCandidate RatePlanID="C1TR0154HA"/>    
          </RatePlanCandidates> 
      </AvailRequestSegment>
   </AvailRequestSegments>
</OTA_HotelAvailRQ>';
        return $this->requestXML($body);
	}


}