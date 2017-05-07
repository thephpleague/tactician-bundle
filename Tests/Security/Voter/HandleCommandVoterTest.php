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
     * Tests the vote method.
     *
     * @param type $attribute
     * @param type $subject
     * @param type $decision
     * @param type $default
     * @param type $mapping
     * @param type $expected
     *
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
            ['handle', new FakeCommand, true, [], VoterInterface::ACCESS_DENIED],
            ['handle', null, true, [], VoterInterface::ACCESS_ABSTAIN],
            ['create', null, true, [], VoterInterface::ACCESS_ABSTAIN],
            ['create', new FakeCommand, true, [], VoterInterface::ACCESS_ABSTAIN],
            ['handle', new FakeCommand, false, [], VoterInterface::ACCESS_DENIED],
            ['handle', new FakeCommand, false, [FakeCommand::class => ['ROLE_USER']], VoterInterface::ACCESS_DENIED],
            ['handle', new FakeCommand, true, [FakeCommand::class => ['ROLE_USER']], VoterInterface::ACCESS_GRANTED],
            ['handle', new FakeCommand, false, ['someOtherCommand' => ['ROLE_USER']], VoterInterface::ACCESS_DENIED],
        ];
    }
}
