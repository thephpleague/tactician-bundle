<?php

namespace League\Tactician\Bundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for security checks on handling commands.
 *
 * @author Ron Rademaker
 */
class HandleCommandVoter extends Voter
{
    /**
     * The decision manager.
     *
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * Command - Require role mapping
     *
     * @var array
     */
    private $commandRoleMapping = [];

    /**
     * Create a new HandleCommandVoter.
     *
     * @param AccessDecisionManagerInterface $decisionManager
     * @param array                          $commandRoleMapping
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, array $commandRoleMapping = [])
    {
        $this->decisionManager = $decisionManager;
        $this->commandRoleMapping = $commandRoleMapping;
    }

    /**
     * The voter supports checking handle commands
     *
     * @param string $attribute
     * @param object $subject
     *
     * @return bool
     */
    protected function supports($attribute, $subject): bool
    {
        return $attribute === 'handle' && is_object($subject);
    }

    /**
     * Checks if the currently logged on user may handle $subject.
     *
     * @param string         $attribute
     * @param mixed          $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $allowedRoles = $this->getAllowedRoles(get_class($subject));

        if (count($allowedRoles) > 0) {
            return $this->decisionManager->decide($token, $allowedRoles);
        }

        // default conclusion is access denied
        return false;
    }

    /**
     * Gets the roles allowed to handle a command of $type
     *
     * @param string $type
     *
     * @return array
     */
    private function getAllowedRoles(string $type)
    {
        if (array_key_exists($type, $this->commandRoleMapping)) {
            return $this->commandRoleMapping[$type];
        }

        return [];
    }
}
