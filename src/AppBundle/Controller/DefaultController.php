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
        return $this->render('AppBundle::index.html.twig');
    }

    /**
     * @Route("/ticker", name="ticker")
     */
    public function tickerAction() {
      $ticker = new Ticker;
      dump($ticker->data());
      return $this->render('AppBundle::index.html.twig');
    }
}
