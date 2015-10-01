<?php

namespace xrow\ImportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('ImportBundle:Default:index.html.twig', array('name' => $name));
    }
}
