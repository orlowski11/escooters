<?php

declare(strict_types=1);

namespace EScooters\Importers;

use EScooters\Exceptions\CityNotAssignedToAnyCountryException;
use Symfony\Component\DomCrawler\Crawler;
use EScooters\Importers\DataSources\HtmlDataSource;
use EScooters\Importers\DataSources\JsonDataSource;
use EScooters\Utils\HardcodedCitiesToCountriesAssigner;
use GuzzleHttp\Client;


class DottDataImporter extends DataImporter implements HtmlDataSource
{
    protected array $markers = [];
    protected Crawler $sections;

    public function getBackground(): string
    {
        return "#F5C605";
    }

    public function extract(): static
    {
        $html = file_get_contents("https://ridedott.com/ride-with-us/paris/");

        $crawler = new Crawler($html);
        $this->sections = $crawler->filter("li.p-small.mb-1");

        return $this;
    }

    public function transform(): static
    {
        $currentCountry = "Italy";
        foreach ($this->sections as $section) 
        {
            $name = trim($section->nodeValue);
            $currentCountry = trim($section->parentNode->previousSibling->previousSibling->nodeValue);
            if($currentCountry == "UK"){
                $currentCountry = "United Kingdom";
            }  

            $country = $this->countries->retrieve($currentCountry);
            $city = $this->cities->retrieve($name, $country);
            $this->provider->addCity($city);

        }
        
        return $this;
    }

}

