<?php
declare(strict_types=1);

namespace AstrX\Auth;

/**
 * Optional resource-level policy.
 *
 * When Gate::can($permission, $resource) is called, Gate looks for a policy
 * for the resource's type and asks it. The role-level check must pass first:
 * a policy can only restrict, never grant what the role denies.
 *
 * A policy answers for exactly the permissions it lists in governs(). Gate
 * refuses any resource check whose permission isn't in that list, so a
 * permission the policy never considered can't fall through to "allow".
 */
interface PolicyInterface
{
    /**
     * The permissions this policy is prepared to rule on.
     *
     * Gate consults evaluate() for these and DENIES any other resource-scoped
     * check routed to this policy. Keep this list and evaluate()'s match arms in
     * step: a permission listed here but missing from the match throws (see each
     * policy's default arm), and a permission handled in the match but missing
     * here is never reached. tests/auth_policy_test.php asserts both directions
     * for every registered policy.
     *
     * @return list<Permission>
     */
    public function governs(): array;

    /**
     * @param Permission  $permission  The permission being checked; always one of governs().
     * @param Identity    $identity    The caller
     * @param object      $resource    The resource being acted on
     */
    public function evaluate(
        Permission  $permission,
        Identity    $identity,
        object      $resource,
    ): PolicyVerdict;
}
