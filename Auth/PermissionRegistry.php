<?php
declare(strict_types=1);

namespace AstrX\Auth;

/**
 * Every permission the application knows, across all features.
 *
 * Built by the composition root from the always-on features' enums and every
 * module manifest's `permissions` key, disabled modules included. The grants
 * editor rebuilds a role's grants from this set when it saves, so a module
 * left out would lose its grants on the next save while switched off.
 */
final class PermissionRegistry
{
    /** @var array<string, Permission> id => permission */
    private array $byId = [];

    /** @param iterable<string> $enums permission enum class names (validated here) */
    public function __construct(iterable $enums)
    {
        foreach ($enums as $enum) {
            if (!is_subclass_of($enum, Permission::class)) {
                throw new \LogicException("{$enum} is not a permission enum (must implement " . Permission::class . ').');
            }
            foreach ($enum::cases() as $permission) {
                \assert($permission instanceof Permission);
                $id = $permission->id();
                if (isset($this->byId[$id]) && $this->byId[$id] !== $permission) {
                    throw new \LogicException(
                        "Permission '{$id}' is declared by both " . $this->byId[$id]::class . ' and ' . $enum . '.'
                    );
                }
                $this->byId[$id] = $permission;
            }
        }
        ksort($this->byId);
    }

    /** @return list<Permission> sorted by id */
    public function all(): array
    {
        return array_values($this->byId);
    }

    public function get(string $id): ?Permission
    {
        return $this->byId[$id] ?? null;
    }
}
