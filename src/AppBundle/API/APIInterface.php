<?php

namespace AppBundle\API;

/**
 * Interface for API endpoint wrappers.
 */
interface APIInterface
{

    /**
     * Returns domain and static URI components for API URL.
     *
     * @return string
     *   The domain and static URI components that HTTP API endpoints will be
     *   concatenated to.
     */
    public function domain();

    /**
     * Returns the endpoint URI for a given API call.
     *
     * @return string
     *   The API endpoint.
     */
    public function endpoint();

    /**
     * Returns the absolute URL to the endpoint for a given API call.
     *
     * @return string
     *   The API endpoint.
     */
    public function url();

        /**
         * Returns PHP data from an endpoint.
         *
         * @return array
         *   The PHP data from the endpoint.
         */
    public function data();

}
