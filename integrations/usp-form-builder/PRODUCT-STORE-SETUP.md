# USP Form Builder Pro: Product Store Setup

The Laravel product store at `https://usp.com.pk/` is the source of truth for products, plans, orders, license keys, website activations, and releases. Product identity is the immutable lowercase slug, so each plugin package must be configured to use the slug of its matching store product.

## Configure each product

1. In the website admin, create a product with a unique slug (for example, `usp-form-builder`) and publish it.
2. Add the product's free and Pro plans, set the per-license website limit and term, and publish them.
3. Upload a ZIP for each release. It must contain only the product folder and its main plugin PHP file; packages are kept in private storage.
4. Configure the exchange rate and payment methods before publishing plans for purchase.

## Connect the WordPress plugin

1. In each WordPress site using USP Form Builder, open **USP Forms → Pro & Security**.
2. Set `USP_FORM_BUILDER_PRODUCT_SLUG` in the plugin's main PHP file to exactly match the product slug created above. Do not make this customer-editable; build each separate plugin product with its own fixed slug.
3. Enter the public pricing and customer account URLs and save the settings.
4. Enter a key purchased from the website account and select **Activate License**.
5. Use **Verify** to check status. The plugin stores the per-site activation token returned by the API and sends it for status checks and deactivation.
6. Use **Deactivate** before moving a license to another site so the store can release the current activation.

The plugin sends JSON POST requests to `https://usp.com.pk/api/licenses/v1` over HTTPS. Requests include the product slug, key, and WordPress home URL. The server hashes keys for lookup, checks that the product matches, applies the license expiry and activation limit, and requires the activation token for follow-up checks. The key alone is never treated as proof that an already-activated site owns its activation.

## Scope and launch checks

- The plugin continues to allow a short grace period after a temporary license API outage. Pro features can therefore remain active briefly after a server-side revocation if the plugin cannot reach the API.
- Existing EDD-issued keys are not automatically imported into the Laravel store; migrate or reissue those licenses before switching existing customers to this API.
- Automatic plugin updates are not included. The current client validates licenses; the API also provides signed package URLs for future updater integration.
- Before selling, test distinct product slugs, activation-limit rejection, expiry, renewal, revocation, site deactivation, a mismatched product slug, and a temporary API outage on staging.
