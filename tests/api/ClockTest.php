<?php

declare(strict_types=1);

namespace tests\api;

use app\api\models\Clock;
use app\models\User;
use tests\ApiTestCase;
use Yii;

/**
 * Class ClockTest
 * @package tests\api
 */
class ClockTest extends ApiTestCase
{
    /**
     * @var array
     */
    public $fixtures = [
        'user' => [
            [
                'id' => 1,
                'email' => 'employee@semfleet.tech',
                'name' => 'employee',
                'auth_key' => 'test',
                'password_hash' => 'test',
                'role' => User::ROLE_EMPLOYEE,
                'status' => User::STATUS_ACTIVE,
            ],
        ],
        'clock' => [
            [
                'id' => 1,
                'user_id' => 1,
                'clock_in' => 10,
                'clock_out' => 100,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->user->setIdentity(User::findOne(1));
    }

    public function testAutoClockIn(): void
    {
        $clock = new Clock();

        $clock->validate();

        $this->assertNotEmpty($clock->clockIn);
    }

    public function testClockOutCanBeEmpty(): void
    {
        $clock = new Clock();

        $this->assertTrue($clock->validate());
    }

    public function testClockOutInPast(): void
    {
        $clock = new Clock();

        $clock->clockOut = 1;

        $this->assertFalse($clock->validate());
        $this->assertSame('Clock Out must be greater than "Clock In".', $clock->getFirstError('clockOut'));
    }

    public function testClockInOverlapping(): void
    {
        $clock = new Clock();

        $clock->clockIn = 50;
        $clock->clockOut = 110;

        $this->assertFalse($clock->validate());
        $this->assertSame('Can not start session because it overlaps with another ended session.', $clock->getFirstError('clockIn'));
    }

    public function testClockOutOverlapping(): void
    {
        $clock = new Clock();

        $clock->clockIn = 1;
        $clock->clockOut = 50;

        $this->assertFalse($clock->validate());
        $this->assertSame('Can not end session because it overlaps with another ended session.', $clock->getFirstError('clockOut'));
    }

    public function testOverMidnightSession(): void
    {
        $clock = new Clock();

        $clock->clockIn = 1545948000;
        $clock->clockOut = 1545987600;

        $this->assertFalse($clock->validate());
        $this->assertSame('Session can not last through midnight.', $clock->getFirstError('clockOut'));
    }

    public function testUpdateSession(): void
    {
        $clock = Clock::findOne(1);

        $clock->scenario = 'update';
        $clock->clockOut = 1000;

        $this->assertTrue($clock->save());

        $this->assertSame(1000, Clock::findOne(1)->clockOut);
    }
}