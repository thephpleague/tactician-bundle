<?php

namespace League\Tactician\Bundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for security checks on handling commands.
 *
 * @author Ron Rademaker
 */
class HandleCommandVoter extends Voter
{
    /**
     * Command - Require role mapping
     *
     * @var array
     */
    private $commandRoleMapping;

    /**
     * Create a new HandleCommandVoter.
     *
     * @param array $commandRoleMapping
     */
    public function __construct(array $commandRoleMapping = [])
    {
        $this->commandRoleMapping = $commandRoleMapping;
    }

    /**
     * The voter supports checking handle commands
     *
     * @param string $attribute
     * @param object $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        return $attribute === 'handle' && is_object($subject);
    }

    /**
     * Checks if the currently logged on user may handle $subject.
     *
     * @param type $attribute
     * @param type $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $allowedRoles = $this->getAllowedRoles(get_class($subject));
        $actualRoles = $token->getRoles();

        foreach ($actualRoles as $role) {
            if (in_array($role->getRole(), $allowedRoles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the roles allowed to handle a command of $type
     *
     * @param string $type
     * @return array
     */
    private function getAllowedRoles($type)
    {
        if (array_key_exists($type, $this->commandRoleMapping)) {
            return $this->commandRoleMapping[$type];
        } else {
            return [];
        }
    }
}
