<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class FailedTradePair
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $bidUSDPrice;

    /**
     * @ORM\Column(type="integer")
     */
    protected $bidBTCVolume;

    /**
     * @ORM\Column(type="integer")
     */
    protected $askUSDPrice;

    /**
     * @ORM\Column(type="integer")
     */
    protected $askBTCVolume;
}
