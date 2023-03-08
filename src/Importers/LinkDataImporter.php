<?php

declare(strict_types=1);

namespace EScooters\Importers;

use DOMElement;
use EScooters\Importers\DataSources\HtmlDataSource;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class LinkDataImporter extends DataImporter implements HtmlDataSource
{
    protected Crawler $sections;

    public function getBackground(): string
    {
        return "#DEF700";
    }

    public function extract(): static
    {
        $client = new Client();
        $html = $client->get("https://superpedestrian.com/locations")->getBody()->getContents();

        $crawler = new Crawler($html);
        $this->sections = $crawler->filter(" div.sqs-block.html-block.sqs-block-html > div.sqs-block-content > p");

        return $this;
    }

    public function transform(): static
    {   
        $exclude = [
        "Ride with us in cities around the world!",
        "Press",
        "Careers",
        "Privacy Policy",
        "Cookie Policy",
        "Terms & Conditions",
        "Contact",
        "Get Help",
        "Select Language",
        "Superpedestrian HQ 84 Hamilton St, Cambridge, MA 02139",
        "California",
        "Connecticut",
        "Illinois",
        "Kansas",
        "Maryland",
        "Michigan",
        "New Jersey",
        "Ohio",
        "Tennessee",
        "Texas",
        "Virginia",
        "Washington"
        ];

        $currentCountry = "United States";
        foreach($this->sections as $section)
        {
            $name = $section->nodeValue;
            if(!(in_array($name, $exclude)))
            {
                if($section->firstChild->nodeName === "strong")
                {
                    $currentCountry = trim($name);
                }
                else
                {
                    $country = $this->countries->retrieve($currentCountry);
                    $city = $this->cities->retrieve($name, $country);
                    $this->provider->addCity($city);
                }
                
            }
        }

        return $this;
    }
}
