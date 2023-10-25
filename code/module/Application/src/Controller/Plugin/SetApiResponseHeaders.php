<?php
namespace Application\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class SetApiResponseHeaders extends AbstractPlugin
{
    public function __invoke($response, $domain = '*', $maxAge = 1728000, $verbs=['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])
    {
        if(!is_int($maxAge)) {
            // good for 20 days
            $maxAge = 1728000;
        }
        // GET, POST, PUT, PATCH, PUSH, DELETE, HEAD, OPTIONS
        $verbs = array_intersect($verbs, ['GET', 'POST', 'PUT', 'PATCH', 'PUSH', 'DELETE', 'HEAD', 'OPTIONS']);

        $response->getHeaders()
            ->addHeaderLine('Access-Control-Allow-Origin', '*')
            ->addHeaderLine('Access-Control-Allow-Methods', implode(', ', $verbs))
            ->addHeaderLine('Access-Control-Allow-Headers', 'Authorization, Content-Type, x-access-token')
            ->addHeaderLine('Access-Control-Allow-Credentials', 'true')
            ->addHeaderLine('Access-Control-Max-Age', $maxAge)
            //->addHeaderLine('Content-Type','application/json; charset=utf-8')
        ;

        return $response;
   }
}
