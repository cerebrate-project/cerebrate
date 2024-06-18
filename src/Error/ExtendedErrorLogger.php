<?php
declare(strict_types=1);

namespace App\Error;

use Cake\Error\ErrorLogger;
use Cake\Error\Debugger;
use Cake\Error\PhpError;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Core\InstanceConfigTrait;
use Cake\Log\Log;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;


/**
 * Log errors and unhandled exceptions to `Cake\Log\Log`
 */
class ExtendedErrorLogger extends ErrorLogger
{

    /**
     * Get the request context for an error/exception trace.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to read from.
     * @return string
     */
    public function getRequestContext(ServerRequestInterface $request): string
    {
        $message = "\nRequest URL: " . $request->getRequestTarget();

        $referer = $request->getHeaderLine('Referer');
        if ($referer) {
            $message .= "\nReferer URL: " . $referer;
        }

        
        if (method_exists($request, 'clientIp')) {
            $clientIp = $request->clientIp();
            if ($clientIp && $clientIp !== '::1') {
                $message .= "\nClient IP: " . $clientIp;
            }
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $message .= sprintf(
                "\nClient X_FORWARDED_FOR: %s",
                $_SERVER['HTTP_X_FORWARDED_FOR']
            );
        }

        if (!empty($request->getSession()->read('authUser'))) {
            $message .= sprintf(
                "\nClient user: #%s :: %s",
                $request->getSession()->read('authUser.id'),
                $request->getSession()->read('authUser.username')
            );
        }
        return $message;
    }
}