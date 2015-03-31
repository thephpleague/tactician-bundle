<?php


namespace Xtrasmal\TacticianBundle\Tests\Middleware;


use League\Tactician\Command;
use Mockery\MockInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Xtrasmal\TacticianBundle\Middleware\InvalidCommandException;
use Xtrasmal\TacticianBundle\Middleware\ValidatorMiddleware;

class ValidatorMiddlewareTest extends \PHPUnit_Framework_TestCase
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
        $command = \Mockery::mock('League\Tactician\Command');

        $this->validator->shouldReceive('validate')->once()->andReturn($list);

        try {

            $this->middleware->execute($command, function () {});

        } catch (InvalidCommandException $e) {
            $this->assertEquals($list, $e->getViolations());
            $this->assertEquals($command, $e->getCommand());
        }
    }

    public function testExecuteWithoutViolations()
    {
        $list = new ConstraintViolationList([]);
        $command = \Mockery::mock('League\Tactician\Command');

        $this->validator->shouldReceive('validate')->once()->andReturn($list);

        $this->middleware->execute($command, function () {});
    }

    /**
     * @expectedException \Exception
     */
    public function testExecuteWithoutValidator()
    {
        $this->middleware = new ValidatorMiddleware();
        $this->middleware->execute(\Mockery::mock('League\Tactician\Command'), function () {});
    }
}
