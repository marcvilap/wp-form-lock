# Changelog
All notable changes to this project will be documented in this file, formatted via [this recommendation](https://keepachangelog.com/).

## [2.3.0] - 2023-01-11
### Added
- Entry Limits & Restrictions now have new "till the end of the day/week/month/year" locker options.
- New filter `wpforms_locker_lockers_entry_limit_exclude_not_allowed_entries_excluded_statuses` allows to exclude entry statuses for entry total limit and entry user limit lockers.

### Changed
- Error markup for the Password locker was modified according to other fields' errors. 
- A message generated by the Email locker now has the same styles as an entry submission confirmation message.
- "per day/week/month/year" locker options for Entry Limits & Restrictions were renamed to "per 24 hours/7 days/~30 days/~365 days".

### Fixed
- PHP notice was generated when upgrading the addon to the latest version.
- Locked forms weren't wrapped in the main form container.

## [2.2.0] - 2022-08-30
### Changed
- Improved the look and feel of the messages displayed to users when the form is closed.
- Minimum WPForms version is now 1.7.6.
- When the "Enable user entry limit" option is turned on, selecting one of the associated checkboxes is now required.

### Fixed
- Entry limit validation allowed to set negative values.
- The "Next" button in multi-page forms was not clickable when a Name field was empty and set as not required but a unique answer was enabled.
- Restrict by email address did not exclude abandoned/partial entries.
- Plugin install did not run properly in certain cases.

## [2.1.0] - 2022-06-28
### IMPORTANT
- Support for PHP 5.5 has been discontinued. If you are running PHP 5.5, you MUST upgrade PHP before installing the new WPForms Form Locker. Failure to do that will disable the WPForms Form Locker plugin.

### Changed
- Minimum WPForms version supported is 1.7.5.
- Reorganized locations of 3rd party libraries.

### Fixed
- Restrict by email address didn't work if the Confirmation setting was enabled for the Email field.
- Form Scheduling datepickers didn't work if custom formats were set in the WordPress General Settings page.
- Smart Phone field that required a unique value didn't get validated.

## [2.0.3] - 2022-02-10
### Changed
- Improved compatibility with PHP 8.

### Fixed
- Correctly handle "Unique answer" feature comparisons of strings having special characters.

## [2.0.2] - 2021-09-16
### Changed
- Properly handle the UI part of the IP-related setting - when IP storage is disabled, do not allow enabling IP-based entry limit.

### Fixed
- Do not globally cache all entry submission limits when Entry Limit by IP/email is enabled.

## [2.0.1] - 2021-09-14
### Changed
- Adjusted various styles on the Form Builder > Settings > Form Locker screen.

### Fixed
- Compatibility with WordPress Multisite installations.
- Start date should always be less than End date and vice versa.
- Correctly handle global site time format changes when rendering a form with Form Schedule enabled.
- Correctly handle time comparison in AM/PM format when setting Scheduling End Date if the user input is incorrect.
- Object cache was breaking Entry Limits logic.

## [2.0.0] - 2021-08-03
### Added
- Age verification locker.
- Email verification locker.
- Entry limit based on user IP.
- Entry limit based on an email field value.
- Compatibility with WPForms 1.6.8 and the updated Form Builder.

### Changed
- Frontend UI enhancement when used with Conversational Forms addon.
- Improved compatibility with jQuery 3.5 and no jQuery Migrate plugin.
- Further improved selective JS script loading to take into account Form Locker settings.

## [1.2.3] - 2020-08-05
### Added
- Filter `wpforms_form_locker_submit_label` to change the submit button label.

### Fixed
- Line breaks not correctly displayed on front-end with Form Locker messages (form settings).
- Scheduling feature does not work with some custom date formats.
- Unique Answer feature does not work with complex Name fields.

## [1.2.2] - 2020-03-03
### Changed
- Improved time delta detection in "Scheduling" section.

## [1.2.1] - 2020-01-09
### Fixed
- Minor layout issues of the 'clear' button in Scheduling section.

## [1.2.0] - 2019-07-23
### Added
- Complete translations for French and Portuguese (Brazilian).

### Changed
- Form Locker Scheduling UI improvements.

## [1.1.1] - 2019-02-08
### Fixed
- Typos, grammar, and other i18n related issues.

## [1.1.0] - 2019-02-06
### Added
- Complete translations for Spanish, Italian, Japanese, and German.

### Changed
- Unqiue value requirement comparisons are not longer case sensitive.

### Fixed
- Typos, grammar, and other i18n related issues.

## [1.0.1] - 2018-11-12
### Fixed
- PHP fatal error if using PHP 5.4.
- Conflict with multiple password protected forms on the same page.

## [1.0.0] - 2018-08-20
### Added
- Initial release.