edropub
=======

is a PHP command line script that establishes a simple publishing workflow between **Editorially** and **Leanpub**, using **Dropbox** as an intermediate / exchange platform.

### Involved platforms

[Editiorially](http://editorially.com) is an excellent online editor with a focus on collaborative writing, using [Markdown](http://en.wikipedia.org/wiki/Markdown) as it's primary format and supporting export to [Dropbox](https://www.dropbox.com).

[Leanpub](http://leanpub.com) is a publishing and distribution platform for ebooks that also uses Markdown as source format and supports synchronizing with **Dropbox**.

Unfortunately, neither Editorially nor Dropbox have support for webhooks or another kind of active triggers at the moment, so an external **polling mechanism** has to be employed in order to detect and process changes of your Markdown files. This is where *edropub* jumps in. It may be installed on any external server and e.g. called periodically by a [cronjob](http://en.wikipedia.org/wiki/Cron). It processes modifications of your Markdown files and can trigger the preview creation or publication of your Leanpub book.

### Requirements

*edropub* uses the [Dropbox SDK for PHP 5.3+](https://github.com/dropbox/dropbox-sdk-php), which implies the following requirements:

* PHP 5.3+, [with 64-bit integers](http://stackoverflow.com/questions/864058/how-to-have-64-bit-integer-on-php) (so I personally didn't manage to get this working on Windows).
* PHP [cURL extension](http://php.net/manual/en/curl.installation.php) with SSL enabled (it's usually built-in).
* Must not be using [`mbstring.func_overload`](http://www.php.net/manual/en/mbstring.overload.php) to overload PHP's standard string functions.

### Installation

To install *edropub*, change to a directory or your liking and clone the [GitHub repository](https://github.com/jkphl/edropub):

```bash
cd /path/to/somewhere
git clone https://github.com/jkphl/edropub.git
```

This will install *edropub* into the subdirectory `edropub`. To install *edropub*'s dependencies, simply use [Composer](https://getcomposer.org/):

```bash
cd edropub
composer install
```

### Setup / configuration

To get the publishing workflow running, you have to fulfill some prerequisites:

#### Dropbox

First of all, create yourself a [Dropbox](https://www.dropbox.com) account to be used for the publishing workflow. I recommend using a dedicated account for each book you want to publish (each with a dedicated email address).

Next, visit the **Dropbox App Console** and [create a Dropbox API app](https://www.dropbox.com/developers/apps/create). Make sure your app

* may store **Files and datastores**,
* is **not limited to it's own private folder**,
* and supports **specific file types**,
* namely **text files** and **images**.

Finally, I recommend giving your app the name **edropub** to avoid confusion. After the app has been created, you will be provided with an **App key** and an **App secret** in your app's settings tab. You will need them later to [configure *edropub*](#first-run).

#### Editorially

Also for [Editiorially](http://editorially.com), I recommend creating a dedicated account (simply use the same email address as you did for Dropbox). Switch to the *Publishing* tab in your account settings and **link the account to the Dropbox** you created earlier. This way, you will be able to publish your documents to Dropbox from within the online editor.

#### Leanpub

Sign up with [Leanpub](http://leanpub.com) and create a new book. You will have to enter an *URL path* to your book. This string is also referred to as **the book's slug**. You will need it later for [configuring *edropub*'s access settings](#access-configuration). 

In the book's *Settings* tab, select the *Writing* sub menu and **activate the Dropbox synchronization** there. You will get an email asking you to accept the invitation for a Dropbox folder. Obviously, you would accept it. ;)

Finally, switch to your Leanpub *Dashboard* and select the *Account* tab. At the bottom of the page, activate the **Leanpub API**. You will get an **API key**, which you will again need for [configuring *edropub*'s access settings](#access-configuration). 

### First run

Before you can run *edropub* for the first time, you need to configure it and complete it's setup. Copy the file `config/config.dist.json` to `config/config.json` and fill in your Dropbox **API key and secret** there. The file should look something like this:

```JavaScript
{
  "key": "k5u3epqu3gz0wbx",
  "secret": "aswrzb2svqubdop"
}
```

In the next step you have to create a Dropbox **access token**. Change to your installation directory and run *edropub* for the first time:

```bash
cd /path/to/edropub
php -f edropub
```

You will be asked to open a specific URL (using your browser) and confirm that *edropub* may access your Dropbox. Just follow the instructions on the screen.

#### Access configuration

As a result, the file `config/access.json` will be written. It looks something like this:

```JavaScript
{
    "access_token": "emJgqzpDA50AAAAAAAAAAWfdy2-EKShmo24INuWwuMLqGGrsYzIgCIFYIeqddxaj",
    "editorially_prefix": "/Apps/Editorially",
    "leanpub_book_slug": "/<YOUR_BOOKS_SLUG>",
    "leanpub_api_key": "<YOUR_ACCOUNT_API_KEY>",
    "leanpub_trigger": "preview"
}
```

These are the possible options inside this configuration file:

| Key                  | Value                                                    |
| -------------------- | -------------------------------------------------------- |
| `access_token`       | Access token returned by the [above process](#first-run) |
| `editorially_prefix` | The Dropbox path Edititorially is exporting your Markdown files to. Currently, this is always `/Apps/Editorially` |
| `leanpub_book_slug`  | This is your [Leanpub book's URL path](#leanpub) |
| `leanpub_api_key`    | This is your [Leanpub API key](#leanpub) |
| `leanpub_trigger`    | *edropub* can automatically trigger the preview creation or publication of your Leanpub book in case any Markdown changes are detected. Use `preview` or `publish` as value, otherwise leave this option empty (or omit it altogether) |

Please fill in your `leanpub_book_slug` and `leanpub_api_key` and optionally specify `leanpub_trigger` according to your needs.

### Running edropub

As soon as you have finished [configuring the access parameters](#access-configuration), you may use *edropub* by calling it on the command line:

```bash
php -f /path/to/edropub.phps
```

*edropub* utilizes the [Dropbox delta API](https://www.dropbox.com/developers/core/docs#delta) to only process changes to your Dropbox that have occured since the last call. You may, however, force *edropub* to start from scratch and reprocess all your Markdown files by passing a `reset` as an argument to the command:

```bash
php -f /path/to/edropub.phps -- reset
```

A [crontab](http://en.wikipedia.org/wiki/Cron) entry for calling *edropub* every 15 minutes could e.g. look like this:

```bash
*/15       *       *       *       *       /usr/bin/php -f /path/to/edropub.phps
```

Known problems
--------------

At the moment, *edropub* only supports exactly **one book**  to be processed. You will need to have a separate *edropub* installation / configuration for each book you want to publish.

Legal
-----
Copyright © 2014 Joschi Kuphal <joschi@kuphal.net> / [@jkphl](https://twitter.com/jkphl)

*edropub* is licensed under the terms of the [MIT license](LICENSE.txt).