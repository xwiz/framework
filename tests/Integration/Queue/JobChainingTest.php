<?php

use Illuminate\Bus\Queueable;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * @group integration
 */
class JobChainingTest extends TestCase
{
    public function tearDown()
    {
        JobChainingTestFirstJob::$ran = false;
        JobChainingTestSecondJob::$ran = false;
    }

    public function test_jobs_can_be_chained_on_success()
    {
        JobChainingTestFirstJob::dispatch()->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function test_jobs_can_be_chained_on_success_using_helper()
    {
        dispatch(new JobChainingTestFirstJob)->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function test_jobs_can_be_chained_via_queue()
    {
        Queue::connection('sync')->push((new JobChainingTestFirstJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function test_second_job_is_not_fired_if_first_was_already_deleted()
    {
        Queue::connection('sync')->push((new JobChainingTestFailingJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }
}

class JobChainingTestFirstJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class JobChainingTestSecondJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class JobChainingTestFailingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        $this->fail();
    }
}
