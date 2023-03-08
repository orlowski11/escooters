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
        $this->sections = $crawler->filter(".p-small mb-1");

        return $this;
    }

    public function transform(): static
    {
        foreach ($this->sections as $section) {
            $country = null;

            foreach ($section->childNodes as $node) {
                if ($node->nodeName === "a") {
                    $value = trim($node->nodeValue);
                    foreach ($node->childNodes as $city) {
                        if ($city->nodeName === "i") {
                            try {
                                $hardcoded = HardcodedCitiesToCountriesAssigner::assign($value);
                                if ($hardcoded) {
                                    $country = $this->countries->retrieve($hardcoded);
                                }
        
                                $city = $this->cities->retrieve($value, $country);
                                $this->provider->addCity($city);
                            } catch (CityNotAssignedToAnyCountryException $exception) {
                                echo $exception->getMessage() . PHP_EOL;
                                continue;
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }
}
