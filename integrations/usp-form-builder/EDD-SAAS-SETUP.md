# USP Form Builder Pro: EDD SaaS License Setup

This integration uses Easy Digital Downloads (EDD) Software Licensing on `https://usp.com.pk/` as the license authority. It does not create or configure products on the store website; that setup must be completed by a store administrator.

## Configure the store

1. Install and configure Easy Digital Downloads and its Software Licensing extension on `usp.com.pk`, with HTTPS enabled.
2. Create and publish a **USP Form Builder Pro** Download. Enable license generation for the product, and choose its activation limit and expiry policy.
3. Configure the product's price and checkout in EDD. If you want recurring subscriptions, configure the EDD recurring-payment capability and verify that renewals extend or maintain the license as intended.
4. Publish a pricing/checkout page and an EDD customer account or purchase-history page.
5. In EDD, record the numeric Download ID of the Pro product. Do not use a license key, password, or payment identifier as the Download ID.

## Connect plugin installations

1. In each WordPress site using USP Form Builder, open **USP Forms → Pro & Security**.
2. Enter the EDD Download ID, the public pricing page URL, and the customer account URL. Select **Save Store Settings**.
3. Enter a license key issued by EDD for the Pro Download and select **Activate License**.
4. Use **Verify** to query EDD Software Licensing. The plugin periodically checks status and expiry; it allows a short grace period after a temporary store connection failure.
5. Use **Deactivate** before moving a license to another site so EDD can release the current site's activation.

The plugin sends the standard EDD Software Licensing `activate_license`, `check_license`, and `deactivate_license` requests to `usp.com.pk`, including the configured Download ID and the licensed site's home URL. License keys are never accepted based on their format alone.

## Scope and launch checks

- Product prices, checkout, customer accounts, renewals, refunds, and license generation are configured in EDD on `usp.com.pk`.
- The plugin's dashboard links to the configured pricing and customer-account pages. Its profile link also opens the current WordPress administrator's profile.
- License validation is implemented, but automatic plugin updates, remote account synchronization, and purchase/checkout-page creation are not part of this integration.
- Before selling subscriptions, test a real EDD license activation, activation-limit rejection, expiration, renewal, refund/revocation, site deactivation, and a temporary API outage on a staging site.
