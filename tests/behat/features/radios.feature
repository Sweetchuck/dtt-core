@api
@javascript
Feature: Radio buttons

  Scenario: Radio buttons - foo
    Given I am acting as a user with the "administrator" role
    And I am at "/admin/structure/types/add"
    Then radios "Preview before submitting" has the following options:
      | value | label    |
      | 0     | Disabled |
      | 1     | Optional |
      | 2     | Required |
