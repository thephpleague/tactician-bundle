<?php

namespace League\Tactician\Bundle\Tests\Security\Voter;

use League\Tactician\Bundle\Security\Voter\HandleCommandVoter;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Unit test for the handle command voter
 *
 * @author Ron Rademaker
 */
class HandleCommandVoterTest extends TestCase
{
    /**
     * @var AccessDecisionManager|MockInterface
     */
    private $decisionManager;

    public function setUp()
    {
        $this->decisionManager = Mockery::mock(AccessDecisionManager::class);
    }

    public function testAKnownCommandWillBeDelegatedToTheDecisionManager()
    {
        $tokenMock = Mockery::mock(TokenInterface::class);

        $voter = new HandleCommandVoter($this->decisionManager, [FakeCommand::class => ['ROLE_USER']]);

        $this->decisionManager
            ->shouldReceive('decide')
            ->with($tokenMock, ['ROLE_USER'])
            ->andReturn(true)
            ->once();

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($tokenMock, new FakeCommand(), ['handle']));
    }

    /**
     * @dataProvider provideTestVoteData
     */
    public function testAbstainOrDenyScenarios($attribute, $subject, $expected)
    {
        // In the test cases provided, we either abstain from voting or refuse
        // to do it since there's no declared mapping. Therefore, it would be
        // an error to ever call the decision manager.
        $this->decisionManager->shouldReceive('decide')->never();

        $voter = new HandleCommandVoter($this->decisionManager, []);
        $tokenMock = Mockery::mock(TokenInterface::class);

        $this->assertEquals($expected, $voter->vote($tokenMock, $subject, [$attribute]));
    }

    /**
     * Gets the testdata for the vote test.
     *
     * @return array
     */
    public function provideTestVoteData()
    {
        return [
            'abstain when not handling a command, but using the handle attribute' => [
                'handle',
                null,
                VoterInterface::ACCESS_ABSTAIN
            ],
            'abstain when not handling a command and not using the handle attribute' => [
                'create',
                null,
                VoterInterface::ACCESS_ABSTAIN
            ],
            'abstain when handling a command and not using the handle attribute' => [
                'create',
                new FakeCommand,
                VoterInterface::ACCESS_ABSTAIN
            ],
            'default access is false' => [
                'handle',
                new FakeCommand,
                VoterInterface::ACCESS_DENIED
            ],
        ];
    }
}
