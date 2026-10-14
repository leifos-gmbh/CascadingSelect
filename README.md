# CascadingSelect

This ILIAS plugin provides a custom user data field that provides the possibility to create cascading selection lists.

**Minimum ILIAS Version:**
11.0

**Maximum ILIAS Version:**
11.99

**Responsible Developer:**
Tim Schmitz <schmitz at leifos dot de>

**Supported Languages:**
German, English

### Change Notes

- With the update to ILIAS 11, CascadingSelect is not a plugin anymore, but rather a custom component in ILIAS.
  Please remove CascadingSelect from the `Customizing` folder, and follow the install guide below.
- With ILIAS 11, language files for custom component are not yet supported. If you want to modify the language variables
  of CascadingSelect, you have to patch `src/Service/Language.php`.

### Quick Installation Guide

Clone this repository into `components\Leifos` of your ILIAS installation, then update your ILIAS via
`composer install` and then `cli\setup.php update`.
