=== Saito Mini Cart ===
Contributors: lucastsl
Tags: woocommerce, mini cart, free shipping bar, cross-sells, cart
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A better WooCommerce mini cart: free shipping progress bar, recommended products carousel and custom cart/checkout buttons.

== Description ==

Saito Mini Cart improves the WooCommerce mini cart (the cart drawer opened from the header), for block themes (Mini-Cart block) and classic themes (cart widget):

* **Free shipping progress bar** — "Only €12.50 left for free shipping", with a progress bar, in the mini cart and above the cart page. The threshold is read from your WooCommerce "Free shipping" method (shipping zone of the customer), or set manually. The remaining amount is computed by the server exactly like WooCommerce does for free shipping (displayed subtotal, coupons deducted unless the method ignores them), never guessed from the displayed text. Also available anywhere with the `[saito_mini_cart_progress]` shortcode.
* **Recommended products** — a carousel at the bottom of the mini cart (or anywhere with the `[saito_mini_cart_recommendations]` shortcode), with one-click "Add" for simple products. Products come from the cross-sells of the cart items, then their upsells, then your own selection, then your best sellers. Products already in the cart, out of stock or hidden are never suggested.
* **Custom buttons** — text and link of the "View cart" and "Checkout" buttons of the mini cart.

Custom texts are translatable with WPML (String Translation). Settings are under the shared **Saito** menu, next to the other Saito plugins.

Coming from the private "Saito Dev Toolkit" plugin? On activation, its free shipping bar and mini cart button settings are imported automatically.

== Installation ==

1. Install and activate the plugin (WooCommerce is required).
2. Go to **Saito → Mini-panier** and choose what to display.
3. For the automatic threshold, add a "Free shipping" method with a minimum order amount in **WooCommerce → Settings → Shipping**.

== Frequently Asked Questions ==

= Does it work with block themes and classic themes? =

Yes. With a block theme, the bar and the carousel are added to the WooCommerce Mini-Cart block. With a classic theme, they are added to the cart widget and updated by WooCommerce cart fragments.

= Can I choose where the bar and the carousel appear in the mini cart? =

Yes. Uncheck the automatic insertion in the settings, then add the `[saito_mini_cart_progress]` and `[saito_mini_cart_recommendations]` shortcodes where you want them, for example in the Mini-Cart template part (Appearance → Editor → Patterns → Mini-Cart, Shortcode block). They are updated whenever the cart changes.

= Which amount is used for the free shipping bar? =

The same as WooCommerce's "Free shipping" method: the cart subtotal as displayed (including tax if your prices are displayed including tax), minus coupons unless the method is set to apply the minimum before coupon discounts.

= Does it send data to external services? =

No. Everything runs on your site: the bar and the recommendations are computed by WordPress and loaded through your site's REST API.

== Screenshots ==

1. Free shipping bar and recommended products in the mini cart.
2. Settings: free shipping bar.

== Changelog ==

= 1.0.0 =
* First release: free shipping progress bar, recommended products carousel, custom mini cart buttons, import of Saito Dev Toolkit settings.
