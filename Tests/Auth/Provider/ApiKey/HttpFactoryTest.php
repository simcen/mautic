<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\TestsAuth\Provider\ApiKey;


use GuzzleHttp\Exception\ConnectException;
use MauticPlugin\IntegrationsBundle\Auth\Provider\ApiKey\Credentials\HeaderCredentialsInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\ApiKey\Credentials\ParameterCredentialsInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\ApiKey\HttpFactory;
use MauticPlugin\IntegrationsBundle\Auth\Provider\CredentialsInterface;
use MauticPlugin\IntegrationsBundle\Exception\InvalidCredentialsException;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;

class HttpFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidCredentialsThrowsException()
    {
        $this->expectException(InvalidCredentialsException::class);

        $credentials = new Class implements CredentialsInterface
        {
        };

        (new HttpFactory())->getClient($credentials);
    }

    public function testMissingCredentialsThrowsException()
    {
        $this->expectException(PluginNotConfiguredException::class);

        $credentials = new Class implements HeaderCredentialsInterface
        {
            public function getApiKey(): ?string
            {
                return '';
            }

            public function getKeyName(): string
            {
                return '';
            }
        };

        (new HttpFactory())->getClient($credentials);
    }

    public function testInstantiatedClientIsReturned()
    {
        $credentials = new Class implements HeaderCredentialsInterface
        {
            public function getApiKey(): ?string
            {
                return 'abc';
            }

            public function getKeyName(): string
            {
                return '123';
            }
        };

        $factory = new HttpFactory();

        $client1 = $factory->getClient($credentials);
        $client2 = $factory->getClient($credentials);
        $this->assertTrue($client1 === $client2);

        $credential2 = new Class implements HeaderCredentialsInterface
        {
            public function getApiKey(): ?string
            {
                return '123';
            }

            public function getKeyName(): string
            {
                return 'abc';
            }
        };

        $client3 = $factory->getClient($credential2);
        $this->assertFalse($client1 === $client3);
    }

    public function testHeaderCredentialsSetsHeader()
    {
        $credentials = new Class implements HeaderCredentialsInterface
        {
            public function getApiKey(): ?string
            {
                return '123';
            }

            public function getKeyName(): string
            {
                return 'abc';
            }
        };

        $factory = new HttpFactory();

        $client  = $factory->getClient($credentials);
        $headers = $client->getConfig('headers');

        $this->assertArrayHasKey('abc', $headers);
        $this->assertEquals('123', $headers['abc']);
    }

    public function testParameterCredentialsAppendsToken()
    {
        $credentials = new Class implements ParameterCredentialsInterface
        {
            public function getApiKey(): ?string
            {
                return '123';
            }

            public function getKeyName(): string
            {
                return 'abc';
            }
        };

        $factory = new HttpFactory();
        $client  = $factory->getClient($credentials);

        try {
            // Triggering an exception so we can extract the request
            $client->request('get', 'foobar');
        } catch (ConnectException $exception) {
            $query = $exception->getRequest()->getUri()->getQuery();
            $this->assertEquals('abc=123', $query);
        }
    }
}