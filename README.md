# Masquerade

## Faker-driven, platform-agnostic, locale-compatible data faker tool

Point Masquerade to a database, give it a rule-set defined in YAML and Masquerade will anonymize the data for you 
 automatically!
 
<img src="https://user-images.githubusercontent.com/431360/42574650-30e8d186-851f-11e8-9693-c23b426c43f2.png" width="600" />
 
### Out-of-the-box supported frameworks

- Magento 2

### Customization

You can add your own configuration files in a directory named `config` in the same directory as where you run masquerade. The configuration files will be merged with any already present configuration files for that platform, overriding any out-of-the-box values.

See the [Magento 2 YAML files](https://github.com/elgentos/masquerade/tree/master/src/config/magento2) as examples for notation. 

For formatters, you can use all default [Faker formatters](https://github.com/fzaninotto/Faker#formatters).

#### Custom Providers / Formatters

You can also create your own custom providers with formatters. They need to extend `Faker\Provider\Base` and they need to live in either `~/.masquerade` or `.masquerade` relative from where you run masquerade.

An example file `.masquerade/Custom/WoopFormatter.php`;

```php
<?php

namespace Custom;

use Faker\Provider\Base;

class WoopFormatter extends Base {

    public function woopwoop() {
        $woops = ['woop', 'wop', 'wopwop', 'woopwoop'];
        return $woops[array_rand($woops)];
    }
}
```

And then use it in your YAML file. A provider needs to be set on the column name level, not on the formatter level.

```
customer:
  customer_entity:
    columns:
      firstname:
        provider: \Custom\WoopFormatter
        formatter: 
          name: woopwoop
```  
 
### Installation

Download the phar file:

```
wget https://github.com/elgentos/masquerade/releases/download/{VERSION}/masquerade.phar
```

### Usage

```
$ php masquerade.phar run --help

Description:
  List of tables (and columns) to be faked

Usage:
  run [options]

Options:
      --platform[=PLATFORM]  
      --driver[=DRIVER]      Database driver [mysql]
      --database[=DATABASE]  
      --username[=USERNAME]  
      --password[=PASSWORD]  
      --host[=HOST]          Database host [localhost]
      --prefix[=PREFIX]      Database prefix [empty]
      --locale[=LOCALE]      Locale for Faker data [en_US]
      --group[=GROUP]        Which groups to run masquerade on [all]
```

You can also set these variables in a `config.yaml` file in the same location as where you run masquerade from, for example:

```yaml
platform: magento2
database: dbnamehere
username: userhere
password: passhere
host: localhost
``` 

#### Magento 2 out-of-the-box rule-set

```
$ php masquerade.phar list --platform=magento2

+----------+------------+--------------------------+--------------------+---------------------+
| Platform | Group      | Table                    | Column             | Formatter           |
+----------+------------+--------------------------+--------------------+---------------------+
| magento2 | invoice    | sales_invoice            | customer_note      | sentence            |
| magento2 | invoice    | sales_invoice_comment    | comment            | sentence            |
| magento2 | invoice    | sales_invoice_grid       | customer_email     | email               |
| magento2 | invoice    | sales_invoice_grid       | customer_name      | name                |
| magento2 | invoice    | sales_invoice_grid       | billing_name       | name                |
| magento2 | invoice    | sales_invoice_grid       | shipping_address   | address             |
| magento2 | invoice    | sales_invoice_grid       | billing_address    | address             |
| magento2 | creditmemo | sales_creditmemo         | customer_note      | sentence            |
| magento2 | creditmemo | sales_creditmemo_comment | comment            | sentence            |
| magento2 | creditmemo | sales_creditmemo_grid    | customer_email     | email               |
| magento2 | creditmemo | sales_creditmemo_grid    | customer_name      | name                |
| magento2 | creditmemo | sales_creditmemo_grid    | billing_name       | name                |
| magento2 | creditmemo | sales_creditmemo_grid    | shipping_address   | address             |
| magento2 | creditmemo | sales_creditmemo_grid    | billing_address    | address             |
| magento2 | review     | review_detail            | nickname           | firstName           |
| magento2 | review     | review_detail            | title              | sentence            |
| magento2 | review     | review_detail            | detail             | paragraph           |
| magento2 | newsletter | newsletter_subscriber    | subscriber_email   | email               |
| magento2 | order      | sales_order              | customer_email     | email               |
| magento2 | order      | sales_order              | customer_firstname | firstName           |
| magento2 | order      | sales_order              | customer_lastname  | lastName            |
| magento2 | order      | sales_order              | customer_dob       | dateTimeThisCentury |
| magento2 | order      | sales_order              | customer_taxvat    | vat                 |
| magento2 | order      | sales_order              | remote_ip          | ipv4                |
| magento2 | order      | sales_order              | customer_note      | sentence            |
| magento2 | order      | sales_order_grid         | customer_email     | email               |
| magento2 | order      | sales_order_grid         | customer_name      | name                |
| magento2 | order      | sales_order_grid         | shipping_name      | name                |
| magento2 | order      | sales_order_grid         | billing_name       | name                |
| magento2 | order      | sales_order_grid         | shipping_address   | address             |
| magento2 | order      | sales_order_grid         | billing_address    | address             |
| magento2 | order      | sales_order_address      | email              | email               |
| magento2 | order      | sales_order_address      | firstname          | firstName           |
| magento2 | order      | sales_order_address      | lastname           | lastName            |
| magento2 | order      | sales_order_address      | company            | company             |
| magento2 | order      | sales_order_address      | street             | streetAddress       |
| magento2 | order      | sales_order_address      | city               | city                |
| magento2 | order      | sales_order_address      | postcode           | postcode            |
| magento2 | order      | sales_order_address      | telephone          | phoneNumber         |
| magento2 | order      | sales_order_address      | fax                | phoneNumber         |
| magento2 | order      | sales_order_address      | vat_id             | vat                 |
| magento2 | quote      | quote                    | customer_email     | email               |
| magento2 | quote      | quote                    | customer_firstname | firstName           |
| magento2 | quote      | quote                    | customer_lastname  | lastName            |
| magento2 | quote      | quote                    | customer_dob       | dateTimeThisCentury |
| magento2 | quote      | quote                    | customer_taxvat    | vat                 |
| magento2 | quote      | quote                    | remote_ip          | ipv4                |
| magento2 | quote      | quote_address            | email              | email               |
| magento2 | quote      | quote_address            | firstname          | firstName           |
| magento2 | quote      | quote_address            | lastname           | lastName            |
| magento2 | quote      | quote_address            | company            | company             |
| magento2 | quote      | quote_address            | street             | streetAddress       |
| magento2 | quote      | quote_address            | city               | city                |
| magento2 | quote      | quote_address            | postcode           | postcode            |
| magento2 | quote      | quote_address            | telephone          | phoneNumber         |
| magento2 | quote      | quote_address            | fax                | phoneNumber         |
| magento2 | quote      | quote_address            | vat_id             | vat                 |
| magento2 | admin      | admin_user               | firstname          | firstName           |
| magento2 | admin      | admin_user               | lastname           | lastName            |
| magento2 | admin      | admin_user               | email              | email               |
| magento2 | admin      | admin_user               | username           | firstName           |
| magento2 | admin      | admin_user               | password           | password            |
| magento2 | email      | email_contact            | email              | email               |
| magento2 | email      | email_automation         | email              | email               |
| magento2 | email      | email_campaign           | email              | email               |
| magento2 | customer   | customer_entity          | email              | email               |
| magento2 | customer   | customer_entity          | firstname          | firstName           |
| magento2 | customer   | customer_entity          | lastname           | lastName            |
| magento2 | customer   | customer_address_entity  | firstname          | firstName           |
| magento2 | customer   | customer_address_entity  | lastname           | lastName            |
| magento2 | customer   | customer_address_entity  | company            | company             |
| magento2 | customer   | customer_address_entity  | street             | streetAddress       |
| magento2 | customer   | customer_address_entity  | city               | city                |
| magento2 | customer   | customer_address_entity  | postcode           | postcode            |
| magento2 | customer   | customer_address_entity  | telephone          | phoneNumber         |
| magento2 | customer   | customer_address_entity  | fax                | phoneNumber         |
| magento2 | customer   | customer_grid_flat       | name               | name                |
| magento2 | customer   | customer_grid_flat       | firstname          | firstName           |
| magento2 | customer   | customer_grid_flat       | email              | email               |
| magento2 | customer   | customer_grid_flat       | dob                | dateTimeThisCentury |
| magento2 | customer   | customer_grid_flat       | billing_full       | address             |
| magento2 | customer   | customer_grid_flat       | shipping_full      | address             |
| magento2 | customer   | customer_grid_flat       | billing_firstname  | firstName           |
| magento2 | customer   | customer_grid_flat       | billing_lastname   | lastName            |
| magento2 | customer   | customer_grid_flat       | billing_telephone  | phoneNumber         |
| magento2 | customer   | customer_grid_flat       | billing_postcode   | postcode            |
| magento2 | customer   | customer_grid_flat       | billing_street     | streetAddress       |
| magento2 | customer   | customer_grid_flat       | billing_city       | city                |
| magento2 | customer   | customer_grid_flat       | billing_fax        | phoneNumber         |
| magento2 | customer   | customer_grid_flat       | billing_vat_id     | vat                 |
| magento2 | customer   | customer_grid_flat       | billing_company    | company             |
| magento2 | shipment   | sales_shipment           | customer_note      | sentence            |
| magento2 | shipment   | sales_shipment_comment   | comment            | sentence            |
| magento2 | shipment   | sales_shipment_grid      | customer_email     | email               |
| magento2 | shipment   | sales_shipment_grid      | customer_name      | name                |
| magento2 | shipment   | sales_shipment_grid      | shipping_name      | name                |
| magento2 | shipment   | sales_shipment_grid      | billing_name       | name                |
| magento2 | shipment   | sales_shipment_grid      | shipping_address   | address             |
| magento2 | shipment   | sales_shipment_grid      | billing_address    | address             |
+----------+------------+--------------------------+--------------------+---------------------+

```

### Building from source

To build the phar from source you can use the `build.sh` script. Note that it depends on [Box](https://github.com/box-project/box2) so you need to make sure that it is available first.

```
cd masquerade
# Find the latest version here: https://github.com/box-project/box2#as-a-phar-recommended
curl -LSs https://box-project.github.io/box2/installer.php | php
composer update
chmod u+x build.sh
./build.sh
bin/masquerade
```

## Generating a new release changelog

```
export BRANCH=master
export VERSION=$(date "+%Y%m%d.%H%M%S")
gbp dch --debian-tag="%(version)s" --new-version=$VERSION --debian-branch $BRANCH --release --commit
```

### Packaging

To build a deb that installs the phar in the dist directory run:

```
gbp buildpackage --git-pbuilder --git-dist=xenial --git-arch=amd64 --git-ignore-branch --git-ignore-new
```

Make sure you have a [cow environment](https://wiki.debian.org/git-pbuilder) configured for your branch and distribution. Keep in mind that the packaging does not build a new phar file, so if you want to package your local revision for testing please run `./build.sh` and copy the created `bin/masquerade` to `dist/masquerade.phar` first.

#### Built by elgentos
