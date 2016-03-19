<?php

/*
 * This file is part of the WebPush library.
 *
 * (c) Louis Lagrange <lagrange.louis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Minishlink\WebPush\WebPush;

class WebPushTest extends PHPUnit_Framework_TestCase
{
    private $endpoints;
    private $keys;
    private $tokens;

    /** @var WebPush WebPush with correct api keys */
    private $webPush;

    public function setUp()
    {
        $this->endpoints = array(
            'standard' => getenv('STANDARD_ENDPOINT'),
            'GCM' => getenv('GCM_ENDPOINT'),
        );

        $this->keys = array(
            'standard' => getenv('USER_PUBLIC_KEY'),
            'GCM' => getenv('GCM_API_KEY'),
        );

        $this->tokens = array(
            'standard' => getenv('USER_AUTH_TOKEN'),
        );

        $this->webPush = new WebPush($this->keys);
    }

    public function testSendNotification()
    {
        $res = $this->webPush->sendNotification($this->endpoints['standard'], null, null, null, true);

        $this->assertEquals($res, true);
    }

    public function testSendNotificationWithPayload()
    {
        $res = $this->webPush->sendNotification(
            $this->endpoints['standard'],
            'test',
            $this->keys['standard'],
            $this->tokens['standard'],
            true
        );

        $this->assertTrue($res);
    }

    public function testSendNotificationWithPayloadWithoutAuthToken()
    {
        $res = $this->webPush->sendNotification(
            $this->endpoints['standard'],
            'test',
            $this->keys['standard'],
            null,
            true
        );

        $this->assertTrue($res);
    }

    public function testSendNotificationWithOldAPI()
    {
        $this->setExpectedException('ErrorException', 'The API has changed: sendNotification now takes the optional user auth token as parameter.');
        $this->webPush->sendNotification(
            $this->endpoints['standard'],
            'test',
            $this->keys['standard'],
            true
        );
    }

    public function testSendNotificationWithTooBigPayload()
    {
        $this->setExpectedException('ErrorException', 'Size of payload must not be greater than 4078 octets.');
        $this->webPush->sendNotification(
            $this->endpoints['standard'],
            str_repeat('test', 1020),
            $this->keys['standard'],
            null,
            true
        );
    }

    public function testSendNotifications()
    {
        foreach($this->endpoints as $endpoint) {
            $this->webPush->sendNotification($endpoint);
        }

        $res = $this->webPush->flush();

        $this->assertEquals(true, $res);
    }

    public function testFlush()
    {
        $this->webPush->sendNotification($this->endpoints['standard']);
        $this->assertEquals(true, $this->webPush->flush());

        // queue has been reset
        $this->assertEquals(false, $this->webPush->flush());

        $this->webPush->sendNotification($this->endpoints['standard']);
        $this->assertEquals(true, $this->webPush->flush());
    }

    public function testSendGCMNotificationWithoutGCMApiKey()
    {
        $webPush = new WebPush();

        $this->setExpectedException('ErrorException', 'No GCM API Key specified.');
        $webPush->sendNotification($this->endpoints['GCM'], null, null, null, true);
    }

    public function testSendGCMNotificationWithWrongGCMApiKey()
    {
        $webPush = new WebPush(array('GCM' => 'bar'));

        $res = $webPush->sendNotification($this->endpoints['GCM'], null, null, null, true);

        $this->assertTrue(is_array($res)); // there has been an error
        $this->assertArrayHasKey('success', $res);
        $this->assertEquals(false, $res['success']);

        $this->assertArrayHasKey('statusCode', $res);
        $this->assertEquals(401, $res['statusCode']);

        $this->assertArrayHasKey('headers', $res);
    }
}
