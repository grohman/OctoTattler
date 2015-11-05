<?php namespace Grohman\Tattler\Lib\Interfaces;

/**
 * Created by PhpStorm.
 * User: grohman
 * Date: 04.11.15
 * Time: 17:51
 */
interface Channel
{
    public function getName();

    public function allow();

    public function deny();

    public function allowed();

}