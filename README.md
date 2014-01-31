edropub
=======

is a PHP command line script that establishes a simple publishing workflow between [Editiorially](http://editorially.com) and [Leanpub](http://leanpub.com), using [Dropbox](https://www.dropbox.com) as an intermediate / exchange platform. It is to be used in conjunction with a [cronjob](http://en.wikipedia.org/wiki/Cron) and may trigger the creation of a preview or the publication of your Leanpub book.

**Editorially** is an excellent online editor with a focus on collaborative writing, using [Markdown](http://en.wikipedia.org/wiki/Markdown) as it's primary format and supporting export to **Dropbox**.

**Leanpub** is a publishing and distribution platform for ebooks that also uses Markdown as source format. It also supports synchronizing with **Dropbox**.

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

This will install *edropub* into the subdirectory `edropub`. To install *edropub*'s dependencies, you'll have to use [Composer](https://getcomposer.org/):

```bash
cd edropub
composer install
```

### Setup

To get the publishing workflow running, you have to fulfill some prerequisites:

#### Dropbox

First of all, create yourself a [Dropbox](https://www.dropbox.com) account that should be used for the publishing workflow. I recommend using a dedicated account for each book you want to publish (each with a dedicated email address).

Next, visit the **Dropbox App Console** and [create a Dropbox API app](https://www.dropbox.com/developers/apps/create). Make sure your app

* may store **Files and datastores**,
* is **not limited to it's own private folder**,
* and supports **specific file types**,
* namely **text files** and **images**.

Finally, I recommend giving your app the name **edropub** to avoid confusion. After the app has been created, you will be provided with an **App key** and an **App secret** in your app's settings tab. You will need them later to configure *edropub*.

#### Editorially

Also for [Editiorially](http://editorially.com), I recommend creating a dedicated account (you could use the same email address as you did for dropbox). Switch to the *Publishing* tab in your account settings and link to the Dropbox account you created earlier. This way, you will be able to publish your documents to Dropbox from within the editor.

#### Leanpub

Sign up with [Leanpub](http://leanpub.com) and create a new book. In the book's *Settings* tab, select the *Writing* sub menu and activate the Dropbox synchronization there. You will get an email asking you to accept the invitation for a Dropbox folder. Obviously, you should accept this invitation. ;)

### First run

Before you can run *edropub* for the first time and complete the setup, you have to copy the file `config/config.dist.json` to `config/config.json` and insert your Dropbox API key and secret there. The file should look something like this:

```JavaScript
{
  "key": "k5u3epqu3gz0wbx",
  "secret": "aswrzb2svqubdop"
}
```

Then change to your installation directory and run *edropub*:

```bash
cd /path/to/edropub
php -f edropub
```

In the first run, you will be asked to create a Dropbox **access token**. Simply follow the instructions on the screen. As a result, the file `config/access.json` will be written. It looks something like this:

```JavaScript
{
    "access_token": "emJgqzpDA50AAAAAAAAAAWfdy2-EKShmo24INuWwuMLqGGrsYzIgCIFYIeqddxaj",
    "editorially_prefix": "/Apps/Editorially",
    "leanpub_book_slug": "/<YOUR_BOOKS_SLUG>",
    "leanpub_api_key": "<YOUR_ACCOUNT_API_KEY>",
    "leanpub_trigger": "preview"
}
```

Please edit the `leanpub_api_key` and `leanpub_book_slug` according to your needs (the books slug is the URL part you gave your book at Leanpub). `leanpub_trigger` may bei either `preview`, `publish` or empty / non-present and controls what happens when changes of your source documents on Dropbox are detected.

Known problems
--------------

At the moment, *edropub* only supports exactly **one book**  to be processed. You will need to have a separate *edropub* installation for each book you want to process.

Legal
-----
Copyright © 2014 Joschi Kuphal <joschi@kuphal.net> / [@jkphl](https://twitter.com/jkphl)

*edropub* is licensed under the terms of the [MIT license](LICENSE.txt).