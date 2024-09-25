<?php
namespace App\View\Helper;

use Cake\Utility\Inflector;
use Cake\View\Helper;

// This helper helps determining the brightness of a colour (initially only used for the tagging) in order to decide
// what text colour to use against the background (black or white)
class FlagHelper extends Helper {

    public $helpers = [
        'Bootstrap',
    ];

    /**
    * @param string $countryCode ISO 3166-1 alpha-2 two-letter country code
    * @param string $countryName Full country name for title
    * @return string
    */
    public function countryFlag($countryCode, $countryName = null, $small = false)
    {
        if (strlen($countryCode) !== 2) {
            return '';
        }
        
        $output = [];
        foreach (str_split(strtolower($countryCode)) as $letter) {
            $letterCode = ord($letter);
            if ($letterCode < 97 || $letterCode > 122) {
                return ''; // invalid letter
            }
            $output[] = "1f1" . dechex(0xe6 + ($letterCode - 97));
        }
        
        $countryNamePretty = Inflector::humanize($countryName ? h($countryName) : $countryCode);
        $baseurl = $this->getView()->get('baseurl');
        $title = __('Flag of %s',  $countryNamePretty);
        $html = '<img src="' . $baseurl . '/img/flags/' . implode('-', $output) . '.svg" title="' . $title .'" alt="' . $title . '" aria-label="' . $title . '"  style="height: 18px" />';
        if (!$small) {
            $html = $this->Bootstrap->node('span', [
                'class' => 'd-flex align-items-center'
            ], $html . '&nbsp;' . $countryNamePretty);
        }
        return $html;
    }

    public function flag($countryName, $small = false) {
        $countryNameLow = strtolower($countryName);
        if (!empty(self::countries[$countryNameLow])) {
            $countryCode = self::countries[$countryNameLow];
            return $this->countryFlag($countryCode, $countryName, $small);
        }
        return '';
    }

