You can see this software in action at [https://tipitaka.theravada.su](https://tipitaka.theravada.su)

# System Requirements

This software is based on [Symfony](https://symfony.com) framework, so requirements are the same as for other Symfony applications.

1. PHP 7.4 or higher (not tested on php 8)
2. Composer2 package manager - [get it here](https://getcomposer.org/download/)
3. MySql Database (tested with MySql 8.0.x)
4. A web server (tested with apache and nginx)

# Installation

1\. Clone the repository or download the code. 

2\. Create the database. You will need a database server hostname, database name, database username and password. Import the SQL script `database.sql` from `database.sql.zip` file to populate the database with tables and data.

3\. Install dependencies. 

If you are installing a production instance, open `.env` file in the source code root directory and change `APP_ENV=dev` to `APP_ENV=prod`.

Make sure the following php extensions are installed: xml curl apcu intl

From the command line switch to the the source code root directory with `composer.json` and run 
    
    composer install --no-dev --optimize-autoloader
    
for production instance or 

    composer install

for development instance.

4\. Configure the web server. The root folder of the site should point to `/public` folder. If this is your own server and you use apache, you will need to enable `.htaccess` files processing (this is <u>disabled</u> by default on apache installations). Add this:
    
    AllowOverride All
    
to the vitrual directory configuration section.

Ensure that `var` folder is writable (or is owned by) the web server account, especially if you use apache. I had to run this to make it work on apache:

    sudo chown -R www-data:www-data var

5\. If you want to start a production instance, run
 
    composer dump-env prod
    
then open `.env.local.php` file and enter the database credentials there.

If you want to start a development instance, create `.env.local` file in the source code root directory and enter the database credentials in the following way

    DATABASE_URL=mysql://username:password@hostname/database_name?serverVersion=5.7         

There are also some settings in `config/services.yaml` that can be customised.

# Usage instructions

## All users

It is possible to search both pali texts and translations. Bookmarks can be used to limit the scope. To set a bookmark, go to `Table of contents` - `All texts` then bookmark those parts of the Tipitaka you want to search in. Then click on `Search` and choose `bookmarks` in the scope selection.

You can also bookmark individual paragraphs.

When searching in pali it is possible to use an asterisk (*) as a wildcard and also double quotes to find exact matches only. Wildcards and double quotes are NOT supported when searching in other languages (e.g. English) and table of contents.  

## Registered users

There are four access levels for registered users: User, Author, Editor, Admin.

### User

Users can comment on sentences and generate code to quote passages. To generate code the user should switch to table view then click on "code link: show" then click on `Code` link in the first cell he wants to quote. Then change the number of rows to quote several at once and use the code on any site that supports pasting javascript. See [this page](https://www.theravada.su/node/2930) as an example.

### Author

Authors can split the pali text to make it available for translation. To do this, open the pali text in `Table of contents` - `All texts` then click `Split for translation`. Click on "new source link: show", then click on the `New source` link on the right hand side of the table. Once a translation is entered, a new column appears in the table where it is possible to enter translations using Quick Edit or full editing mode.

It is also possible to split a single paragraph for translation. To do this, click on `view` in pali text for the paragraph you want to translate then click `split for translation` in the bottom of the page.

Authors can add new translations and edit their own translations.

### Editor

Editors can edit sources that authors can use - see `My Account` - `Sources`. Sources usually correspond to translation authors but may also be websites the translation was taken from. 

Editors can import translations and align them with a pali source. The pali source should be split into sentences, then the editor should add a new source where the new translation will be imported. Then the editor should click "align tools: show" and then click `Import` in the table column the translation will go to. This will open a form where the editor can paste the text or choose a text file to import. The imported text now appears in the table. 

The align process can proceed with shifting the text up and down and editing cell text so it corresponds to the pali source. Tools for that show up when  "align tools" is set to "show".

Editor also has additional tools in the `Table of contents` - `All texts` subsection. He can set names to tree nodes, apply tags and set some other settings.

Editor can edit all translations.

### Admin

Admin can manage users and assign roles to user accounts. see `My Account` - `Users`.

Default administrator username is `admin` and default password is `12345`.
