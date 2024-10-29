# NotifyMe

## Installation
Execute

    composer require hallowelt/notifications dev-REL1_35
within MediaWiki root or add `hallowelt/notifications` to the
`composer.json` file of your project

## Activation
Add

    wfLoadExtension( 'NotifyMe' );
to your `LocalSettings.php` or the appropriate `settings.d/` file.
