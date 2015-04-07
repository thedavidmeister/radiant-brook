<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\API\Bitstamp\Ticker;

class DefaultController extends Controller
{
    /**
     * @Route("/app/example", name="homepage")
     */
    public function indexAction()
    {
      $ticker = new Ticker;
      print $ticker->getDomain();
      print $ticker->getEndpoint();
        return $this->render('AppBundle::index.html.twig');
    }
}
