<?php

namespace League\Tactician\Bundle\Tests\Security\Voter;

use League\Tactician\Bundle\Security\Voter\HandleCommandVoter;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use Mockery;
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
     * The decision manager mock.
     */
    private $decisionManager;

    /**
     * Set up.
     */
    public function setUp()
    {
        $this->decisionManager = Mockery::mock(AccessDecisionManager::class);
    }

    /**
     * @dataProvider provideTestVoteData
     */
    public function testVote($attribute, $subject, $decision, $mapping, $expected)
    {
        $this->decisionManager->shouldReceive('decide')->andReturn($decision);
        $voter = new HandleCommandVoter($this->decisionManager, $mapping);
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
            // Testcase: default access is false
            ['handle', new FakeCommand, true, [], VoterInterface::ACCESS_DENIED],
            // Testcase: abstain when not handling a command, but using the handle attribute
            ['handle', null, true, [], VoterInterface::ACCESS_ABSTAIN],
            // Testcase: abstain when not handling a command and not using the handle attribute
            ['create', null, true, [], VoterInterface::ACCESS_ABSTAIN],
            // Testcase: abstain when not handling a command
            ['create', new FakeCommand, true, [], VoterInterface::ACCESS_ABSTAIN],
            // Testcase: default is unrelated to decision manager
            ['handle', new FakeCommand, false, [], VoterInterface::ACCESS_DENIED],
            // Testcase: deny access if decision manager returns false
            ['handle', new FakeCommand, false, [FakeCommand::class => ['ROLE_USER']], VoterInterface::ACCESS_DENIED],
            // Testcase: grant access if decision manager returns true and the command is in the mapping
            ['handle', new FakeCommand, true, [FakeCommand::class => ['ROLE_USER']], VoterInterface::ACCESS_GRANTED],
            // Testcase: deny access if the command is not in the mapping (i.e. a default deny access case)
            ['handle', new FakeCommand, false, ['someOtherCommand' => ['ROLE_USER']], VoterInterface::ACCESS_DENIED],
        ];
    }
}
