<?php

namespace Multifinger\UtilitiesBundle\Twig;


/**
 * @author Maksim Borisov <maksim.i.borisov@gmail.com> 11.03.19 13:15
 */
class TimeExtension extends \Twig_Extension
{

    public function getName()
    {
        return 'multifinger_time';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('seo_year',            [$this, 'getYear']),
            new \Twig_SimpleFunction('seo_new_year',        [$this, 'getNewYear']),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('timestamp2datetime',    [$this, 'timestamp2datetime']),
            new \Twig_SimpleFilter('seo_cheapest_tours',    [$this, 'getCheapestTours']),
        ];
    }

    /**
     * @author Maksim Borisov <maksim.i.borisov@gmail.com> 11.03.19 13:14
     * @param int $timestamp
     * @return \DateTime
     * @throws \Exception
     */
    public function timestamp2datetime(int $timestamp): \DateTime
    {
        return (new \DateTime())->setTimestamp($timestamp);
    }

}