    private const countries = [
        "afghanistan" => "AF",
        "albania" => "AL",
        "algeria" => "DZ",
        "andorra" => "AD",
        "angola" => "AO",
        "antigua and barbuda" => "AG",
        "argentina" => "AR",
        "armenia" => "AM",
        "australia" => "AU",
        "austria" => "AT",
        "azerbaijan" => "AZ",
        "bahamas" => "BS",
        "bahrain" => "BH",
        "bangladesh" => "BD",
        "barbados" => "BB",
        "belarus" => "BY",
        "belgium" => "BE",
        "belize" => "BZ",
        "benin" => "BJ",
        "bhutan" => "BT",
        "bolivia" => "BO",
        "bosnia and herzegovina" => "BA",
        "botswana" => "BW",
        "brazil" => "BR",
        "brunei" => "BN",
        "bulgaria" => "BG",
        "burkina faso" => "BF",
        "burundi" => "BI",
        "cabo verde" => "CV",
        "cambodia" => "KH",
        "cameroon" => "CM",
        "canada" => "CA",
        "central african republic" => "CF",
        "chad" => "TD",
        "chile" => "CL",
        "china" => "CN",
        "colombia" => "CO",
        "comoros" => "KM",
        "congo (brazzaville)" => "CG",
        "congo (kinshasa)" => "CD",
        "costa rica" => "CR",
        "croatia" => "HR",
        "cuba" => "CU",
        "cyprus" => "CY",
        "czechia" => "CZ",
        "denmark" => "DK",
        "djibouti" => "DJ",
        "dominica" => "DM",
        "dominican republic" => "DO",
        "ecuador" => "EC",
        "egypt" => "EG",
        "el salvador" => "SV",
        "equatorial guinea" => "GQ",
        "eritrea" => "ER",
        "estonia" => "EE",
        "eswatini" => "SZ",
        "ethiopia" => "ET",
        "fiji" => "FJ",
        "finland" => "FI",
        "france" => "FR",
        "gabon" => "GA",
        "gambia" => "GM",
        "georgia" => "GE",
        "germany" => "DE",
        "ghana" => "GH",
        "greece" => "GR",
        "grenada" => "GD",
        "guatemala" => "GT",
        "guinea" => "GN",
        "guinea-bissau" => "GW",
        "guyana" => "GY",
        "haiti" => "HT",
        "honduras" => "HN",
        "hungary" => "HU",
        "iceland" => "IS",
        "india" => "IN",
        "indonesia" => "ID",
        "iran" => "IR",
        "iraq" => "IQ",
        "ireland" => "IE",
        "israel" => "IL",
        "italy" => "IT",
        "jamaica" => "JM",
        "japan" => "JP",
        "jordan" => "JO",
        "kazakhstan" => "KZ",
        "kenya" => "KE",
        "kiribati" => "KI",
        "korea (north)" => "KP",
        "korea (south)" => "KR",
        "kuwait" => "KW",
        "kyrgyzstan" => "KG",
        "laos" => "LA",
        "latvia" => "LV",
        "lebanon" => "LB",
        "lesotho" => "LS",
        "liberia" => "LR",
        "libya" => "LY",
        "liechtenstein" => "LI",
        "lithuania" => "LT",
        "luxembourg" => "LU",
        "madagascar" => "MG",
        "malawi" => "MW",
        "malaysia" => "MY",
        "maldives" => "MV",
        "mali" => "ML",
        "malta" => "MT",
        "marshall islands" => "MH",
        "mauritania" => "MR",
        "mauritius" => "MU",
        "mexico" => "MX",
        "micronesia" => "FM",
        "moldova" => "MD",
        "monaco" => "MC",
        "mongolia" => "MN",
        "montenegro" => "ME",
        "morocco" => "MA",
        "mozambique" => "MZ",
        "myanmar" => "MM",
        "namibia" => "NA",
        "nauru" => "NR",
        "nepal" => "NP",
        "netherlands" => "NL",
        "new zealand" => "NZ",
        "nicaragua" => "NI",
        "niger" => "NE",
        "nigeria" => "NG",
        "north macedonia" => "MK",
        "norway" => "NO",
        "oman" => "OM",
        "pakistan" => "PK",
        "palau" => "PW",
        "panama" => "PA",
        "papua new guinea" => "PG",
        "paraguay" => "PY",
        "peru" => "PE",
        "philippines" => "PH",
        "poland" => "PL",
        "portugal" => "PT",
        "qatar" => "QA",
        "romania" => "RO",
        "russia" => "RU",
        "rwanda" => "RW",
        "saint kitts and nevis" => "KN",
        "saint lucia" => "LC",
        "saint vincent and the grenadines" => "VC",
        "samoa" => "WS",
        "san marino" => "SM",
        "sao tome and principe" => "ST",
        "saudi arabia" => "SA",
        "senegal" => "SN",
        "serbia" => "RS",
        "seychelles" => "SC",
        "sierra leone" => "SL",
        "singapore" => "SG",
        "slovakia" => "SK",
        "slovenia" => "SI",
        "solomon islands" => "SB",
        "somalia" => "SO",
        "south africa" => "ZA",
        "south sudan" => "SS",
        "spain" => "ES",
        "sri lanka" => "LK",
        "sudan" => "SD",
        "suriname" => "SR",
        "sweden" => "SE",
        "switzerland" => "CH",
        "syria" => "SY",
        "taiwan" => "TW",
        "tajikistan" => "TJ",
        "tanzania" => "TZ",
        "thailand" => "TH",
        "timor-leste" => "TL",
        "togo" => "TG",
        "tonga" => "TO",
        "trinidad and tobago" => "TT",
        "tunisia" => "TN",
        "turkey" => "TR",
        "turkmenistan" => "TM",
        "tuvalu" => "TV",
        "uganda" => "UG",
        "ukraine" => "UA",
        "united arab emirates" => "AE",
        "united kingdom" => "GB",
        "united states" => "US",
        "uruguay" => "UY",
        "uzbekistan" => "UZ",
        "vanuatu" => "VU",
        "vatican city" => "VA",
        "venezuela" => "VE",
        "vietnam" => "VN",
        "yemen" => "YE",
        "zambia" => "ZM",
        "zimbabwe" => "ZW"
    ];
    
}