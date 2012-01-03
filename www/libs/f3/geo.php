<?php

/**
	Geo plugin for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2011 F3::Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Geo
		@version 2.0.5
**/

//! Geo plugin
class Geo extends Base {

	static
		// Countries indexed by country code
		$countries=array(
			'AF'=>'Afganistan',
			'AL'=>'Albania',
			'DZ'=>'Algeria',
			'AS'=>'American Samoa',
			'AD'=>'Andorra',
			'AO'=>'Angola',
			'AI'=>'Anguilla',
			'AQ'=>'Antarctica',
			'AG'=>'Antigua and Barbuda',
			'AR'=>'Argentina',
			'AM'=>'Armenia',
			'AW'=>'Aruba',
			'AU'=>'Australia',
			'AT'=>'Austria',
			'AZ'=>'Azerbaijan',
			'BS'=>'Bahamas',
			'BH'=>'Bahrain',
			'BD'=>'Bangladesh',
			'BB'=>'Barbados',
			'BY'=>'Belarus',
			'BE'=>'Belgium',
			'BZ'=>'Belize',
			'BJ'=>'Benin',
			'BM'=>'Bermuda',
			'BT'=>'Bhutan',
			'BO'=>'Bolivia',
			'BA'=>'Bosnia and Herzegowina',
			'BW'=>'Botswana',
			'BV'=>'Bouvet Island',
			'BR'=>'Brazil',
			'IO'=>'British Indian Ocean Territory',
			'BN'=>'Brunei Darussalam',
			'BG'=>'Bulgaria',
			'BF'=>'Burkina Faso',
			'BI'=>'Burundi',
			'KH'=>'Cambodia',
			'CM'=>'Cameroon',
			'CA'=>'Canada',
			'CV'=>'Cape Verde',
			'KY'=>'Cayman Islands',
			'CF'=>'Central African Republic',
			'TD'=>'Chad',
			'CL'=>'Chile',
			'CN'=>'China',
			'CX'=>'Christmas Island',
			'CC'=>'Cocos (Keeling) Islands',
			'CO'=>'Colombia',
			'KM'=>'Comoros',
			'CG'=>'Congo',
			'CD'=>'Congo, the Democratic Republic of the',
			'CK'=>'Cook Islands',
			'CR'=>'Costa Rica',
			'CI'=>'Cote d\'Ivoire',
			'HR'=>'Croatia (Hrvatska)',
			'CU'=>'Cuba',
			'CY'=>'Cyprus',
			'CZ'=>'Czech Republic',
			'DK'=>'Denmark',
			'DJ'=>'Djibouti',
			'DM'=>'Dominica',
			'DO'=>'Dominican Republic',
			'TP'=>'East Timor',
			'EC'=>'Ecuador',
			'EG'=>'Egypt',
			'SV'=>'El Salvador',
			'GQ'=>'Equatorial Guinea',
			'ER'=>'Eritrea',
			'EE'=>'Estonia',
			'ET'=>'Ethiopia',
			'FK'=>'Falkland Islands (Malvinas)',
			'FO'=>'Faroe Islands',
			'FJ'=>'Fiji',
			'FI'=>'Finland',
			'FR'=>'France',
			'FX'=>'France, Metropolitan',
			'GF'=>'French Guiana',
			'PF'=>'French Polynesia',
			'TF'=>'French Southern Territories',
			'GA'=>'Gabon',
			'GM'=>'Gambia',
			'GE'=>'Georgia',
			'DE'=>'Germany',
			'GH'=>'Ghana',
			'GI'=>'Gibraltar',
			'GR'=>'Greece',
			'GL'=>'Greenland',
			'GD'=>'Grenada',
			'GP'=>'Guadeloupe',
			'GU'=>'Guam',
			'GT'=>'Guatemala',
			'GN'=>'Guinea',
			'GW'=>'Guinea-Bissau',
			'GY'=>'Guyana',
			'HT'=>'Haiti',
			'HM'=>'Heard and Mc Donald Islands',
			'VA'=>'Holy See (Vatican City State)',
			'HN'=>'Honduras',
			'HK'=>'Hong Kong',
			'HU'=>'Hungary',
			'IS'=>'Iceland',
			'IN'=>'India',
			'ID'=>'Indonesia',
			'IR'=>'Iran (Islamic Republic of)',
			'IQ'=>'Iraq',
			'IE'=>'Ireland',
			'IL'=>'Israel',
			'IT'=>'Italy',
			'JM'=>'Jamaica',
			'JP'=>'Japan',
			'JO'=>'Jordan',
			'KZ'=>'Kazakhstan',
			'KE'=>'Kenya',
			'KI'=>'Kiribati',
			'KP'=>'Korea, Democratic People\'s Republic of',
			'KR'=>'Korea, Republic of',
			'KW'=>'Kuwait',
			'KG'=>'Kyrgyzstan',
			'LA'=>'Lao People\'s Democratic Republic',
			'LV'=>'Latvia',
			'LB'=>'Lebanon',
			'LS'=>'Lesotho',
			'LR'=>'Liberia',
			'LY'=>'Libyan Arab Jamahiriya',
			'LI'=>'Liechtenstein',
			'LT'=>'Lithuania',
			'LU'=>'Luxembourg',
			'MO'=>'Macau',
			'MK'=>'Macedonia, The Former Yugoslav Republic of',
			'MG'=>'Madagascar',
			'MW'=>'Malawi',
			'MY'=>'Malaysia',
			'MV'=>'Maldives',
			'ML'=>'Mali',
			'MT'=>'Malta',
			'MH'=>'Marshall Islands',
			'MQ'=>'Martinique',
			'MR'=>'Mauritania',
			'MU'=>'Mauritius',
			'YT'=>'Mayotte',
			'MX'=>'Mexico',
			'FM'=>'Micronesia, Federated States of',
			'MD'=>'Moldova, Republic of',
			'MC'=>'Monaco',
			'MN'=>'Mongolia',
			'MS'=>'Montserrat',
			'MA'=>'Morocco',
			'MZ'=>'Mozambique',
			'MM'=>'Myanmar',
			'NA'=>'Namibia',
			'NR'=>'Nauru',
			'NP'=>'Nepal',
			'NL'=>'Netherlands',
			'AN'=>'Netherlands Antilles',
			'NC'=>'New Caledonia',
			'NZ'=>'New Zealand',
			'NI'=>'Nicaragua',
			'NE'=>'Niger',
			'NG'=>'Nigeria',
			'NU'=>'Niue',
			'NF'=>'Norfolk Island',
			'MP'=>'Northern Mariana Islands',
			'NO'=>'Norway',
			'OM'=>'Oman',
			'PK'=>'Pakistan',
			'PW'=>'Palau',
			'PA'=>'Panama',
			'PG'=>'Papua New Guinea',
			'PY'=>'Paraguay',
			'PE'=>'Peru',
			'PH'=>'Philippines',
			'PN'=>'Pitcairn',
			'PL'=>'Poland',
			'PT'=>'Portugal',
			'PR'=>'Puerto Rico',
			'QA'=>'Qatar',
			'RE'=>'Reunion',
			'RO'=>'Romania',
			'RU'=>'Russian Federation',
			'RW'=>'Rwanda',
			'KN'=>'Saint Kitts and Nevis',
			'LC'=>'Saint LUCIA',
			'VC'=>'Saint Vincent and the Grenadines',
			'WS'=>'Samoa',
			'SM'=>'San Marino',
			'ST'=>'Sao Tome and Principe',
			'SA'=>'Saudi Arabia',
			'SN'=>'Senegal',
			'SC'=>'Seychelles',
			'SL'=>'Sierra Leone',
			'SG'=>'Singapore',
			'SK'=>'Slovakia (Slovak Republic)',
			'SI'=>'Slovenia',
			'SB'=>'Solomon Islands',
			'SO'=>'Somalia',
			'ZA'=>'South Africa',
			'GS'=>'South Georgia and the South Sandwich Islands',
			'ES'=>'Spain',
			'LK'=>'Sri Lanka',
			'SH'=>'St. Helena',
			'PM'=>'St. Pierre and Miquelon',
			'SD'=>'Sudan',
			'SR'=>'Suriname',
			'SJ'=>'Svalbard and Jan Mayen Islands',
			'SZ'=>'Swaziland',
			'SE'=>'Sweden',
			'CH'=>'Switzerland',
			'SY'=>'Syrian Arab Republic',
			'TW'=>'Taiwan, Province of China',
			'TJ'=>'Tajikistan',
			'TZ'=>'Tanzania, United Republic of',
			'TH'=>'Thailand',
			'TG'=>'Togo',
			'TK'=>'Tokelau',
			'TO'=>'Tonga',
			'TT'=>'Trinidad and Tobago',
			'TN'=>'Tunisia',
			'TR'=>'Turkey',
			'TM'=>'Turkmenistan',
			'TC'=>'Turks and Caicos Islands',
			'TV'=>'Tuvalu',
			'UG'=>'Uganda',
			'UA'=>'Ukraine',
			'AE'=>'United Arab Emirates',
			'GB'=>'United Kingdom',
			'US'=>'United States',
			'UM'=>'United States Minor Outlying Islands',
			'UY'=>'Uruguay',
			'UZ'=>'Uzbekistan',
			'VU'=>'Vanuatu',
			'VE'=>'Venezuela',
			'VN'=>'Viet Nam',
			'VG'=>'Virgin Islands (British)',
			'VI'=>'Virgin Islands (U.S.)',
			'WF'=>'Wallis and Futuna Islands',
			'EH'=>'Western Sahara',
			'YE'=>'Yemen',
			'YU'=>'Yugoslavia',
			'ZM'=>'Zambia',
			'ZW'=>'Zimbabwe'
		);

