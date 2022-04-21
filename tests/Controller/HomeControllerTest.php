<?php
namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    /**
     * Si la page d'accueil retourne un statut 200
     * 
     * @return void
     */
    public function testReturnsA200Response(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Si il y a un lien "formations"
     * 
     * @return void
     */
    public function testFormationsLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $link = $crawler->selectLink('Formations')->link();
        $crawler = $client->click($link);

        $this->assertSame("Formations", $link);
    }
}