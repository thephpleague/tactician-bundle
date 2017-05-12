<?php
namespace League\Tactician\Bundle\Tests\Middleware;

use League\Tactician\Bundle\Middleware\InvalidCommandException;
use League\Tactician\Bundle\Middleware\ValidatorMiddleware;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorMiddlewareTest extends TestCase
{
    /**
     * @var ValidatorInterface | MockInterface
     */
    protected $validator;

    /**
     * @var ValidatorMiddleware
     */
    protected $middleware;

    protected function setUp()
    {
        parent::setUp();

        $this->validator = \Mockery::mock('Symfony\Component\Validator\Validator\ValidatorInterface');

        $this->middleware = new ValidatorMiddleware($this->validator);
    }

    public function testExecute()
    {
        $list = new ConstraintViolationList([\Mockery::mock('Symfony\Component\Validator\ConstraintViolation')]);

        $this->validator->shouldReceive('validate')->once()->andReturn($list);

        try {

            $this->middleware->execute(new FakeCommand(), function () {
            });

        } catch (InvalidCommandException $e) {
            $this->assertEquals($list, $e->getViolations());
            $this->assertEquals(new FakeCommand(), $e->getCommand());
        }
    }

    public function testExecuteWithoutViolations()
    {
        $list = new ConstraintViolationList([]);

        $this->validator->shouldReceive('validate')->once()->andReturn($list);

        $this->middleware->execute(new FakeCommand(), function () {
        });
    }
}