	/**
		Return additional information for specified Unix timezone
			@return array
			@param $id string
			@private
	**/
	private static function tzdata($id) {
		$ref=new DateTimeZone($id);
		$loc=$ref->getLocation();
		$now=time();
		$trn=$ref->getTransitions($now,$now);
		return array(
			'offset'=>$ref->
				getOffset(new DateTime('now',new DateTimeZone('GMT')))/
				3600,
			'country'=>$loc['country_code'],
			'latitude'=>$loc['latitude'],
			'longitude'=>$loc['longitude'],
			'dst'=>$trn[0]['isdst']
		);
	}

	/**
		Return zoneinfo array indexed by Unix time zone
			@return array
			@public
	**/
	static function timezones() {
		$zone=array();
		foreach (DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC)
			as $id)
			$zone[$id]=self::tzdata($id);
		return $zone;
	}

	/**
		Return array describing weather conditions for specific location;
		if an error occurs, return FALSE
			@return mixed
			@param $latitude float
			@param $longitude float
			@public
	**/
	static function weather($latitude,$longitude) {
		$result=json_decode(
			Web::http(
				'GET http://ws.geonames.org/findNearByWeatherJSON',
				http_build_query(
					array(
						'username'=>self::realIP(),
						'lat'=>$latitude,
						'lng'=>$longitude
					)
				)
			),
			TRUE
		);
		if (isset($result['weatherObservation']))
			return $result['weatherObservation'];
		trigger_error($result['status']['message']);
		return FALSE;
	}

	/**
		Return geolocation data based on IP address, or FALSE on failure
			@return mixed
			@param $ip string
			@public
	**/
	static function location($ip=NULL) {
		$data=unserialize(
			Web::http(
				'GET http://www.geoplugin.net/php.gp?'.($ip?('ip='.$ip):'')
			)
		);
		if (!$data)
			return FALSE;
		$result=array();
		foreach ($data as $key=>$val)
			$result[str_replace('geoplugin_','',$key)]=$val;
		return $result;
	}

}
