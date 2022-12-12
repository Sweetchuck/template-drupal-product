@api
@javascript
Feature: Admin - Basic access

  Scenario Outline: Site wide contact form – navigation
    Given I am acting as a user with the "<roles>" role
    And I am on "/admin"
    Then the response status code should be <status_code>
    And I <should> see the link "Structure"
    Examples:
      | roles         | status_code | should     |
      | anonymous     | 403         | should not |
      | authenticated | 403         | should not |
      | administrator | 200         | should     |
