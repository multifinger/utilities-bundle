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
        return [];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('timestamp2datetime',    [$this, 'timestamp2datetime']),
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
