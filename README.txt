=== Cowpay For WooCommerce ===
Contributors: ammarrabie
Tags: cowpay, credit card, fawry, PayAtFawry
Requires at least: 5.0
Tested up to: 5.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accepts credit cards, Pay At Fawry using Cowpay services

== Description ==
COWPAY is a premium payment technology enabler dedicated to helping businesses transform   
their operation collecting , splitting , and disbursing money digitally!

Cowpay offers the following Environment options:
1. **Staging Environment**: Staging is your initial account environment on which you must do your testing operations without your cards being charged.
2. **Production Environment**: This is the your actual environment, you should put your website into production before deploying.

You can charge your customers using these methods:
1. Credit Card
2. Pay At Fawry
3. Cowpay Checkout

You should enable these methods in the WooCommerce payments settings.
== Installation ==
You should have the WooCommerce plugin installed and active.
1. Extract `woo-cowpay.zip` in the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==
= How to generate signature? =
Signatures are validation tokens used by cowpay to insure the identity of the merchant beside authentication tokens starting from API v1

= What is merchant_reference_id Key? =
Cowpay requires a unique id for each charge request from the merchant as each charge request represents a separated order on our system. You can use numbers, strings or combination of both.

= How to get customer_merchant_reference_id? =
Cowpay requires customer being charged id on the merchant system. It's value valid format is the same of merchant_reference_id.

= How to go live within my account? =
Your initial account status is staging which means no actual amounts being paid or charged based on your requests. In order to go live please contact one of our business team, and they will take care of that transmission.

== Screenshots ==

1. Admin, Cowpay settings.
2. Accepts Fawry POS
3. Accepts credit card
4. Credit Card OTP (one time password) redirection
5. Example Order Notes

== Changelog ==

= 1.0.0 =
* Initial cowpay official release

== Upgrade Notice ==
= 1.0.0 =