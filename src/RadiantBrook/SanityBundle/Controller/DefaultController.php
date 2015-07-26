<?php

namespace RadiantBrook\SanityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('RadiantBrookSanityBundle:Default:index.html.twig', array('name' => $name));
    }
}
