<?php namespace Grohman\Tattler\Lib\Interfaces;

/**
 * Interface Channel
 * @package Grohman\Tattler\Lib\Interfaces
 */
interface Channel
{
    /**
     * @return mixed
     */
    public function getName();

    /**
     * @return mixed
     */
    public function allow();

    /**
     * @return mixed
     */
    public function deny();

    /**
     * @return mixed
     */
    public function allowed();

}