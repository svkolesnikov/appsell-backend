<?php

namespace App\Controller\Admin;

use App\Exceptions\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class BaseController extends Controller
{
//    /**
//     * @param $objectName
//     * @param $action
//     * @throws \LogicException
//     * @throws AccessDeniedException
//     */
//    protected function checkAccess($objectName, $action): void
//    {
//        $concreteRole = sprintf('ROLE_APP_%s_%s', strtoupper($objectName), strtoupper($action));
//        $allRole = sprintf('ROLE_APP_%s_ALL', strtoupper($objectName));
//
//        if (!$this->isGranted($concreteRole) && !$this->isGranted($allRole)) {
//            throw new AccessDeniedException();
//        }
//    }
}