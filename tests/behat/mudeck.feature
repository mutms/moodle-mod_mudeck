@mod @mod_mudeck
Feature: Markdown slide deck
  In order to present in Moodle
  As a teacher
  I need to write slides and play them, without students getting more than they should

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | course | name       | intro            |
      | mudeck   | C1     | Conference | About the topic. |

  Scenario: A teacher writes slides and presents them
    Given I am on the "Conference" "mudeck activity" page logged in as "teacher1"
    Then I should see "This presentation has no slides yet."
    And I should not see "Start presentation"
    When I follow "Overview"
    And I follow "Add part"
    And I set the field "Deck part" to "Opening"
    And I set the field "Markdown" to "# First slide"
    And I press "Save and close"
    Then I should see "Opening"
    When I am on the "Conference" "mudeck activity" page
    Then I should see "Start presentation"

  Scenario: A teacher manages the parts of a presentation
    Given I am on the "Conference" "mod_mudeck > overview" page logged in as "teacher1"
    When I follow "Add part"
    And I set the field "Deck part" to "One"
    And I set the field "Markdown" to "# One"
    And I press "Save and close"
    And I follow "Add part"
    And I set the field "Deck part" to "Two"
    And I set the field "Markdown" to "# Two"
    And I press "Save and close"
    Then I should see "One"
    And I should see "Two"
    And "Import" "link" should exist
    And "Export" "link" should exist
    And "Preview" "link" should exist

  Scenario: Saving without leaving keeps working on the same new part
    Given I am on the "Conference" "mod_mudeck > overview" page logged in as "teacher1"
    When I follow "Add part"
    And I set the field "Deck part" to "Once"
    And I set the field "Markdown" to "# Once"
    And I press "Save and continue"
    Then I should see "Slides saved."
    When I set the field "Deck part" to "Renamed"
    And I press "Save and close"
    Then I should see "Renamed"
    # The second save has to land in the part the first one made, not in a new one.
    And I should not see "Once"

  Scenario: A presentation of one part can be edited from its welcome page
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name    | content   |
      | Conference | Opening | # Opening |
    When I am on the "Conference" "mudeck activity" page logged in as "teacher1"
    And I follow "Edit slides"
    And I press "Save and close"
    # Started at the welcome page, so that is where saving comes back to.
    Then I should see "Start presentation"
    And "Edit slides" "link" should exist
    # A second part makes "which one" a question, which the overview answers.
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name   | content  |
      | Conference | Second | # Second |
    When I am on the "Conference" "mudeck activity" page
    Then "Edit slides" "link" should not exist

  @javascript
  Scenario: An administrator adds a theme the whole site can use
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Slide themes" in site administration
    And I follow "Add a theme"
    And I set the following fields to these values:
      | Name       | Acme corporate           |
      | Short name | acme                     |
      | CSS        | section { color: red; }  |
    And I press "Save changes"
    Then I should see "Acme corporate"
    And I should see "acme"
    # And from there it is a theme like any other.
    When I am on the "Conference" "mudeck activity editing" page
    Then the "Theme" select box should contain "Acme corporate"

  @javascript
  Scenario: An administrator deletes a site theme after confirming
    Given the following "mod_mudeck > themes" exist:
      | shortname | name |
      | acme      | Acme |
    When I log in as "admin"
    And I navigate to "Plugins > Activity modules > Slide themes" in site administration
    Then I should see "Acme"
    When I click on "Delete" "link" in the "Acme" "table_row"
    Then I should see "Delete the theme \"Acme\"?"
    When I click on "Cancel" "button"
    Then I should see "Acme"
    When I click on "Delete" "link" in the "Acme" "table_row"
    And I click on "Continue" "button"
    Then I should see "The theme was deleted."
    And I should not see "Acme"

  Scenario: Slide themes are not for teachers to manage
    When I log in as "teacher1"
    And I am on site homepage
    Then I should not see "Slide themes"

  Scenario: An empty presentation offers a way to write slides
    Given the following "activities" exist:
      | activity | name  | course | idnumber |
      | mudeck   | Empty | C1     | mudeck2  |
    When I am on the "Empty" "mudeck activity" page logged in as "teacher1"
    Then I should see "This presentation has no slides yet."
    And "Add part" "link" should exist
    And "Import" "link" should exist
    # And the same on the overview, where the parts would be listed.
    When I am on the "Empty" "mod_mudeck > overview" page
    Then I should see "This presentation has no slides yet."
    # A student can do nothing about it, so they are not invited to try.
    When I am on the "Empty" "mudeck activity" page logged in as "student1"
    Then "Add part" "link" should not exist
    And "Import" "link" should not exist

  Scenario: A student sees the presentation but not the management
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name    | content  |
      | Conference | Opening | # Hello  |
    When I am on the "Conference" "mudeck activity" page logged in as "student1"
    Then I should see "Start presentation"
    And "Overview" "link" should not exist

  Scenario: The present button is gone without the capability to present
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name    | content  |
      | Conference | Opening | # Hello  |
    And the following "permission overrides" exist:
      | capability         | permission | role | contextlevel | reference |
      | mod/mudeck:present | Prevent    | user | Course       | C1        |
    When I am on the "Conference" "mudeck activity" page logged in as "student1"
    Then I should see "About the topic."
    And I should not see "Start presentation"

  @javascript
  Scenario: A teacher previews one part and can look at its notes
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name    | content                                                                    |
      | Conference | Opening | # First slide\n\n<!-- Remember the coffee break. -->\n\n---\n\n# Second |
    When I am on the "Conference" "mod_mudeck > overview" page logged in as "teacher1"
    And I follow "Preview"
    Then I should see "First slide"
    And I should see "Slide 1 of 2" in the "[data-region=mudeck-chrome]" "css_element"
    And I should not see "Remember the coffee break."
    When I click on "Speaker notes" "button"
    Then I should see "Remember the coffee break." in the "[data-region=mudeck-slide-notes]" "css_element"

  @javascript
  Scenario: The presentation runs for a teacher and for a student
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name    | content                            |
      | Conference | Opening | # First slide\n\n---\n\n# Second   |
    When I am on the "Conference" "mudeck activity" page logged in as "teacher1"
    And I follow "Start presentation"
    Then I should see "First slide"
    And I should see "Slide 1 of 2" in the "[data-region=mudeck-chrome]" "css_element"
    And I am on the "Conference" "mudeck activity" page logged in as "student1"
    And I follow "Start presentation"
    Then I should see "First slide"

  Scenario: The tabs of a presentation follow the capabilities and the settings
    Given the following "activities" exist:
      | activity | course | name        | intro          | allowdevicesync |
      | mudeck   | C1     | With sync   | Sync is on.    | 1               |
    When I am on the "Conference" "mudeck activity" page logged in as "teacher1"
    Then "Presentation" "link" should exist
    And "Overview" "link" should exist
    But "Sessions" "link" should not exist
    When I am on the "With sync" "mudeck activity" page
    Then "Sessions" "link" should exist
    When I follow "Sessions"
    Then I should see "Nothing is running."
    And I am on the "With sync" "mudeck activity" page logged in as "student1"
    Then "Overview" "link" should not exist
    And "Sessions" "link" should not exist

  @javascript
  Scenario: Viewing completion is earned by starting the presentation, not by opening the page
    Given the following "course" exists:
      | fullname         | Completion course |
      | shortname        | C2                |
      | enablecompletion | 1                 |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C2     | student |
    And the following "activities" exist:
      | activity | course | name    | completion | completionview |
      | mudeck   | C2     | Lecture | 2          | 1              |
    And the following "mod_mudeck > parts" exist:
      | mudeck  | name    | content |
      | Lecture | Opening | # Hello |
    When I am on the "Completion course" course page logged in as "student1"
    Then the "View" completion condition of "Lecture" is displayed as "todo"
    When I am on the "Lecture" "mudeck activity" page
    And I am on the "Completion course" course page
    Then the "View" completion condition of "Lecture" is displayed as "todo"
    When I am on the "Lecture" "mudeck activity" page
    And I follow "Start presentation"
    And I am on the "Completion course" course page
    Then the "View" completion condition of "Lecture" is displayed as "done"

  @javascript
  Scenario: Completion by reaching the last slide is earned on the last slide, not the first
    Given the following "course" exists:
      | fullname         | Completion course |
      | shortname        | C2                |
      | enablecompletion | 1                 |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C2     | student |
    And the following "activities" exist:
      | activity | course | name    | completion | completionview | completionreachedend |
      | mudeck   | C2     | Lecture | 2          | 0              | 1                    |
    And the following "mod_mudeck > parts" exist:
      | mudeck  | name    | content                       |
      | Lecture | Opening | # First\n\n---\n\n# Second |
    When I am on the "Completion course" course page logged in as "student1"
    Then the "Reach the last slide" completion condition of "Lecture" is displayed as "todo"
    When I am on the "Lecture" "mudeck activity" page
    And I follow "Start presentation"
    And I should see "Slide 1 of 2" in the "[data-region=mudeck-chrome]" "css_element"
    And I am on the "Completion course" course page
    Then the "Reach the last slide" completion condition of "Lecture" is displayed as "todo"
    When I am on the "Lecture" "mudeck activity" page
    And I follow "Start presentation"
    And I click on "Next slide" "button"
    And I should see "Slide 2 of 2" in the "[data-region=mudeck-chrome]" "css_element"
    And I wait until the page is ready
    And I am on the "Completion course" course page
    Then the "Reach the last slide" completion condition of "Lecture" is displayed as "done"

  @javascript
  Scenario: A deck with dollar signs, code and maths still renders
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name    | content                                                                     |
      | Conference | Opening | # Rozpocet\n\n- cena je $200\n\n```php\n$deck = new mudeck();\n```\n\n---\n\n# Vzorec\n\nPlocha je \\( \\pi r^2 \\) presne. |
    When I am on the "Conference" "mudeck activity" page logged in as "teacher1"
    And I follow "Start presentation"
    Then I should see "Rozpocet"
    And I should see "cena je $200"
    And I should see "Slide 1 of 2" in the "[data-region=mudeck-chrome]" "css_element"
    When I click on "Next slide" "button"
    Then I should see "Vzorec"

  @javascript @_file_upload
  Scenario: A teacher imports slides as a plain Markdown file
    Given I am on the "Conference" "mod_mudeck > overview" page logged in as "teacher1"
    When I follow "Import"
    And I upload "mod/mudeck/tests/fixtures/imported-deck.md" file to "Markdown or zip" filemanager
    And I press the escape key
    And I press "Import slides"
    Then I should see "imported-deck"
    When I am on the "Conference" "mudeck activity" page
    Then I should see "Start presentation"

  @javascript
  Scenario: The overview shows each part as slides
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name    | content                          |
      | Conference | Opening | # First slide\n\n---\n\n# Second |
    When I am on the "Conference" "mod_mudeck > overview" page logged in as "teacher1"
    Then I should see "Opening"
    # One part opens by itself, and its slides are drawn in the browser.
    And ".mudeck-thumb" "css_element" should exist
    And "Edit slides" "link" should exist
    When I click on ".mudeck-part-toggle" "css_element"
    Then ".mudeck-thumb" "css_element" should not exist

  @javascript
  Scenario: The presenter can print the slides with their notes
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name    | content                                                |
      | Conference | Opening | # First slide\n\n<!-- Remember the coffee break. -->  |
    When I am on the "Conference" "mod_mudeck > overview" page logged in as "teacher1"
    And I follow "Print with notes"
    Then I should see "First slide"
    And I should see "Remember the coffee break."
    And "Print with notes" "button" should exist
    # The copy says what it is: the presentation, its course and its description.
    And I should see "About the topic."
    And "Conference" "link" should exist
    And "Course 1" "link" should exist

  @javascript
  Scenario: Anybody who may watch can print the slides without the notes
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name    | content                                                |
      | Conference | Opening | # First slide\n\n<!-- Remember the coffee break. -->  |
    When I am on the "Conference" "mudeck activity" page logged in as "student1"
    And I follow "Print slides"
    Then I should see "First slide"
    And "Print slides" "button" should exist
    But I should not see "Remember the coffee break."
    And I should not see "Speaker notes"

  @javascript
  Scenario: The editor shows the slides beside the text
    Given the following "mod_mudeck > parts" exist:
      | mudeck     | name    | content                          |
      | Conference | Opening | # First slide\n\n---\n\n# Second |
    When I am on the "Conference" "mod_mudeck > overview" page logged in as "teacher1"
    And I follow "Edit slides"
    Then I should see "First slide" in the "[data-region=mudeck-editor-preview]" "css_element"
    And I should see "Slide 2" in the "[data-region=mudeck-editor-preview]" "css_element"
    And "Save and continue" "button" should exist
