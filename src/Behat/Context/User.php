<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

class User extends Base {

  /**
   * The "anonymous" role also can be used.
   *
   * @Given I am acting as a user with the :roles role(s)
   * @Given I am acting as a/an :roles
   *
   * @param string $roles
   *   Comma separated user_role identifiers.
   *
   * @see \Drupal\DrupalExtension\Context\DrupalContext::assertAuthenticatedByRole
   */
  public function doLoginOrAnonymous(string $roles) {
    $roleIds = $this->parseUserRoleList($roles);
    $isAnonymous = in_array('anonymous', $roleIds);

    if ($isAnonymous && count($roleIds) > 1) {
      throw new \LogicException("anonymous role cannot be used with other roles: $roles");
    }

    if ($isAnonymous) {
      if ($this->loggedIn()) {
        $this->logout();
      }

      return;
    }

    $key = array_search('authenticated', $roleIds);
    if ($key !== false) {
      unset($roleIds[$key]);
    }
    sort($roleIds);

    if (!$this->loggedInWithRole($roles)) {
      $user = (object) [
        'name' => $this->getRandom()->name(8),
        'pass' => $this->getRandom()->name(16),
        'role' => implode(',', $roleIds),
        'roles' => $roleIds,
      ];
      $user->mail = "{$user->name}@example.com";

      $this->userCreate($user);
      $this->login($user);
    }
  }

  public function parseUserRoleList(string $userRoleList): array {
    $roles = explode(',', trim($userRoleList));
    $roles = array_map('trim', $roles);
    $roles = array_filter($roles);

    return array_unique($roles);
  }
}
